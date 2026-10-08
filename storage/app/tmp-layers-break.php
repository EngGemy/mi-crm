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

$breaks = $xpath->query('//w:lastRenderedPageBreak');
echo 'renderedBreaks='.$breaks->length.PHP_EOL;
foreach ($breaks as $b) {
    $run = $b;
    while ($run && ! ($run->localName === 'r' && $run->parentNode && $run->parentNode->localName === 'p')) {
        $run = $run->parentNode;
    }
    $p = $run ? $run->parentNode : null;
    $idx = 0;
    if ($p) {
        foreach ($p->childNodes as $child) {
            if ($child->isSameNode($run)) {
                break;
            }
            $idx++;
        }
    }
    echo "break near run index $idx parent=".$p->localName.PHP_EOL;
}

$sect = $xpath->query('//w:sectPr')->item(0);
echo $dom->saveXML($sect).PHP_EOL;

$rels = ['paragraph' => 0, 'page' => 0];
foreach ($xpath->query('//wp:positionV') as $v) {
    $rel = $v->getAttribute('relativeFrom');
    $rels[$rel] = ($rels[$rel] ?? 0) + 1;
}
echo json_encode($rels, JSON_UNESCAPED_UNICODE).PHP_EOL;
