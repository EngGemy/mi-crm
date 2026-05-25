<?php

namespace App\Filament\Pages;

use App\Services\Poultry\PoultryConfigLoader;
use App\Services\Poultry\PoultryTechnicalCalculator;
use App\Support\TaxResolver;
use Filament\Pages\Page;

class SalesTraining extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'الأدوات';

    protected static ?string $navigationLabel = 'دليل التدريب';

    protected static ?string $title = 'دليل التدريب — مندوب المبيعات';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.sales-training';

    public array $example = [];

    public array $pricingParams = [];

    public float $vatRate = 14;

    // Quiz state
    public array $quizAnswers = [];

    public bool $quizSubmitted = false;

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        $configLoader = new PoultryConfigLoader;
        $calculator = new PoultryTechnicalCalculator;
        $techConfig = $configLoader->loadTechnicalConfig();
        $pricingParams = $configLoader->loadPricingParams();

        // مثال قياسي: عنبر 80م × 12م، 3 أدوار، وزن طائر 2.1 كجم
        $barnLength = 80;
        $hallWidth = 12;
        $serviceLength = (float) $techConfig['default_service_length'];
        $tiers = 3;
        $birdWeight = 2.1;

        $lines = $calculator->resolveLinesFromWidth($hallWidth, $techConfig);
        $birdsPerNest = $calculator->birdsPerNestFromWeight($birdWeight, $techConfig);
        $effectiveLength = $barnLength - $serviceLength;
        $nestsPerLine = (int) ($effectiveLength * 2 * $tiers);
        $totalNests = $nestsPerLine * $lines;
        $totalBirds = $totalNests * $birdsPerNest;

        $fanCapacity = (float) $techConfig['fan_capacity_kg'];
        $fanResult = $calculator->exhaustFansFromBirds($totalBirds, $birdWeight, $fanCapacity);
        $mainFans = $fanResult['fans_count'];
        $coolingPerFan = (float) $techConfig['cooling_pad_meters_per_fan'];
        $coolingPad = (int) ceil($mainFans * $coolingPerFan);
        $airWindows = $calculator->broilerAirWindows($barnLength);

        // حسابات التسعير
        $pricePerBird = (float) $pricingParams['price_per_bird'];
        $concreteArea = $barnLength * $hallWidth;
        $steelArea = $barnLength * $hallWidth;
        $hallHeight = 3;
        $wallsArea = $barnLength * $hallHeight * 2;
        $batteryTotal = $totalBirds * $pricePerBird;
        $fansTotal = $mainFans * (float) $pricingParams['back_fan_unit_price'];
        $coolingTotal = $coolingPad * (float) $pricingParams['cooling_unit_price'];
        $windowsTotal = $airWindows * (float) $pricingParams['window_unit_price'];
        $concreteTotal = $concreteArea * (float) $pricingParams['concrete_cost_per_m2'];
        $steelTotal = $steelArea * (float) $pricingParams['steel_cost_per_m2'];
        $wallsTotal = $wallsArea * (float) $pricingParams['wall_cost_per_m2'];
        $controlTotal = (float) $pricingParams['control_fixed_cost'];
        $tanksTotal = (float) $pricingParams['tanks_fixed_cost'];

        $subtotal = $batteryTotal + $fansTotal + $coolingTotal + $windowsTotal
            + $concreteTotal + $steelTotal + $wallsTotal + $controlTotal + $tanksTotal;

        $vatRate = TaxResolver::percentageFor();
        $vatAmount = $subtotal * ($vatRate / 100);
        $total = $subtotal + $vatAmount;

        $this->example = [
            'barn_length' => $barnLength,
            'hall_width' => $hallWidth,
            'hall_height' => $hallHeight,
            'service_length' => $serviceLength,
            'tiers' => $tiers,
            'bird_weight' => $birdWeight,
            'lines' => $lines,
            'birds_per_nest' => $birdsPerNest,
            'effective_length' => $effectiveLength,
            'nests_per_line' => $nestsPerLine,
            'total_nests' => $totalNests,
            'total_birds' => $totalBirds,
            'fan_capacity' => $fanCapacity,
            'main_fans' => $mainFans,
            'fan_load_kg' => $fanResult['fan_load_kg'],
            'fan_formula' => $fanResult['formula'],
            'cooling_per_fan' => $coolingPerFan,
            'cooling_pad' => $coolingPad,
            'air_windows' => $airWindows,
            'battery_total' => $batteryTotal,
            'fans_total' => $fansTotal,
            'cooling_total' => $coolingTotal,
            'windows_total' => $windowsTotal,
            'concrete_area' => $concreteArea,
            'concrete_total' => $concreteTotal,
            'steel_area' => $steelArea,
            'steel_total' => $steelTotal,
            'walls_area' => $wallsArea,
            'walls_total' => $wallsTotal,
            'control_total' => $controlTotal,
            'tanks_total' => $tanksTotal,
            'subtotal' => $subtotal,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'total' => $total,
        ];

        $this->pricingParams = $pricingParams;
        $this->vatRate = $vatRate;
    }

    public function submitQuiz(): void
    {
        $this->quizSubmitted = true;
    }

    public function resetQuiz(): void
    {
        $this->quizAnswers = [];
        $this->quizSubmitted = false;
    }

    /** حساب نقاط الاختبار */
    public function quizScore(): int
    {
        $correct = [
            'q1' => (string) $this->example['effective_length'],
            'q2' => (string) $this->example['lines'],
            'q3' => (string) $this->example['birds_per_nest'],
            'q4' => (string) $this->example['total_nests'],
            'q5' => (string) $this->example['main_fans'],
        ];

        $score = 0;
        foreach ($correct as $key => $answer) {
            if (($this->quizAnswers[$key] ?? '') === $answer) {
                $score++;
            }
        }

        return $score;
    }
}
