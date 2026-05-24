# Manual Acceptance Tests — Poultry House Auto-Pricing

## Setup

1. Run migrations: `php artisan migrate`
2. Seed settings: `php artisan db:seed --class=Database\Seeders\PoultryPricingSettingsSeeder`
3. Run automated tests: `php artisan test --filter=PoultryHousePricing`

> ⚠️ **Important**: The default prices in `PoultryPricingSettingsSeeder` are placeholders. Confirm with management before going live.

---

## TC-01: Golden Case — Standard House 81×12×3.5

**Inputs:**
- hall_length = 81, hall_width = 12, hall_height = 3.5
- tiers = 4, lines = 4
- side_fans_count = 8, heaters_count = 2

**Expected Derived Values:**
| Metric | Formula | Expected |
|--------|---------|----------|
| effective_length | 81 − 6 | **75** |
| bird_count | 75 × 2 × 4 × 4 × 16 | **38,400** |
| back_fans_count | ceil(38,400 × 2.1 / 5,000) | **17** |
| cooling_units | 17 × 5.5 | **93.5** |
| windows_count | 81 − 4 | **77** |
| concrete_area | 81 × 12 | **972 m²** |
| steel_area | 81 × 12 | **972 m²** |
| walls_area | 81 × 3.5 × 2 | **567 m²** |

**Verification Steps:**
1. Open Filament admin → Quotations → Create
2. Fill step 2 (تفاصيل المشروع) with dimensions above
3. Go to step 4 (التسعير) → click "احسب البنود تلقائياً"
4. Verify the preview placeholders show: 38,400 birds, 17 back fans, 93.5 cooling, 77 windows
5. Verify 11 items appear in the repeater
6. Save → verify `bird_capacity = 38400` in DB

✅ Pass if all match exactly.

---

## TC-02: Small House 40×10×3

**Inputs:**
- hall_length = 40, hall_width = 10, hall_height = 3
- tiers = 3, lines = 3
- side_fans_count = 6, heaters_count = 2

**Hand Calculation:**
- effective_length = 40 − 6 = **34**
- bird_count = 34 × 2 × 3 × 3 × 16 = **9,792**
- back_fans = ceil(9,792 × 2.1 / 5,000) = ceil(4.11264) = **5**
- cooling_units = 5 × 5.5 = **27.5**
- windows_count = 40 − 4 = **36**
- concrete_area = 40 × 10 = **400 m²**
- walls_area = 40 × 3 × 2 = **240 m²**

✅ Pass if computed values match above.

---

## TC-03: Large House 120×15×4

**Inputs:**
- hall_length = 120, hall_width = 15, hall_height = 4
- tiers = 5, lines = 5
- side_fans_count = 10, heaters_count = 6

**Hand Calculation:**
- effective_length = 120 − 6 = **114**
- bird_count = 114 × 2 × 5 × 5 × 16 = **91,200**
- back_fans = ceil(91,200 × 2.1 / 5,000) = ceil(38.304) = **39**
- cooling_units = 39 × 5.5 = **214.5**
- windows_count = 120 − 4 = **116**
- concrete_area = 120 × 15 = **1,800 m²**
- walls_area = 120 × 4 × 2 = **960 m²**

✅ Pass if computed values match above.

---

## TC-04: Edge — Minimum Valid Length

**Inputs:** hall_length = 7, dead_zone = 6

**Expected:** effective_length = 1, no exception thrown.

**Steps:**
1. Create quotation with length = 7, width = 10, height = 3
2. Click auto-price
3. Should succeed with bird_count = 1 × 2 × tiers × lines × 16

✅ Pass if calculation succeeds.

---

## TC-05: Edge — Length Equals Dead Zone (Must Throw)

**Inputs:** hall_length = 6, dead_zone = 6

**Expected:** Error message "طول العنبر (6م) أقل من أو يساوي المنطقة الميتة (6م)"

✅ Pass if auto-price button shows error notification.

---

## TC-06: VAT 14% Applied to TC-01 Subtotal

**Steps:**
1. Run TC-01 auto-pricing
2. Set VAT percentage to 14 in step 4
3. Save

**Expected:**
- `vat_amount` = subtotal × 0.14
- `total_amount` = subtotal + vat_amount

**Hand check:** If subtotal = 5,000,000, then VAT = 700,000, total = 5,700,000.

✅ Pass if totals match calculator.

---

## TC-07: Snapshot Integrity After Price Change

**Steps:**
1. Create and auto-price quotation with TC-01 inputs
2. Note the subtotal (e.g., S1)
3. Go to Settings → Poultry Pricing → change `price_per_bird` from 95 to 110
4. Create NEW quotation with same inputs
5. New subtotal should be higher (S2 > S1)
6. Open first quotation → call `recomputeFromSnapshot()`
7. Subtotal should still equal S1

✅ Pass if old quote reproduces original totals.

---

## TC-08: Generate Image and Verify Arabic Rendering

**Steps:**
1. Open any quoted quotation in edit mode
2. Click "عرض كصورة" in header actions
3. Open the generated PNG

**Expected:**
- Arabic text renders correctly (no □□□ boxes)
- Dimensions: approximately 1240×1754 (A4 @ 150 DPI)
- Company header visible
- Itemized table with all 11 items
- Watermark visible
- Sales manager name appears in footer

✅ Pass if image is readable and professional.

---

## TC-09: WhatsApp Share Button

**Steps:**
1. Open quotation edit page
2. Click "شارك واتساب"
3. WhatsApp web opens with pre-filled message

**Expected:** Message contains quotation number, customer name, total amount, and public preview URL.

✅ Pass if link opens with correct text.

---

## TC-10: Settings Editable from Admin

**Steps:**
1. Go to Settings → تسعير عنابر الدواجن
2. Change `concrete_cost_per_m2` from 850 to 900
3. Create new quotation → auto-price

**Expected:** Concrete line item reflects new price.

✅ Pass if new price is used immediately.

---

## Calculation Verification Table

Use these to verify with a hand calculator:

| Case | effective_length | bird_count | back_fans | cooling | windows |
|------|-----------------|------------|-----------|---------|---------|
| TC-01 (81×12×3.5, 4×4) | 75 | 38,400 | 17 | 93.5 | 77 |
| TC-02 (40×10×3, 3×3) | 34 | 9,792 | 5 | 27.5 | 36 |
| TC-03 (120×15×4, 5×5) | 114 | 91,200 | 39 | 214.5 | 116 |

---

## Automated Test Commands

```bash
# Run all poultry pricing tests
php artisan test --filter=PoultryHousePricing

# Run feature flow tests
php artisan test --filter=PoultryAutoPricingFlow

# Run full suite (must remain green)
php artisan test
```
