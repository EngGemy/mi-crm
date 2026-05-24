# 📋 قائمة التحقق للنشر (Deployment Checklist)

## ✅ المرحلة 1: ما قبل التشغيل (التطوير المحلي)

### 🔧 الإعدادات الأساسية

- [ ] PHP 8.2+ مثبّت: `php -v`
- [ ] Composer 2.x مثبّت: `composer -V`
- [ ] MySQL 8.0+ أو MariaDB 10.6+ يعمل: `mysql --version`
- [ ] Node.js 18+ مثبّت: `node -v`
- [ ] Git مثبّت: `git --version`

### 📦 Extensions PHP المطلوبة

```bash
php -m | grep -E "bcmath|ctype|fileinfo|json|mbstring|openssl|pdo|tokenizer|xml|curl|gd|zip|mysql"
```

يجب أن تظهر كلها. لو ناقصة:
```bash
sudo apt install php8.2-bcmath php8.2-mbstring php8.2-xml php8.2-zip php8.2-gd php8.2-curl php8.2-mysql
```

### 🗂️ تحضير المشروع

- [ ] فك ضغط `mi-contracts.zip`
- [ ] `cd mi-contracts`
- [ ] `composer install` (سيستغرق 2-5 دقائق)
- [ ] `cp .env.example .env`
- [ ] `php artisan key:generate`

### 💾 إعداد قاعدة البيانات

- [ ] إنشاء قاعدة البيانات:
  ```sql
  CREATE DATABASE mi_contracts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'mi_user'@'localhost' IDENTIFIED BY 'كلمة_مرور_قوية';
  GRANT ALL PRIVILEGES ON mi_contracts.* TO 'mi_user'@'localhost';
  FLUSH PRIVILEGES;
  ```

- [ ] تعديل `.env`:
  ```
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=mi_contracts
  DB_USERNAME=mi_user
  DB_PASSWORD=كلمة_المرور
  ```

- [ ] تشغيل migrations + seed:
  ```bash
  php artisan migrate --seed
  ```

### 🔠 خطوط Cairo (إلزامي للـ PDF)

- [ ] تحميل Cairo Regular:
  ```bash
  curl -L -o public/fonts/Cairo-Regular.ttf https://github.com/google/fonts/raw/main/ofl/cairo/static/Cairo-Regular.ttf
  ```

- [ ] تحميل Cairo Bold:
  ```bash
  curl -L -o public/fonts/Cairo-Bold.ttf https://github.com/google/fonts/raw/main/ofl/cairo/static/Cairo-Bold.ttf
  ```

- [ ] التحقق:
  ```bash
  ls -la public/fonts/*.ttf
  ```

### 🚀 تشغيل التطبيق

- [ ] `php artisan storage:link`
- [ ] `php artisan serve`
- [ ] فتح `http://localhost:8000/admin`
- [ ] تسجيل الدخول:
  - البريد: `admin@mi-cnc.com`
  - كلمة المرور: `password`
- [ ] **تغيير كلمة المرور فوراً** من ملف Profile

---

## ✅ المرحلة 2: اختبار النظام

### 👥 اختبار العملاء
- [ ] فتح `/admin/customers` — يظهر العميل أحمد نزار
- [ ] إضافة عميل جديد — يجب أن يتولّد له كود `CUST-00002`
- [ ] تعديل بيانات عميل
- [ ] حذف عميل تجريبي

### 📋 اختبار البنود
- [ ] فتح `/admin/contract-clauses` — تظهر 15+ بند
- [ ] إنشاء بند جديد بمتغيرات `{{TEST}}`
- [ ] تجربة Tab "المتغيرات الديناميكية"
- [ ] تجربة Tab "جدول البند (BOQ)"

### 📑 اختبار العقود
- [ ] فتح `/admin/contracts` — يظهر عقد PRJ-AN-2026
- [ ] الضغط على "تحميل PDF" — يُحمّل الملف ✅
- [ ] فتح PDF والتحقق من:
  - ظهور الخط Cairo بشكل صحيح
  - عرض كل البيانات
  - جدول الدفعات (70/25/5)
  - التوقيعات في الأسفل

### 💰 اختبار الدفعات
- [ ] فتح `/admin/payments` — تظهر 3 دفعات للعقد
- [ ] تسجيل دفعة كمدفوعة → الحالة تتحدّث تلقائياً
- [ ] تجربة فلتر "متأخرة"

### 📊 اختبار Dashboard
- [ ] فتح `/admin` — تظهر KPIs
- [ ] التحقق من جدول "أحدث العقود"
- [ ] التحقق من جدول "دفعات تحتاج متابعة"

---

## ✅ المرحلة 3: النشر للإنتاج (Production)

### 🌐 خيار 1: Laravel Forge + DigitalOcean (مُوصى به)

**التكلفة الشهرية: ~$18**
- DigitalOcean VPS: $6/شهر (1GB RAM)
- Laravel Forge: $12/شهر

**الخطوات:**

- [ ] إنشاء حساب على https://forge.laravel.com
- [ ] إنشاء حساب على https://digitalocean.com
- [ ] في Forge: Add Server → DigitalOcean → اختر منطقة قريبة (Frankfurt للشرق الأوسط)
- [ ] انتظار 5-10 دقائق حتى ينشئ السيرفر
- [ ] في Forge: New Site → اربط بـ GitHub Repo
- [ ] Deploy
- [ ] إعداد SSL مجاني (Let's Encrypt) — تلقائي
- [ ] إعداد Cron للمهام المجدولة:
  ```
  * * * * * cd /home/forge/contracts.mi-cnc.com && php artisan schedule:run >> /dev/null 2>&1
  ```

### 🌐 خيار 2: Cloudways (الأسهل)

**التكلفة: ~$11/شهر**

- [ ] إنشاء حساب على https://cloudways.com
- [ ] Launch Server → DigitalOcean → 1GB
- [ ] PHP 8.2 Stack
- [ ] فتح SSH وارفع الكود
- [ ] إعداد domain
- [ ] تفعيل SSL (مجاني)

### 🌐 خيار 3: VPS يدوي (للمتقدمين)

**التكلفة: $6/شهر**

استخدم الدليل في `README.md` قسم "النشر على سيرفر إنتاج".

---

## ✅ المرحلة 4: ما بعد النشر

### 🔒 الأمان

- [ ] HTTPS مفعّل (شهادة SSL)
- [ ] تغيير كلمة مرور admin@mi-cnc.com لكلمة قوية
- [ ] إنشاء حسابات للموظفين بصلاحيات محدودة
- [ ] في `.env` تأكد من:
  ```
  APP_ENV=production
  APP_DEBUG=false
  ```
- [ ] تعطيل عرض ملفات `.env` و `.git` في Nginx
- [ ] تثبيت `fail2ban` لحماية SSH
- [ ] تفعيل Two-Factor Auth (2FA) في Filament

### 💾 النسخ الاحتياطية

- [ ] إعداد backup يومي لقاعدة البيانات:
  ```bash
  # في crontab
  0 2 * * * mysqldump -u mi_user -pPASSWORD mi_contracts | gzip > /backups/db-$(date +\%Y\%m\%d).sql.gz
  ```

- [ ] إعداد backup أسبوعي للملفات (storage/):
  ```bash
  0 3 * * 0 tar -czf /backups/files-$(date +\%Y\%m\%d).tar.gz /var/www/mi-contracts/storage
  ```

- [ ] إعداد رفع البيانات على S3 / Spaces (احتياطي خارجي):
  ```bash
  aws s3 sync /backups s3://mi-backups/
  ```

- [ ] **اختبر استرجاع البيانات** فعلياً قبل الاعتماد على النسخ

### 📊 المراقبة

- [ ] تثبيت Sentry لتتبع الأخطاء (مجاني للبداية):
  ```bash
  composer require sentry/sentry-laravel
  ```

- [ ] تفعيل Laravel Telescope في بيئة الـ staging فقط
- [ ] إعداد UptimeRobot لمراقبة توافر النظام (مجاني)

### 📈 تحسين الأداء

- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan optimize`
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] تفعيل OPcache في PHP
- [ ] تفعيل Redis للـ cache و sessions (في الإنتاج)

### 📚 التدريب

- [ ] جلسة تدريب لفريق المبيعات (1-2 ساعة)
- [ ] جلسة تدريب لفريق المحاسبة (1-2 ساعة)
- [ ] جلسة تدريب للإدارة على Dashboard (30 دقيقة)
- [ ] إنشاء دليل استخدام داخلي (Word/PDF)
- [ ] فيديوهات تعليمية قصيرة لكل مهمة شائعة

---

## ✅ المرحلة 5: التحسينات المستقبلية (Phase 2)

### 🔔 الإشعارات (طلبت لاحقاً)

- [ ] **Email**: تثبيت Resend
  ```bash
  composer require resend/resend-php
  ```
- [ ] **WhatsApp**: تثبيت Twilio
  ```bash
  composer require twilio/sdk
  ```
- [ ] **SMS**: نفس Twilio
- [ ] أنواع الإشعارات:
  - دفعة مستحقة بعد 7 أيام (Email + WhatsApp)
  - دفعة متأخرة (Email + SMS فوري)
  - بدء التصنيع (Email داخلي)
  - اقتراب التسليم (Email + WhatsApp قبل 10 أيام)
  - ضمان قارب على الانتهاء (Email تسويقي)

### 🌍 إضافات مستقبلية مقترحة

- [ ] تكامل مع البنك (تأكيد التحويلات تلقائياً)
- [ ] تكامل مع Google Calendar (مواعيد التسليم)
- [ ] PDF بصيغة Word أيضاً (phpoffice/phpword)
- [ ] Mobile App (Flutter / React Native)
- [ ] صلاحيات تفصيلية (Spatie Permissions)
- [ ] Multi-language switcher (عربي/إنجليزي)
- [ ] تكامل مع SAP / Odoo
- [ ] Reports PDF تلقائية شهرية
- [ ] Real-time notifications (Laravel Reverb)

---

## 🆘 استكشاف الأخطاء (Troubleshooting)

### المشكلة: PDF يخرج بدون نص عربي
**الحل**: تأكد من تحميل خطوط Cairo في `public/fonts/`

### المشكلة: `Class not found` بعد التحديث
**الحل**: 
```bash
composer dump-autoload
php artisan config:clear
```

### المشكلة: Permission denied على storage/
**الحل**:
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### المشكلة: 500 Internal Server Error
**الحل**: راجع `storage/logs/laravel.log`

### المشكلة: قاعدة البيانات غير متصلة
**الحل**: تأكد من `.env` ثم:
```bash
php artisan config:clear
php artisan migrate:status
```

---

**✅ إذا تمت كل الخطوات بنجاح، النظام جاهز للعمل في الإنتاج!**
