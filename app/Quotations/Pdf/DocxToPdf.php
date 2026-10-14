<?php

namespace App\Quotations\Pdf;

use RuntimeException;

class DocxToPdf
{
    public function convert(string $docxPath): string
    {
        $libre = $this->viaLibreOffice($docxPath);
        if ($libre !== null) {
            return $libre;
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            throw new RuntimeException('LibreOffice غير متاح على السيرفر.');
        }

        $pdfPath = $docxPath.'.pdf';
        $scriptPath = $docxPath.'.ps1';
        $docx = str_replace("'", "''", $docxPath);
        $pdf = str_replace("'", "''", $pdfPath);
        file_put_contents($scriptPath, <<<PS1
\$ErrorActionPreference = 'Stop'
\$word = \$null
\$doc = \$null
try {
  \$word = New-Object -ComObject Word.Application
  \$word.Visible = \$false
  \$word.DisplayAlerts = 0
  \$doc = \$word.Documents.Open('{$docx}', \$false, \$true, \$false)
  \$doc.ExportAsFixedFormat('{$pdf}', 17)
} catch {
  [Console]::Error.WriteLine(\$_.Exception.Message)
  exit 1
} finally {
  if (\$doc -ne \$null) { \$doc.Close(0) }
  if (\$word -ne \$null) { \$word.Quit() }
}
PS1);

        $detail = 'تأكد أن Microsoft Word مثبت.';
        $code = 1;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            @unlink($pdfPath);
            $output = [];
            exec('powershell -NoProfile -STA -NonInteractive -ExecutionPolicy Bypass -File '.escapeshellarg($scriptPath).' 2>&1', $output, $code);
            if ($code === 0 && is_file($pdfPath)) {
                break;
            }
            $detail = trim(implode(' ', $output)) ?: $detail;
            if ($attempt < 3) {
                sleep(2);
            }
        }
        @unlink($scriptPath);

        if ($code !== 0 || ! is_file($pdfPath)) {
            throw new RuntimeException($detail);
        }

        $bytes = file_get_contents($pdfPath);
        @unlink($pdfPath);
        if ($bytes === false || ! str_starts_with($bytes, '%PDF')) {
            throw new RuntimeException('ملف PDF لم يُنشأ بشكل صحيح.');
        }

        return $bytes;
    }

    private function viaLibreOffice(string $docxPath): ?string
    {
        $binary = $this->libreOfficeBinary();
        if ($binary === null) {
            return null;
        }

        $outDir = dirname($docxPath);
        exec(
            escapeshellarg($binary).' --headless --norestore --convert-to pdf --outdir '.escapeshellarg($outDir).' '.escapeshellarg($docxPath),
            $output,
            $code
        );

        $pdfPath = $outDir.DIRECTORY_SEPARATOR.pathinfo($docxPath, PATHINFO_FILENAME).'.pdf';
        if ($code !== 0 || ! is_file($pdfPath)) {
            @unlink($pdfPath);

            return null;
        }

        $bytes = file_get_contents($pdfPath);
        @unlink($pdfPath);
        if ($bytes === false || ! str_starts_with($bytes, '%PDF')) {
            return null;
        }

        return $bytes;
    }

    private function libreOfficeBinary(): ?string
    {
        $candidates = PHP_OS_FAMILY === 'Windows'
            ? [
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            ]
            : ['soffice', 'libreoffice'];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return null;
        }

        exec('command -v soffice', $output, $code);

        return $code === 0 && isset($output[0]) && $output[0] !== '' ? trim($output[0]) : null;
    }
}
