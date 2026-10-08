<?php

$zip = new ZipArchive();
$zip->open(__DIR__.'/../../docs/quotations/layers/layers_quotation.docx');
$xml = $zip->getFromName('word/document.xml');
$zip->close();

$dom = new DOMDocument();
$dom->preserveWhiteSpace = true;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
foreach ([
    'w' => 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
    'wp' => 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing',
    'a' => 'http://schemas.openxmlformats.org/drawingml/2006/main',
] as $p => $ns) {
    $xpath->registerNamespace($p, $ns);
}

$body = $xpath->query('/w:document/w:body')->item(0);
$p0 = null;
foreach ($body->childNodes as $child) {
    if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'p') {
        $p0 = $child;
        break;
    }
}

$pPr = $xpath->query('./w:pPr', $p0)->item(0);
echo "PPR:\n".$dom->saveXML($pPr)."\n\n";

$i = 0;
$pageGuess = 1;
$lines = 0;
foreach ($p0->childNodes as $node) {
    if ($node->nodeType !== XML_ELEMENT_NODE || $node->localName !== 'r') {
        continue;
    }
    $br = $xpath->query('./w:br', $node);
    if ($br->length) {
        $type = $br->item(0)->getAttribute('w:type') ?: 'line';
        if ($type === 'page') {
            $pageGuess++;
            echo "#$i PAGEBREAK -> page $pageGuess after $lines lines\n";
            $lines = 0;
        } else {
            $lines++;
        }
        $i++;
        continue;
    }
    $drawing = $xpath->query('.//w:drawing', $node)->item(0);
    if (! $drawing) {
        $i++;
        continue;
    }
    $v = $xpath->query('.//wp:positionV', $drawing)->item(0);
    $vRel = $v ? $v->getAttribute('relativeFrom') : '';
    $vOff = $xpath->query('.//wp:positionV/wp:posOffset', $drawing)->item(0);
    $vy = $vOff ? round(((int) $vOff->textContent) / 36000, 1) : '';
    $texts = $xpath->query('.//w:t', $drawing);
    $bits = [];
    foreach ($texts as $tn) {
        $val = trim(preg_replace('/\s+/u', ' ', $tn->textContent));
        if ($val !== '') {
            $bits[] = $val;
        }
    }
    $blip = $xpath->query('.//a:blip', $drawing)->length ? 'IMG' : '';
    $label = $blip !== '' ? 'IMG' : mb_substr(implode(' | ', $bits), 0, 70);
    if ($i >= 46) {
        echo "#$i page~$pageGuess lines=$lines V=$vRel:$vy $label\n";
    }
    $i++;
}
