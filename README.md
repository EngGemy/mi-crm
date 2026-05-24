# MI Metal Industries — نظام إدارة العقود

> Production-Ready Laravel + Filament Application

نظام احترافي متكامل لإدارة العقود والعملاء والدفعات لشركة MI Metal Industries — متخصص في عقود تصنيع وتركيب بطاريات الدواجن الأوتوماتيكية.

---

## ⭐ المميزات الرئيسية

### 📋 إدارة عقود ديناميكية
- **محرر بنود مرن**: مكتبة بنود اختيارية يمكن إضافتها/إزالتها لكل عقد
- **متغيرات ديناميكية**: كل بند يدعم متغيرات `{{VAR_NAME}}` قابلة للتخصيص
- **جداول BOQ مدمجة**: لبنود الإنشاءات/الكهرباء/السباكة
- **6 أنواع عقود**: تسمين، بياض، إنشاءات، بطاريات، صيانة، قطع غيار
- **15+ بند جاهز**: ديباجة، مالي، فني، إنشاءات، كهرباء، سباكة، ضمان...

### 💰 إدارة الدفعات الذكية
- **Milestones** بدلاً من تواريخ ثابتة (يحل مشكلة عقود MI القديمة)
- جدولة تلقائية: 70% توقيع / 25% شحن / 5% تسليم
- تنبيهات تلقائية للدفعات المتأخرة
- تتبع نسبة التحصيل

### 🎨 Admin Panel احترافي
- بناءً على Filament v3 (مفتوح المصدر، مجاني)
- واجهة عربية كاملة RTL
- خط Cairo احترافي
- لوحة معلومات Dashboard مع KPIs
- قابل للوصول من الموبايل

### 📄 توليد PDF تلقائي
- Cairo font للنصوص العربية
- تصميم احترافي بألوان MI
- قالب Blade قابل للتعديل
- معاينة مباشرة + تحميل
- يدعم أرقام بالحروف العربية تلقائياً

### 🔒 Audit Trail كامل
- تسجيل كل تعديل تلقائياً
- يحفظ من قام بالتعديل + IP + User Agent
- مهم للمراجعات المحاسبية

---

## 🚀 التثبيت السريع

### المتطلبات

| المتطلب | الإصدار |
|---------|---------|
| PHP | 8.2+ |
| Composer | 2.x |
| MySQL / MariaDB | 8.0+ / 10.6+ |
| Node.js (للبناء) | 18+ |
| LibreOffice (لتوليد Word) | اختياري |

### خطوات التثبيت

```bash
# 1. فك ضغط الحزمة
unzip mi-contracts.zip
cd mi-contracts

# 2. تثبيت dependencies
composer install
npm install && npm run build

# 3. إعداد ملف البيئة
cp .env.example .env
php artisan key:generate

# 4. عدّل .env بإعدادات قاعدة البيانات
nano .env
# DB_DATABASE=mi_contracts
# DB_USERNAME=root
# DB_PASSWORD=

# 5. إنشاء قاعدة البيانات
mysql -u root -p -e "CREATE DATABASE mi_contracts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. تشغيل الـ migrations + seed
php artisan migrate --seed

# 7. تحميل خط Cairo
mkdir -p public/fonts
cd public/fonts
curl -o Cairo-Regular.ttf https://github.com/google/fonts/raw/main/ofl/cairo/static/Cairo-Regular.ttf
curl -o Cairo-Bold.ttf https://github.com/google/fonts/raw/main/ofl/cairo/static/Cairo-Bold.ttf
cd ../..

# 8. ربط storage
php artisan storage:link

# 9. التشغيل
php artisan serve
```

ثم افتح: `http://localhost:8000/admin`

**بيانات الدخول الافتراضية:**
- البريد: `admin@mi-cnc.com`
- كلمة المرور: `password`

⚠️ **غيّر كلمة المرور فوراً بعد التشغيل الأول!**

---

## 🌐 النشر على سيرفر إنتاج

### خيار 1: Laravel Forge (مُوصى به)

1. اشترِ سيرفر VPS من DigitalOcean ($6/شهر)
2. اشترك في Forge ($12/شهر) — https://forge.laravel.com
3. اربط Forge بالـ VPS
4. ارفع الكود على GitHub
5. في Forge: New Site → اربط بالـ Repo → Deploy
6. شهادة SSL مجانية تتفعّل تلقائياً (Let's Encrypt)

**الإجمالي: ~$18/شهر** لسيرفر إنتاج كامل

### خيار 2: Cloudways (أسهل)

- اختر DigitalOcean من Cloudways
- اختر PHP Stack
- ادفع $11/شهر
- ارفع الكود واتبع المعالج

### خيار 3: VPS يدوي

```bash
# على Ubuntu 22.04+
sudo apt update
sudo apt install -y nginx mysql-server php8.2 php8.2-fpm php8.2-mysql \
    php8.2-mbstring php8.2-xml php8.2-zip php8.2-gd php8.2-curl

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# نسخ الملفات
sudo mkdir -p /var/www/mi-contracts
sudo chown -R $USER:www-data /var/www/mi-contracts
cd /var/www/mi-contracts
# ... ارفع الملفات هنا

# إعداد
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# إعداد Nginx
sudo nano /etc/nginx/sites-available/mi-contracts
```

محتوى ملف Nginx:

```nginx
server {
    listen 80;
    server_name contracts.mi-cnc.com;
    root /var/www/mi-contracts/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

ثم:
```bash
sudo ln -s /etc/nginx/sites-available/mi-contracts /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# SSL مع Let's Encrypt
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d contracts.mi-cnc.com
```

---

## 📁 هيكل النظام

```
mi-contracts/
├── app/
│   ├── Filament/
│   │   ├── Resources/      # شاشات الإدارة (Customer, Contract, Clause...)
│   │   └── Widgets/        # KPIs و Tables
│   ├── Http/Controllers/   # Controllers للـ PDF
│   ├── Models/             # Eloquent Models
│   ├── Observers/          # Audit Trail تلقائي
│   ├── Providers/          # Service Providers
│   └── Services/           # Business Logic
│       ├── ClauseRenderer.php       # ⭐ معالج المتغيرات
│       ├── ContractGenerator.php    # ⭐ توليد PDF
│       └── PaymentScheduler.php     # ⭐ جدولة الدفعات
├── database/
│   ├── migrations/         # 10 migrations
│   └── seeders/           # بيانات MI الفعلية
├── resources/
│   └── views/contracts/   # قالب PDF Blade
└── public/
    └── fonts/             # ضع Cairo هنا
```

---

## 📚 المفاهيم الرئيسية

### 1. مكتبة البنود (Contract Clauses Library)

كل بند يحتوي:
- **محتوى**: نص قانوني مع متغيرات `{{VAR_NAME}}`
- **متغيرات**: لكل بند متغيراته (مثل `{{WARRANTY_YEARS}}`)
- **جدول مدمج**: للبنود اللي فيها BOQ (إنشاءات/كهرباء/سباكة)

**مثال على بند الإنشاءات:**

```
يلتزم الطرف الأول بتنفيذ الأعمال الإنشائية...

[[ITEMS_TABLE]]    ← هنا يتم استبدال الجدول

تكلفة هذا البند: {{CONSTRUCTION_TOTAL_COST}} {{CURRENCY}}
ضمان الإنشاءات لمدة {{CONSTRUCTION_WARRANTY_YEARS}} سنة.
```

### 2. الدفعات بالـ Milestones

بدلاً من:
- ❌ "الدفعة الثانية في 24/5/2026" (تاريخ ثابت)

يستخدم:
- ✅ "الدفعة الثانية عند بدء الشحن للموقع" (Milestone)

ده يحل مشكلة عقد أحمد نزار حيث الدفعة الثانية كانت مرتبطة بتاريخ ثابت غير منطقي.

### 3. Audit Trail

كل تعديل على عقد يُسجّل تلقائياً في `change_logs`:
- من قام بالتعديل
- متى
- ماذا تغيّر (قبل وبعد)
- IP و User Agent

---

## 🔧 المهام الإدارية الشائعة

### إضافة بند جديد للمكتبة

```
1. افتح: /admin/contract-clauses
2. اضغط "+ بند جديد"
3. املأ:
   - العنوان والفئة
   - المحتوى مع المتغيرات {{VAR}}
   - عرّف المتغيرات في Tab المتغيرات
   - لو فيه جدول، عرّف الأعمدة في Tab الجدول
4. احفظ
```

### إنشاء عقد جديد

```
1. افتح: /admin/contracts
2. اضغط "+ عقد جديد"
3. اتبع 5 خطوات الـ Wizard:
   - البيانات الأساسية
   - التكاليف
   - الجدول الزمني
   - البنود الاختيارية ⭐
   - الملاحظات
4. احفظ — الدفعات والمراحل تتولّد تلقائياً
5. اضغط "تحميل PDF" لطباعة العقد
```

### تحديث الدفعات

```
1. افتح العقد → "إعادة توليد الدفعات"
2. أو افتح /admin/payments واعدّل يدوياً
3. حالة العقد المالية تتحدّث تلقائياً
```

---

## 🛠️ الصيانة الدورية

### النسخ الاحتياطية

```bash
# يومياً (cron)
0 2 * * * mysqldump -u root mi_contracts | gzip > /backups/mi-$(date +\%Y\%m\%d).sql.gz

# أسبوعياً (الكود)
0 3 * * 0 tar -czf /backups/mi-code-$(date +\%Y\%m\%d).tar.gz /var/www/mi-contracts
```

### التحديثات

```bash
cd /var/www/mi-contracts
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload php8.2-fpm
```

### مراقبة الأداء

```bash
# logs
tail -f storage/logs/laravel.log

# قاعدة البيانات
mysql -u root -p -e "SHOW PROCESSLIST;"

# مساحة القرص
df -h
du -sh /var/www/mi-contracts/storage
```

---

## 📞 الدعم الفني

- **المشروع**: MI Metal Industries — Contract Management System
- **الإصدار**: 1.0.0
- **التقنيات**: Laravel 11 + Filament 3 + MySQL
- **الترخيص**: Proprietary — للاستخدام الداخلي لشركة MI فقط

---

## 🚀 الخطوات التالية (Phase 2)

بعد تشغيل النسخة الحالية، الخطوات المقترحة:

1. **الإشعارات** (طلب لاحق)
   - Email عبر Resend
   - WhatsApp عبر Twilio
   - SMS عبر Twilio
   - تنبيهات للدفعات والتسليمات

2. **التكاملات**
   - ربط مع SAP/Odoo
   - تكامل مع البنوك (تأكيد التحويلات)
   - تكامل مع Google Calendar للمواعيد

3. **التحليلات المتقدمة**
   - Reports PDF شهرية تلقائية
   - مقارنات عقود
   - توقعات التحصيل

4. **الموبايل**
   - PWA Mobile App
   - إشعارات Push

---

**صنع بـ ❤️ لـ MI Metal Industries — 2026**
