<?php

namespace App\Services\Pricing;

use App\Support\NodeBinaryResolver;
use RuntimeException;
use Symfony\Component\Process\Process;

class HtmlScreenshotService
{
    public function capture(string $htmlPath, string $outputPath, int $width, int $height): void
    {
        if (! is_file($htmlPath)) {
            throw new RuntimeException("HTML file not found: {$htmlPath}");
        }

        $scriptPath = base_path('scripts/screenshot.cjs');
        if (! is_file($scriptPath)) {
            throw new RuntimeException("Screenshot script not found: {$scriptPath}");
        }

        if (! is_file(base_path('node_modules/playwright/package.json'))) {
            throw new RuntimeException(
                'Playwright is not installed. On the server run: npm ci && npx playwright install chromium'
            );
        }

        $outputDir = dirname($outputPath);
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $browserPath = storage_path('app/playwright-browsers');
        if (! is_dir($browserPath)) {
            mkdir($browserPath, 0755, true);
        }

        $node = NodeBinaryResolver::resolve();
        $env = array_merge($_SERVER, $_ENV, [
            'TEMP' => sys_get_temp_dir(),
            'TMP' => sys_get_temp_dir(),
            'PLAYWRIGHT_BROWSERS_PATH' => $browserPath,
        ]);

        $process = new Process([
            $node,
            $scriptPath,
            $htmlPath,
            $outputPath,
            (string) $width,
            (string) $height,
        ], base_path(), $env);

        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            $error = trim($process->getErrorOutput() ?: $process->getOutput());

            throw new RuntimeException(
                'Card generation failed: '.($error !== '' ? $error : 'unknown error')
            );
        }

        if (! is_file($outputPath)) {
            throw new RuntimeException('Screenshot file was not created.');
        }
    }
}
