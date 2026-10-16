<?php

function inflatePdf(string $path): string
{
    $bytes = file_get_contents($path);
    $out = '';
    if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $bytes, $matches)) {
        foreach ($matches[1] as $stream) {
            $plain = @gzuncompress($stream);
            if ($plain === false) {
                $plain = @gzinflate($stream);
            }
            if ($plain === false) {
                $plain = @gzdecode($stream);
            }
            $out .= "\n".(is_string($plain) ? $plain : '');
        }
    }

    return $out;
}

foreach (['template.pdf', 'template-layer.pdf'] as $name) {
    $text = inflatePdf(__DIR__.'/../../resources/quotations/ventilation/'.$name);
    echo "==== $name\n";
    if (preg_match_all('/\[(.*?)\]\s*TJ/s', $text, $matches)) {
        $shown = [];
        foreach ($matches[1] as $chunk) {
            if (! preg_match_all('/\(([^)]*)\)/', $chunk, $parts)) {
                continue;
            }
            $s = implode('', $parts[1]);
            if (preg_match('/\d/', $s) && strlen($s) < 40) {
                $shown[] = $s;
            }
        }
        $shown = array_values(array_unique($shown));
        echo implode(' | ', array_slice($shown, 0, 80)).PHP_EOL;
    }
}
