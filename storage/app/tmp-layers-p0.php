<?php

$zip = new ZipArchive();
$zip->open(__DIR__.'/../../docs/quotations/layers/layers_quotation.docx');
$xml = $zip->getFromName('word/document.xml');
$rels = $zip->getFromName('word/_rels/document.xml.rels');
$zip->close();

$dom = new DOMDocument();
$dom->preserveWhiteSpace = true;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
foreach ([
    'w' => 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
    'wp' => 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing',
    'a' => 'http://schemas.openxmlformats.org/drawingml/2006/main',
    'r' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
    'wps' => 'http://schemas.microsoft.com/office/word/2010/wordprocessingShape',
    'v' => 'urn:schemas-microsoft-com:vml',
    'mc' => 'http://schemas.openxmlformats.org/markup-compatibility/2006',
] as $p => $ns) {
    $xpath->registerNamespace($p, $ns);
}

$relDom = new DOMDocument();
$relDom->loadXML($rels);
$relX = new DOMXPath($relDom);
$relX->registerNamespace('pr', 'http://schemas.openxmlformats.org/package/2006/relationships');
$images = [];
foreach ($relX->query('//pr:Relationship') as $rel) {
    $images[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
}

$body = $xpath->query('/w:document/w:body')->item(0);
$p0 = null;
foreach ($body->childNodes as $child) {
    if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'p') {
        $p0 = $child;
        break;
    }
}

$i = 0;
foreach ($p0->childNodes as $node) {
    if ($node->nodeType !== XML_ELEMENT_NODE) {
        continue;
    }
    $local = $node->localName;
    if ($local === 'pPr') {
        echo "#$i pPr\n";
        $i++;
        continue;
    }
    if ($local !== 'r') {
        echo "#$i <$local>\n";
        $i++;
        continue;
    }
    $br = $xpath->query('./w:br', $node);
    if ($br->length) {
        $type = $br->item(0)->getAttribute('w:type') ?: 'line';
        echo "#$i BR $type\n";
        $i++;
        continue;
    }
    $t = $xpath->query('.//w:t', $node);
    if ($t->length && $xpath->query('.//w:drawing', $node)->length === 0) {
        $bits = [];
        foreach ($t as $tn) {
            $bits[] = trim($tn->textContent);
        }
        echo "#$i TEXT ".implode('', $bits)."\n";
        $i++;
        continue;
    }
    $drawing = $xpath->query('.//w:drawing', $node)->item(0);
    if (! $drawing) {
        echo "#$i RUN other\n";
        $i++;
        continue;
    }
    $anchor = $xpath->query('.//wp:anchor | .//wp:inline', $drawing)->item(0);
    $kind = $anchor ? $anchor->localName : '?';
    $behind = $anchor ? $anchor->getAttribute('behindDoc') : '';
    $h = $xpath->query('.//wp:positionH', $drawing)->item(0);
    $v = $xpath->query('.//wp:positionV', $drawing)->item(0);
    $hRel = $h ? $h->getAttribute('relativeFrom') : '';
    $vRel = $v ? $v->getAttribute('relativeFrom') : '';
    $hOff = $xpath->query('.//wp:positionH/wp:posOffset', $drawing)->item(0);
    $vOff = $xpath->query('.//wp:positionV/wp:posOffset', $drawing)->item(0);
    $ext = $xpath->query('.//wp:extent', $drawing)->item(0);
    $cx = $ext ? round($ext->getAttribute('cx') / 36000, 1) : '';
    $cy = $ext ? round($ext->getAttribute('cy') / 36000, 1) : '';
    $hx = $hOff ? round(((int) $hOff->textContent) / 36000, 1) : '';
    $vy = $vOff ? round(((int) $vOff->textContent) / 36000, 1) : '';
    $blip = $xpath->query('.//a:blip', $drawing)->item(0);
    $img = $blip ? ($images[$blip->getAttribute('r:embed')] ?? $blip->getAttribute('r:embed')) : '';
    $texts = $xpath->query('.//w:t', $drawing);
    $bits = [];
    foreach ($texts as $tn) {
        $val = trim(preg_replace('/\s+/u', ' ', $tn->textContent));
        if ($val !== '') {
            $bits[] = $val;
        }
    }
    $label = $img !== '' ? $img : mb_substr(implode(' | ', $bits), 0, 80);
    echo "#$i DRAW $kind behind=$behind H=$hRel:$hx V=$vRel:$vy {$cx}x{$cy}mm $label\n";
    $i++;
}
