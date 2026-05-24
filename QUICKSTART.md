# ⚡ التشغيل السريع (5-10 دقائق)

## 🪟 على Windows + Laragon

```bash
cd D:\laragon\www\mi\laravel_app

# 1️⃣ تثبيت Composer (PHP)
composer install

# 2️⃣ تثبيت npm (Frontend Assets)
npm install
npm run build

# 3️⃣ إعداد البيئة
copy .env.example .env
php artisan key:generate

# 4️⃣ قاعدة البيانات
# - افتح Laragon → Database → HeidiSQL
# - انشئ قاعدة: mi_contracts (utf8mb4_unicode_ci)
# - عدّل .env:
#     DB_DATABASE=mi_contracts
#     DB_USERNAME=root
#     DB_PASSWORD=

php artisan migrate --seed

# 5️⃣ تحميل خط Cairo (إلزامي للـ PDF)
mkdir public\fonts
curl -L -o public\fonts\Cairo-Regular.ttf https://github.com/google/fonts/raw/main/ofl/cairo/static/Cairo-Regular.ttf
curl -L -o public\fonts\Cairo-Bold.ttf https://github.com/google/fonts/raw/main/ofl/cairo/static/Cairo-Bold.ttf

# 6️⃣ ربط Storage
php artisan storage:link

# 7️⃣ التشغيل
php artisan serve
```

🌐 افتح: http://localhost:8000/admin

أو في Laragon (لو ضبطت auto virtual host): http://mi.test/admin

🔐 الدخول:
- البريد: `admin@mi-cnc.com`
- كلمة المرور: `password`

⚠️ **غيّر كلمة المرور فوراً!**

---

## 🐧 على Linux/Mac

```bash
unzip mi-contracts-laravel.zip
cd laravel_app

composer install
npm install
npm run build

cp .env.example .env
php artisan key:generate

mysql -u root -p -e "CREATE DATABASE mi_contracts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# عدّل .env

php artisan migrate --seed

mkdir -p public/fonts
curl -L -o public/fonts/Cairo-Regular.ttf https://github.com/google/fonts/raw/main/ofl/cairo/static/Cairo-Regular.ttf
curl -L -o public/fonts/Cairo-Bold.ttf https://github.com/google/fonts/raw/main/ofl/cairo/static/Cairo-Bold.ttf

php artisan storage:link
php artisan serve
```

---

## 🧪 جرّب فوراً بعد التشغيل

1. اذهب إلى **العقود** → ستجد عقد أحمد نزار جاهز
2. اضغط **تحميل PDF** → سيُولّد العقد كاملاً
3. اذهب إلى **مكتبة البنود** → ستجد 15+ بند جاهز
4. اذهب إلى **Dashboard** → ستجد KPIs مباشرة

---

## ❓ مشاكل شائعة

### `npm install` يفشل بسبب package.json مفقود
✅ **حُلّت**: package.json موجود الآن

### `composer install` بطيء جداً
```bash
composer config -g repo.packagist composer https://packagist.org
```

### خطأ: `Could not find driver` (PDO MySQL)
في Laragon: فعّل extension `php_pdo_mysql` في `php.ini`

### خطأ: `Class 'Filament\Filament' not found`
```bash
composer dump-autoload
php artisan filament:upgrade
```

### الـ PDF يخرج بدون نص عربي
- تأكد من تحميل Cairo في `public/fonts/`
- نظّف cache:
  ```bash
  php artisan view:clear
  php artisan cache:clear
  ```

### الـ migrations تفشل بـ `Specified key was too long`
في `app/Providers/AppServiceProvider.php` أضف داخل `boot()`:
```php
use Illuminate\Support\Facades\Schema;
Schema::defaultStringLength(191);
```

### مشكلة الصلاحيات (Linux)
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 📚 الملفات المهمة

| الملف | الوصف |
|------|-------|
| `README.md` | الدليل الكامل (نشر، صيانة) |
| `DEPLOYMENT_CHECKLIST.md` | قائمة تحقق تفصيلية للنشر |
| `ARCHITECTURE.md` | شرح المعمارية التقنية |
| `.env.example` | قالب إعدادات البيئة |
| `composer.json` | dependencies الـ PHP |
| `package.json` | dependencies الـ Frontend |
