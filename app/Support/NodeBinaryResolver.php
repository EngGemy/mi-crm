<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Process\Process;

class NodeBinaryResolver
{
    public static function resolve(): string
    {
        $configured = config('services.node.binary');
        if (is_string($configured) && $configured !== '' && self::isUsable($configured)) {
            return $configured;
        }

        $candidates = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = [
                'C:\\Program Files\\nodejs\\node.exe',
                'C:\\Program Files (x86)\\nodejs\\node.exe',
            ];

            foreach (glob('C:/laragon/bin/nodejs/node-*/node.exe') ?: [] as $path) {
                $candidates[] = $path;
            }
        } else {
            $candidates = [
                '/usr/local/bin/node',
                '/usr/bin/node',
                '/opt/cpanel/ea-nodejs18/bin/node',
                '/opt/cpanel/ea-nodejs20/bin/node',
                '/opt/cpanel/ea-nodejs22/bin/node',
            ];

            $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? null);
            if (is_string($home) && $home !== '') {
                foreach (glob("{$home}/nodevenv/*/bin/node") ?: [] as $path) {
                    $candidates[] = $path;
                }
            }
        }

        foreach (array_unique($candidates) as $candidate) {
            if (self::isUsable($candidate)) {
                return $candidate;
            }
        }

        $onPath = self::findOnPath();
        if ($onPath !== null) {
            return $onPath;
        }

        throw new RuntimeException(
            'Node.js is not available. Install Node.js on the server or set NODE_BINARY in .env '
            .'(cPanel: Setup Node.js App → copy the node binary path).'
        );
    }

    private static function findOnPath(): ?string
    {
        $command = PHP_OS_FAMILY === 'Windows' ? 'where node' : 'command -v node';
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $line = trim(explode("\n", trim($process->getOutput()))[0]);

        return self::isUsable($line) ? $line : null;
    }

    private static function isUsable(string $path): bool
    {
        if ($path === '' || $path === 'node') {
            return false;
        }

        return is_file($path) && is_executable($path);
    }
}
