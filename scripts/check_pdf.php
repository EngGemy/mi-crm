<?php

require __DIR__ . '/../vendor/autoload.php';

$parser = new \Smalot\PdfParser\Parser();

echo "=== فحص PDF العقد ===\n";
$pdf = $parser->parseFile(__DIR__ . '/../sample_contract_dual_signature.pdf');
$text = $pdf->getText();
echo "عدد الصفحات: " . count($pdf->getPages()) . "\n";
echo "يحتوي 'الطرف الأول': " . (str_contains($text, 'الطرف الأول') ? 'YES' : 'NO') . "\n";
echo "يحتوي 'الطرف الثاني': " . (str_contains($text, 'الطرف الثاني') ? 'YES' : 'NO') . "\n";
echo "يحتوي 'التوقيع': " . (str_contains($text, 'التوقيع') ? 'YES' : 'NO') . "\n";
echo "يحتوي 'صفحة': " . (str_contains($text, 'صفحة') ? 'YES' : 'NO') . "\n";
echo "\n=== فحص PDF عرض السعر ===\n";
$pdf2 = $parser->parseFile(__DIR__ . '/../sample_quotation_calc_sync.pdf');
$text2 = $pdf2->getText();
echo "عدد الصفحات: " . count($pdf2->getPages()) . "\n";
echo "يحتوي 'البيانات الفنية': " . (str_contains($text2, 'البيانات الفنية') ? 'YES' : 'NO') . "\n";
echo "يحتوي 'عدد الطيور': " . (str_contains($text2, 'عدد الطيور') ? 'YES' : 'NO') . "\n";
echo "يحتوي 'الإنشاءات': " . (str_contains($text2, 'الإنشاءات') ? 'YES' : 'NO') . "\n";
echo "يحتوي 'بطاريات العنبر': " . (str_contains($text2, 'بطاريات العنبر') ? 'YES' : 'NO') . "\n";
echo "يحتوي 'معادلة الشفاطات': " . (str_contains($text2, 'معادلة الشفاطات') ? 'YES' : 'NO') . "\n";
echo "يحتوي 'مساحة الخرسانة': " . (str_contains($text2, 'مساحة الخرسانة') ? 'YES' : 'NO') . "\n";
