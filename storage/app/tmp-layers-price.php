<?php

$zip = new ZipArchive();
$zip->open(__DIR__.'/../../docs/quotations/layers/layers_quotation.docx');
$xml = $zip->getFromName('word/document.xml');
$zip->close();

$dom = new DOMDocument();
$dom->preserveWhiteSpace = true;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
$xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
$xpath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');

$needles = ['130', '6,789', 'دولار', 'جنيه', 'USD', 'EGP', 'السعر', 'Amount'];
$nodes = $xpath->query('//w:t');
$i = 0;
foreach ($nodes as $n) {
    $t = trim($n->textContent);
    foreach ($needles as $needle) {
        if ($t !== '' && mb_strpos($t, $needle) !== false) {
            $drawing = $n;
            while ($drawing && $drawing->localName !== 'drawing' && $drawing->localName !== 'body') {
                $drawing = $drawing->parentNode;
            }
            $pos = '';
            if ($drawing && $drawing->localName === 'drawing') {
                $v = $xpath->query('.//wp:positionV', $drawing)->item(0);
                $h = $xpath->query('.//wp:positionH', $drawing)->item(0);
                $vOff = $v ? $xpath->query('.//wp:posOffset', $v)->item(0) : null;
                $hOff = $h ? $xpath->query('.//wp:posOffset', $h)->item(0) : null;
                $vy = $vOff ? round(((int) $vOff->textContent) / 36000, 1) : ($v ? $v->getAttribute('relativeFrom') : '');
                $hx = $hOff ? round(((int) $hOff->textContent) / 36000, 1) : ($h ? 'align' : '');
                $pos = ' H='.($h ? $h->getAttribute('relativeFrom') : '').":$hx V=".($v ? $v->getAttribute('relativeFrom') : '').":$vy";
            } else {
                $pos = ' inline';
            }
            echo "$t | $pos\n";
            break;
        }
    }
    $i++;
}
