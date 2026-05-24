# خطوط النظام

ضع هنا ملفات خط Cairo (مطلوب لـ DomPDF لتوليد العقود بالعربية):

## التحميل:

1. زُر: https://fonts.google.com/specimen/Cairo
2. اضغط "Download Family"
3. فك الضغط واستخرج الملفات التالية:
   - `Cairo-Regular.ttf`
   - `Cairo-Bold.ttf`
4. ضعها في هذا المجلد (`public/fonts/`)

## أو عبر سطر الأوامر:

```bash
cd public/fonts
curl -o Cairo-Regular.ttf https://github.com/google/fonts/raw/main/ofl/cairo/static/Cairo-Regular.ttf
curl -o Cairo-Bold.ttf https://github.com/google/fonts/raw/main/ofl/cairo/static/Cairo-Bold.ttf
```

## بعد الإضافة:

نفّذ لتنظيف cache الخطوط:

```bash
php artisan view:clear
php artisan cache:clear
rm -rf storage/fonts/*
```

ثم جرّب توليد PDF لأي عقد، Cairo سيظهر تلقائياً.

## ملاحظة:

ملفات الخطوط لم تُضمّن في الحزمة لأسباب:
- حقوق النشر (Cairo SIL Open Font License)
- حجم الحزمة
- يفضّل تحميلها من المصدر الرسمي

الخط مجاني تماماً ومتاح للاستخدام التجاري.
