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

$body = $xpath->query('/w:document/w:body')->item(0);
$pIndex = 0;
foreach ($body->childNodes as $child) {
    if ($child->nodeType !== XML_ELEMENT_NODE) {
        continue;
    }
    $drawings = $xpath->query('.//w:drawing', $child);
    foreach ($drawings as $drawing) {
        $v = $xpath->query('.//wp:positionV', $drawing)->item(0);
        if (! $v || $v->getAttribute('relativeFrom') !== 'paragraph') {
            continue;
        }
        $off = $xpath->query('.//wp:posOffset', $v)->item(0);
        $y = $off ? round(((int) $off->textContent) / 36000, 1) : 'align';
        $bits = [];
        foreach ($xpath->query('.//w:t', $drawing) as $t) {
            $val = trim(preg_replace('/\s+/u', ' ', $t->textContent));
            if ($val !== '') {
                $bits[] = $val;
            }
        }
        echo "p$pIndex y=$y ".mb_substr(implode(' | ', $bits), 0, 90)."\n";
    }
    $pIndex++;
}
