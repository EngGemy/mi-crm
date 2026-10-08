<?php

$zip = new ZipArchive();
$zip->open(__DIR__.'/../../docs/quotations/layers/layers_quotation.docx');
$xml = $zip->getFromName('word/document.xml');
$zip->close();

$dom = new DOMDocument();
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
$xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
$xpath->registerNamespace('wps', 'http://schemas.microsoft.com/office/word/2010/wordprocessingShape');
$xpath->registerNamespace('v', 'urn:schemas-microsoft-com:vml');

$breaks = $xpath->query('//w:br[@w:type="page"] | //w:lastRenderedPageBreak');
echo 'pageBreaks='.$breaks->length.PHP_EOL;
echo 'sectPr='.$xpath->query('//w:sectPr')->length.PHP_EOL;
echo 'txbx='.$xpath->query('//w:txbxContent')->length.PHP_EOL;
echo 'drawings='.$xpath->query('//w:drawing')->length.PHP_EOL;

$nodes = $xpath->query('//w:t');
echo 'textNodes='.$nodes->length.PHP_EOL;
$i = 0;
foreach ($nodes as $n) {
    $t = preg_replace('/\s+/u', ' ', $n->textContent);
    if (trim((string) $t) === '') {
        continue;
    }
    echo $i.'|'.$t.PHP_EOL;
    $i++;
}
