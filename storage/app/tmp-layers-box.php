<?php

$zip = new ZipArchive();
$zip->open(__DIR__.'/../../docs/quotations/layers/layers_quotation.docx');
$xml = $zip->getFromName('word/document.xml');
$zip->close();

$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
foreach ([
    'w' => 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
    'wps' => 'http://schemas.microsoft.com/office/word/2010/wordprocessingShape',
    'mc' => 'http://schemas.openxmlformats.org/markup-compatibility/2006',
] as $p => $ns) {
    $xpath->registerNamespace($p, $ns);
}

$hits = $xpath->query('//w:t[contains(., "محمد جامع")]');
echo 'hits='.$hits->length.PHP_EOL;
$node = $hits->item(0);
$cur = $node;
while ($cur && $cur->localName !== 'drawing' && $cur->localName !== 'AlternateContent') {
    $cur = $cur->parentNode;
}
$xmlOut = $dom->saveXML($cur);
file_put_contents(__DIR__.'/tmp-box.xml', $xmlOut);
echo 'bytes='.strlen($xmlOut).' root='.$cur->localName.PHP_EOL;
