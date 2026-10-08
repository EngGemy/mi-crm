<?php

$zip = new ZipArchive();
$zip->open(__DIR__.'/../../docs/quotations/layers/layers_quotation.docx');
$xml = $zip->getFromName('word/document.xml');
$zip->close();

$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
$xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
$xpath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
$xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
$xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
$xpath->registerNamespace('wps', 'http://schemas.microsoft.com/office/word/2010/wordprocessingShape');
$xpath->registerNamespace('mc', 'http://schemas.openxmlformats.org/markup-compatibility/2006');

$body = $xpath->query('/w:document/w:body')->item(0);
$page = 1;
$index = 0;
foreach ($body->childNodes as $child) {
    if ($child->nodeType !== XML_ELEMENT_NODE) {
        continue;
    }
    $name = $child->localName;
    $text = '';
    $texts = $xpath->query('.//w:t', $child);
    $bits = [];
    foreach ($texts as $t) {
        $v = trim(preg_replace('/\s+/u', ' ', $t->textContent));
        if ($v !== '') {
            $bits[] = $v;
        }
    }
    $text = implode(' ', array_slice($bits, 0, 8));
    if (mb_strlen($text) > 120) {
        $text = mb_substr($text, 0, 120).'…';
    }
    $draws = $xpath->query('.//w:drawing', $child)->length;
    $breaks = $xpath->query('.//w:br', $child)->length;
    $anchor = $xpath->query('.//wp:anchor | .//wp:inline', $child);
    $pos = '';
    if ($anchor->length) {
        $a = $anchor->item(0);
        $pos = $a->localName;
        $off = $xpath->query('.//wp:positionH/wp:posOffset | .//wp:positionV/wp:posOffset', $a);
        $coords = [];
        foreach ($off as $o) {
            $coords[] = $o->textContent;
        }
        $pos .= ' '.implode(',', $coords);
    }
    echo sprintf("p%d #%d <%s> draws=%d br=%d %s | %s\n", $page, $index, $name, $draws, $breaks, $pos, $text);
    if ($xpath->query('.//w:br[@w:type="page"] | .//w:lastRenderedPageBreak', $child)->length) {
        $page++;
        echo "---- PAGE $page ----\n";
    }
    $index++;
    if ($index > 40) {
        break;
    }
}
