<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\TextInput;

class MoneyInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->numeric()
            ->type('text')
            ->minValue(0)
            ->maxValue(999999999999.99)
            ->step('any')
            ->inputMode('decimal')
            ->prefix('ج.م')
            ->extraInputAttributes([
                'lang' => 'en',
                'inputmode' => 'decimal',
                'autocomplete' => 'off',
                'dir' => 'ltr',
                'style' => 'text-align: left; font-family: monospace; font-size: 13pt; font-weight: 600; direction: ltr;',
                'data-money-input' => 'true',
            ])
            ->dehydrateStateUsing(fn ($state) => $this->cleanValue($state))
            ->formatStateUsing(function ($state) {
                if ($state === null || $state === '') {
                    return null;
                }

                return number_format((float) $state, 2, '.', '');
            })
            ->rules([
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ])
            ->validationMessages([
                'numeric' => 'يجب أن يكون رقماً صحيحاً',
                'min' => 'يجب أن يكون أكبر من أو يساوي صفر',
                'max' => 'الرقم كبير جداً',
            ]);
    }

    protected function cleanValue(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        $cleaned = preg_replace('/[^\d.\-]/', '', str_replace(',', '', (string) $state));
        if ($cleaned === '' || $cleaned === '-' || $cleaned === '.') {
            return null;
        }

        $float = (float) $cleaned;
        if ($float < 0 || $float > 999999999999.99) {
            return null;
        }

        return number_format($float, 2, '.', '');
    }

    public function currency(string $currency = 'EGP'): static
    {
        $symbols = [
            'EGP' => 'ج.م',
            'USD' => '$',
            'SAR' => 'ر.س',
            'AED' => 'د.إ',
        ];
        $this->prefix($symbols[$currency] ?? $currency);

        return $this;
    }

    public function asReadOnly(): static
    {
        $this
            ->disabled()
            ->dehydrated()
            ->extraInputAttributes([
                'lang' => 'en',
                'dir' => 'ltr',
                'style' => 'text-align: left; font-family: monospace; font-size: 14pt; font-weight: bold; color: #b91c1c; background: #fff5f5; direction: ltr;',
            ]);

        return $this;
    }

    /**
     * يمكن تفعيل قناع العرض لاحقاً؛ تجنبنا Alpine غير المتوافق مع locale هنا.
     */
    public function withSeparator(): static
    {
        return $this;
    }
}
