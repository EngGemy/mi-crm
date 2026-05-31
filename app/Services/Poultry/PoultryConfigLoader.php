<?php

namespace App\Services\Poultry;

use App\Support\HeaterOptions;

/**
 * Loads poultry calculator + pricing configuration from settings().
 */
class PoultryConfigLoader
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function resolveTechnicalConfig(array $params = []): array
    {
        $defaults = $this->defaultTechnicalConfig();
        $merged = array_merge($defaults, array_intersect_key($params, $defaults));

        if ($this->hasExplicitTechnicalOverrides($params)) {
            return array_merge($merged, array_intersect_key($params, $defaults));
        }

        return $this->loadTechnicalConfig();
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultTechnicalConfig(): array
    {
        return [
            'default_service_length' => 10,
            'fan_capacity_kg' => 5000,
            'cooling_pad_meters_per_fan' => 5.5,
            'layer_nest_module_m' => 0.60,
            'layer_birds_per_nest' => 10,
            'layer_max_bird_weight_kg' => 1.7,
            'layer_air_windows_formula' => '',
            'broiler_weight_birds_map' => PoultryTechnicalCalculator::DEFAULT_BROILER_WEIGHT_MAP,
            'width_lines_map' => PoultryTechnicalCalculator::DEFAULT_WIDTH_LINES_MAP,
            'side_fan_rules' => $this->defaultSideFanRules(),
            'heater_rules' => $this->defaultHeaterRules(),
            'broiler_height_options' => [3.7, 4.0, 4.5],
            'layer_height_options' => [3.5, 4.0, 4.5],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function loadTechnicalConfig(): array
    {
        return [
            'default_service_length' => (float) settings('poultry_pricing.default_service_length', 10),
            'fan_capacity_kg' => (float) settings('poultry_pricing.fan_capacity_kg', 5000),
            'cooling_pad_meters_per_fan' => (float) settings('poultry_pricing.cooling_pad_meters_per_fan', 5.5),
            'layer_nest_module_m' => (float) settings('poultry_pricing.layer_nest_module_m', 0.60),
            'layer_birds_per_nest' => (int) settings('poultry_pricing.layer_birds_per_nest', 10),
            'layer_max_bird_weight_kg' => (float) settings('poultry_pricing.layer_max_bird_weight_kg', 1.7),
            'layer_air_windows_formula' => settings('poultry_pricing.layer_air_windows_formula', ''),
            'broiler_weight_birds_map' => $this->decodeJsonSetting(
                'poultry_pricing.broiler_weight_birds_map',
                PoultryTechnicalCalculator::DEFAULT_BROILER_WEIGHT_MAP
            ),
            'width_lines_map' => $this->decodeJsonSetting(
                'poultry_pricing.width_lines_map',
                PoultryTechnicalCalculator::DEFAULT_WIDTH_LINES_MAP
            ),
            'side_fan_rules' => $this->decodeJsonSetting('poultry_pricing.side_fan_rules', $this->defaultSideFanRules()),
            'heater_rules' => $this->decodeJsonSetting('poultry_pricing.heater_rules', $this->defaultHeaterRules()),
            'broiler_height_options' => $this->decodeJsonSetting('poultry_pricing.broiler_height_options', [3.7, 4.0, 4.5]),
            'layer_height_options' => $this->decodeJsonSetting('poultry_pricing.layer_height_options', [3.5, 4.0, 4.5]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function loadPricingParams(?string $wallType = null): array
    {
        $wallRates = $this->decodeJsonSetting('poultry_pricing.wall_type_rates', [
            'sandwich' => 1200,
            'cement' => 2000,
        ]);

        $wallKey = $wallType ?? settings('poultry_pricing.default_wall_type', 'sandwich');
        $wallRate = (float) ($wallRates[$wallKey] ?? settings('poultry_pricing.wall_cost_per_m2', 650));

        return [
            'concrete_cost_per_m2' => (float) settings('poultry_pricing.concrete_cost_per_m2', 1200),
            'steel_cost_per_m2' => (float) settings('poultry_pricing.steel_cost_per_m2', 2520),
            'wall_cost_per_m2' => $wallRate,
            'wall_type' => $wallKey,
            'tanks_fixed_cost' => (float) settings('poultry_pricing.tanks_fixed_cost', 400000),
            'price_per_bird' => (float) settings('poultry_pricing.price_per_bird', 95),
            'cooling_unit_price' => (float) settings('poultry_pricing.cooling_unit_price', 1800),
            'window_unit_price' => (float) settings('poultry_pricing.window_unit_price', 450),
            'back_fan_unit_price' => (float) settings('poultry_pricing.back_fan_unit_price', 12500),
            'side_fan_unit_price' => (float) settings('poultry_pricing.side_fan_unit_price', 8500),
            'heater_unit_price' => (float) settings('poultry_pricing.heater_unit_price', 9500),
            'control_fixed_cost' => (float) settings('poultry_pricing.control_fixed_cost', 75000),
            'electricity_fixed_cost' => (float) settings('poultry_pricing.electricity_fixed_cost', 0),
            'include_tanks' => (bool) settings('poultry_pricing.include_tanks_default', true),
            'heater_lot_prices' => $this->decodeJsonSetting(
                'poultry_pricing.heater_lot_prices',
                HeaterOptions::defaultLotPrices()
            ),
            'egp_to_usd_rate' => (float) settings('poultry_pricing.egp_to_usd_rate', settings('defaults.exchange_rate', 48)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function defaultSideFanRules(): array
    {
        return [
            ['min_barn_length' => 0, 'max_barn_length' => 90, 'min_birds' => 0, 'max_birds' => 30000, 'count' => 4],
            ['min_barn_length' => 90, 'max_barn_length' => 110, 'min_birds' => 0, 'max_birds' => 50000, 'count' => 6],
            ['min_barn_length' => 110, 'max_barn_length' => 999, 'min_birds' => 0, 'max_birds' => 999999, 'count' => 10],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function defaultHeaterRules(): array
    {
        return [
            ['min_birds' => 0, 'max_birds' => 30000, 'count' => 2],
            ['min_birds' => 30001, 'max_birds' => 45000, 'count' => 3],
            ['min_birds' => 45001, 'max_birds' => 55000, 'count' => 4],
            ['min_birds' => 55001, 'max_birds' => 70000, 'count' => 5],
            ['min_birds' => 70001, 'max_birds' => 999999, 'count' => 6],
        ];
    }

    /**
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    /**
     * @param  array<string, mixed>  $params
     */
    protected function hasExplicitTechnicalOverrides(array $params): bool
    {
        return isset($params['fan_capacity_kg']) || isset($params['broiler_weight_birds_map']);
    }

    protected function decodeJsonSetting(string $key, array $default): array
    {
        $raw = settings($key);
        if ($raw === null || $raw === '') {
            return $default;
        }
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : $default;
    }
}
