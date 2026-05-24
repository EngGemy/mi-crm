# 🏗️ معمارية النظام (Architecture)

## نظرة عامة

النظام مبني على معمارية **Laravel 11 + Filament 3** بأسلوب Clean Architecture:

```
┌──────────────────────────────────────────────────────────┐
│                  Presentation Layer                       │
│  ┌────────────────┐  ┌────────────────┐                 │
│  │   Filament     │  │   Blade Views  │                 │
│  │  Admin Panel   │  │   (PDF Tmpl.)  │                 │
│  └────────────────┘  └────────────────┘                 │
└──────────────────────────────────────────────────────────┘
                           │
┌──────────────────────────▼───────────────────────────────┐
│                   Business Logic Layer                    │
│  ┌──────────────────┐  ┌─────────────────┐              │
│  │  Services        │  │   Observers     │              │
│  │  - Renderer      │  │  - Audit Trail  │              │
│  │  - Generator     │  │                 │              │
│  │  - Scheduler     │  │                 │              │
│  └──────────────────┘  └─────────────────┘              │
└──────────────────────────────────────────────────────────┘
                           │
┌──────────────────────────▼───────────────────────────────┐
│                    Data Layer                             │
│  ┌──────────────────┐  ┌─────────────────┐              │
│  │  Eloquent Models │  │   Migrations    │              │
│  │  + Relationships │  │   (10 tables)   │              │
│  └──────────────────┘  └─────────────────┘              │
└──────────────────────────────────────────────────────────┘
                           │
┌──────────────────────────▼───────────────────────────────┐
│                   MySQL Database                          │
└──────────────────────────────────────────────────────────┘
```

---

## 🗄️ Database Schema

### العلاقات الرئيسية:

```
Customer (1) ──┐
               ├──> Contract (M)
ContractType (1) ┘   │
                     │
                     ├──> ContractItem (M)         [BOQ]
                     │
                     ├──> ContractClauseAttachment (M) ──> ContractClause (1)
                     │
                     ├──> ContractMilestone (M)
                     │      │
                     │      └──> Payment (M)
                     │
                     └──> ChangeLog (Polymorphic) [Audit]
```

### الجداول العشرة:

| الجدول | الوصف | السطر التقريبي |
|--------|-------|----------------|
| `customers` | العملاء | عميل واحد لكل صف |
| `products` | كتالوج المنتجات | منتج واحد |
| `contract_types` | أنواع العقود (تسمين/بياض/صيانة...) | 6 أنواع |
| `contract_clauses` | **مكتبة البنود** ⭐ | 15+ بند |
| `contracts` | العقود الرئيسية | عقد واحد |
| `contract_clause_attachments` | **ربط بنود ↔ عقود** ⭐ | بند مُخصّص لعقد |
| `contract_items` | بنود BOQ التفصيلية | بند واحد |
| `contract_milestones` | المراحل (توقيع/شحن/تسليم) | مرحلة واحدة |
| `payments` | الدفعات (مرتبطة بمراحل) | دفعة واحدة |
| `change_logs` | سجل التغييرات (Polymorphic) | تغيير واحد |

---

## 🧩 المكونات الذكية (Smart Components)

### 1. ClauseRenderer (معالج المتغيرات) ⭐

**المسؤولية**: استبدال المتغيرات `{{VAR}}` و الجداول `[[ITEMS_TABLE]]`

```php
// مثال: بند الإنشاءات
$content = "تكلفة هذا البند: {{CONSTRUCTION_COST}} {{CURRENCY}}";

// المتغيرات
$vars = ['CONSTRUCTION_COST' => '4,800,000', 'CURRENCY' => 'EGP'];

// النتيجة
$rendered = "تكلفة هذا البند: 4,800,000 EGP";
```

**المتغيرات المدعومة:**
- متغيرات العقد العامة (30+ متغير): `{{CUSTOMER_NAME}}`, `{{TOTAL_VALUE}}`, `{{CONTRACT_DATE}}`...
- متغيرات البند الخاصة (يعرّفها Admin): `{{WARRANTY_YEARS}}`, `{{TRAINING_DAYS}}`...
- جداول داخلية: `[[ITEMS_TABLE]]` يستبدل بجدول HTML

**ميزة استثنائية**: يحوّل الأرقام إلى كتابة عربية رسمية تلقائياً للعقود القانونية:
```
5,500,000 → "خمسة ملايين وخمسمائة ألف جنيه مصري لا غير"
```

---

### 2. ContractGenerator (مولّد العقود)

**المسؤولية**: تحويل العقد إلى PDF احترافي

**الـ Pipeline:**
```
Contract Model
    ↓ (يحمّل العلاقات)
ClauseRenderer (يعالج كل بند)
    ↓
Blade Template (template.blade.php)
    ↓
DomPDF + Cairo Font
    ↓
PDF File
```

**الإخراج المتاح:**
- `downloadPdf()` — تحميل مباشر
- `streamPdf()` — عرض في المتصفح
- `renderHtml()` — HTML خام (للتعديل)

---

### 3. PaymentScheduler (جدولة الدفعات الذكية)

**المسؤولية**: إنشاء جدول الدفعات تلقائياً عند توقيع العقد

**الفكرة الأساسية**: الدفعات مرتبطة بـ **Milestones** وليس **تواريخ ثابتة**

#### المراحل القياسية (6 مراحل):

| المرحلة | الإزاحة | يفعّل دفعة؟ |
|---------|---------|-------------|
| `CONTRACT_SIGN` | يوم 0 (التعاقد) | ✅ 70% |
| `MANUFACTURING_START` | بعد 3 أيام | - |
| `SHIPPING_START` | 67% من فترة التصنيع | ✅ 25% |
| `INSTALLATION_START` | 75% من فترة التصنيع | - |
| `TESTING` | 95% من فترة التصنيع | - |
| `FINAL_DELIVERY` | 100% (التسليم) | ✅ 5% |

**ميزة**: لو فترة التصنيع 105 يوم، الدفعة الثانية في يوم 70 (وليس يوم 30 كما كان في عقد أحمد نزار).

---

### 4. ContractObserver (Audit Trail تلقائي)

**المسؤولية**: تسجيل كل تعديل على العقد

```php
Contract::observe(ContractObserver::class);

// تلقائياً:
- created → يسجّل في change_logs
- updated → يحفظ القيم القديمة والجديدة
- deleted → يحفظ آخر حالة قبل الحذف
- restored → يسجّل الاسترجاع
```

**المعلومات المحفوظة:**
- المستخدم (`user_id`, `user_name`)
- النوع (`event`: created/updated/signed/...)
- القيم (`old_values`, `new_values`)
- الـ IP و User Agent
- الحقول المتغيرة فقط

---

## 🎨 طبقة العرض (UI Layer)

### Filament Admin Panel

#### Resources الستة:

| Resource | الغرض | الميزة الخاصة |
|----------|-------|---------------|
| `CustomerResource` | إدارة العملاء | تكامل مع 20 جنسية |
| `ProductResource` | كتالوج المنتجات | 16 فئة منتج |
| `ContractTypeResource` | أنواع العقود | جدول دفعات مخصّص |
| `ContractClauseResource` ⭐ | **مكتبة البنود** | محرر متغيرات + جداول BOQ |
| `ContractResource` ⭐ | **محرر العقود** | Wizard 5 خطوات + بنود ديناميكية |
| `PaymentResource` | الدفعات | فلاتر للمتأخرة + المستحقة |

#### Widgets الثلاثة (Dashboard):

| Widget | المحتوى |
|--------|---------|
| `StatsOverview` | 6 KPIs (قيمة، تحصيل، متأخر، تسليمات...) |
| `LatestContracts` | آخر 10 عقود |
| `OverduePayments` | دفعات تحتاج متابعة (متأخرة + مستحقة قريباً) |

---

## 🔐 الأمان

### الطبقات الموجودة:

1. **Authentication**: Filament Auth (sessions)
2. **Authorization**: 5 أدوار (admin/sales/production/accounting/viewer)
3. **CSRF Protection**: على كل POST requests
4. **SQL Injection**: محمي بـ Eloquent ORM
5. **XSS Protection**: Blade auto-escape
6. **Soft Deletes**: للعملاء والعقود والبنود
7. **Audit Trail**: لكل التعديلات

### يحتاج إضافة (Phase 2):
- 2FA (Two-Factor Authentication)
- Rate Limiting
- IP Whitelist للـ admin
- Session timeout بعد عدم نشاط

---

## ⚡ تحسينات الأداء

### المُطبَّق:
- Eager Loading (`loadMissing`) لتجنّب N+1
- Database Indexes على الحقول المهمة (status, dates, foreign keys)
- Computed attributes تُحسب عند الحاجة فقط
- Pagination تلقائي في Filament Tables

### للـ Production:
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- `composer install --optimize-autoloader --no-dev`
- Redis للـ cache و sessions
- OPcache للـ PHP
- CDN للـ static assets

---

## 📊 إحصائيات المشروع

```
الملفات الإجمالية: 80+
أسطر الكود: 5,000+
- PHP Classes: 35+
- Migrations: 10
- Filament Resources: 6
- Services: 3
- Models: 11
- Pages: 14
- Widgets: 3
- Blade Templates: 1 (احترافي)
- Documentation: 4 ملفات
```

---

## 🔄 Workflow كامل لإنشاء عقد

```
1. Admin يفتح "العقود" → "+ عقد جديد"
   ↓
2. الخطوة 1: يختار عميل (أو ينشئ جديد) + نوع العقد
   ↓
3. الخطوة 2: يدخل التكاليف (الإجمالي يحسب live)
   ↓
4. الخطوة 3: يحدد مدة التصنيع (التسليم يحسب تلقائياً)
   ↓
5. الخطوة 4: يختار البنود من المكتبة:
   - يضيف "بند الإنشاءات" → يدخل قيم المتغيرات → يدخل بنود الجدول
   - يضيف "بند الكهرباء" → نفس الشيء
   - يضيف "بند الضمان" → 12 سنة، 12 شهر
   - ... إلخ
   ↓
6. الخطوة 5: ملاحظات نهائية + حفظ
   ↓
7. النظام تلقائياً:
   ✓ يولّد رقم العقد (CTR-2026-0001)
   ✓ يولّد كود المشروع (PRJ-2026-0001)
   ✓ يحسب الإجمالي والضرائب
   ✓ ينشئ 6 Milestones
   ✓ ينشئ 3 Payments (70/25/5)
   ✓ يسجّل في Change Log
   ↓
8. Admin يضغط "تحميل PDF" → عقد جاهز للتوقيع
   ↓
9. عند توقيع العقد، تتم تحديث الحالة → status = 'signed'
   ↓
10. مع تحصيل الدفعات → payment_status يتحدّث تلقائياً
```

---

## 🌟 ما يميّز هذا النظام

1. **عقود ديناميكية فعلية** — مش templates ثابتة
2. **مكتبة بنود قابلة للتوسع** — أضف أي بند بدون كود
3. **Milestones بدلاً من تواريخ ثابتة** — يحل مشكلة عقود MI القديمة
4. **PDF احترافي بـ Cairo** — تصميم بمستوى الشركات الكبرى
5. **Audit Trail كامل** — مهم للمحاسبة والمراجعات
6. **Wizard متعدد الخطوات** — تجربة استخدام سلسة
7. **Dashboard حقيقي بـ KPIs** — رؤية واضحة للإدارة
8. **مرن للتوسع** — جاهز لإضافة الإشعارات (Phase 2)

---

**تم بناؤه بمعمارية إنتاج حقيقية، ليس Demo.**
