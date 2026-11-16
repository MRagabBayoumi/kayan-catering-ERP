<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Unit;
use InvalidArgumentException;

class UnitConversionService
{
    /**
     * Common legacy and bilingual aliases mapped to standard unit codes.
     */
    protected const ALIAS_MAP = [
        // Weight
        'كجم' => 'kg',
        'كيلو' => 'kg',
        'كيلوجرام' => 'kg',
        'كيلو جرام' => 'kg',
        'kg' => 'kg',
        'kgs' => 'kg',
        'kilogram' => 'kg',

        'جم' => 'g',
        'جرام' => 'g',
        'g' => 'g',
        'gm' => 'g',
        'gram' => 'g',
        'grams' => 'g',

        'مجم' => 'mg',
        'مليجرام' => 'mg',
        'mg' => 'mg',
        'milligram' => 'mg',

        'طن' => 'ton',
        'أطنان' => 'ton',
        'ton' => 'ton',
        'tons' => 'ton',

        // Volume
        'لتر' => 'l',
        'l' => 'l',
        'ltr' => 'l',
        'liter' => 'l',
        'liters' => 'l',

        'مل' => 'ml',
        'مليلتر' => 'ml',
        'ml' => 'ml',
        'milliliter' => 'ml',

        // Count / Packaging
        'قطعة' => 'piece',
        'قطعه' => 'piece',
        'حبة' => 'piece',
        'حبه' => 'piece',
        'رغيف' => 'piece',
        'وجبة' => 'piece',
        'piece' => 'piece',
        'pcs' => 'piece',
        'unit' => 'piece',

        'دستة' => 'dozen',
        'دسته' => 'dozen',
        'dozen' => 'dozen',
        'dz' => 'dozen',

        'كرتونة' => 'carton',
        'كرتونه' => 'carton',
        'طرد' => 'carton',
        'صندوق' => 'carton',
        'carton' => 'carton',
        'ctn' => 'carton',

        'علبة' => 'box',
        'علبه' => 'box',
        'box' => 'box',
    ];

    /**
     * Resolve a unit model from name, code, symbol, or known alias.
     */
    public function resolveUnit(string|Unit|null $unit): ?Unit
    {
        if ($unit instanceof Unit) {
            return $unit;
        }

        if (empty($unit)) {
            return null;
        }

        $cleaned = trim(mb_strtolower($unit));

        // 1. Check known aliases
        $code = self::ALIAS_MAP[$cleaned] ?? self::ALIAS_MAP[$unit] ?? null;
        if ($code) {
            $matched = Unit::where('code', $code)->first();
            if ($matched) {
                return $matched;
            }
        }

        // 2. Direct query by code, name, or symbol
        return Unit::where('code', $cleaned)
            ->orWhere('name', $unit)
            ->orWhere('symbol', $unit)
            ->first();
    }

    /**
     * Convert a quantity from one unit of measure to another.
     *
     * @throws InvalidArgumentException
     */
    public function convert(float $quantity, string|Unit $from, string|Unit $to, ?Product $product = null): float
    {
        $fromUnit = $this->resolveUnit($from);
        $toUnit = $this->resolveUnit($to);

        if (! $fromUnit || ! $toUnit) {
            throw new InvalidArgumentException("Unable to resolve units for conversion: [{$from}] to [{$to}].");
        }

        // Same unit
        if ($fromUnit->id === $toUnit->id || $fromUnit->code === $toUnit->code) {
            return $quantity;
        }

        // 1. Same category conversion (e.g. g -> kg, l -> ml)
        if ($fromUnit->category === $toUnit->category) {
            $baseQuantity = $quantity * (float) $fromUnit->conversion_factor;

            return $baseQuantity / (float) $toUnit->conversion_factor;
        }

        // 2. Product-specific packaging conversion (e.g., cartons or dozens to pieces)
        if ($product && in_array($fromUnit->category, [Unit::CATEGORY_COUNT, Unit::CATEGORY_PACKAGING])
            && in_array($toUnit->category, [Unit::CATEGORY_COUNT, Unit::CATEGORY_PACKAGING])) {

            $packagingMultiplier = $product->pieces_per_package && $product->pieces_per_package > 1
                ? (float) $product->pieces_per_package
                : (float) $fromUnit->conversion_factor;

            if ($toUnit->code === 'piece') {
                return $quantity * $packagingMultiplier;
            }

            if ($fromUnit->code === 'piece' && $packagingMultiplier > 0) {
                return $quantity / $packagingMultiplier;
            }
        }

        throw new InvalidArgumentException(
            "Incompatible unit categories: cannot convert from [{$fromUnit->category}: {$fromUnit->name}] to [{$toUnit->category}: {$toUnit->name}] without custom product density or packaging rules."
        );
    }

    /**
     * Normalize raw kitchen/recipe quantities to standard recipe units (kg, liter, piece).
     *
     * @return array{quantity: float, unit: string}
     */
    public function normalizeForRecipe(float $quantity, string $unit): array
    {
        $cleaned = trim(mb_strtolower($unit));
        $mappedCode = self::ALIAS_MAP[$cleaned] ?? self::ALIAS_MAP[$unit] ?? $cleaned;

        // Grams to Kilograms (unconditional exact decimal conversion)
        if (in_array($mappedCode, ['g', 'gm', 'gram', 'grams', 'جم', 'جرام'])) {
            return [
                'quantity' => $quantity / 1000.0,
                'unit' => 'kg',
            ];
        }

        // Milligrams to Kilograms
        if (in_array($mappedCode, ['mg', 'مجم', 'مليجرام'])) {
            return [
                'quantity' => $quantity / 1000000.0,
                'unit' => 'kg',
            ];
        }

        // Milliliters to Liters
        if (in_array($mappedCode, ['ml', 'مل', 'مليلتر'])) {
            return [
                'quantity' => $quantity / 1000.0,
                'unit' => 'l',
            ];
        }

        // Tons to Kilograms
        if (in_array($mappedCode, ['ton', 'طن'])) {
            return [
                'quantity' => $quantity * 1000.0,
                'unit' => 'kg',
            ];
        }

        return [
            'quantity' => $quantity,
            'unit' => $mappedCode,
        ];
    }
}
