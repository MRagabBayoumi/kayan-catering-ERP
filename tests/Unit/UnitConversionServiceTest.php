<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\UnitConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class UnitConversionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UnitConversionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->service = app(UnitConversionService::class);
    }

    public function test_resolves_units_from_codes_and_arabic_aliases(): void
    {
        $kgUnit = $this->service->resolveUnit('كجم');
        $this->assertNotNull($kgUnit);
        $this->assertEquals('kg', $kgUnit->code);

        $gramUnit = $this->service->resolveUnit('جرام');
        $this->assertNotNull($gramUnit);
        $this->assertEquals('g', $gramUnit->code);

        $literUnit = $this->service->resolveUnit('لتر');
        $this->assertNotNull($literUnit);
        $this->assertEquals('l', $literUnit->code);

        $mlUnit = $this->service->resolveUnit('مل');
        $this->assertNotNull($mlUnit);
        $this->assertEquals('ml', $mlUnit->code);

        $pieceUnit = $this->service->resolveUnit('قطعة');
        $this->assertNotNull($pieceUnit);
        $this->assertEquals('piece', $pieceUnit->code);
    }

    public function test_converts_weight_measurements_correctly(): void
    {
        // 500 grams = 0.5 kilograms
        $converted = $this->service->convert(500, 'g', 'kg');
        $this->assertEquals(0.5, $converted);

        // 2.5 kilograms = 2500 grams
        $convertedBack = $this->service->convert(2.5, 'kg', 'g');
        $this->assertEquals(2500, $convertedBack);
    }

    public function test_converts_volume_measurements_correctly(): void
    {
        // 750 ml = 0.75 liter
        $converted = $this->service->convert(750, 'ml', 'l');
        $this->assertEquals(0.75, $converted);

        // 3 liters = 3000 ml
        $convertedBack = $this->service->convert(3, 'l', 'ml');
        $this->assertEquals(3000, $convertedBack);
    }

    public function test_converts_packaging_units_with_product_multiplier(): void
    {
        $product = new Product([
            'name' => 'جبنة بيضاء معلبة',
            'pieces_per_package' => 24,
        ]);

        // 5 cartons * 24 pieces/carton = 120 pieces
        $pieces = $this->service->convert(5, 'carton', 'piece', $product);
        $this->assertEquals(120, $pieces);

        // 48 pieces / 24 = 2 cartons
        $cartons = $this->service->convert(48, 'piece', 'carton', $product);
        $this->assertEquals(2, $cartons);
    }

    public function test_throws_exception_on_incompatible_unit_categories_without_density(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->convert(10, 'kg', 'l');
    }

    public function test_normalizes_for_recipe_bom(): void
    {
        $normalizedGrams = $this->service->normalizeForRecipe(250, 'g');
        $this->assertEquals(0.25, $normalizedGrams['quantity']);
        $this->assertEquals('kg', $normalizedGrams['unit']);

        $normalizedFractionalGrams = $this->service->normalizeForRecipe(0.5, 'جرام');
        $this->assertEquals(0.0005, $normalizedFractionalGrams['quantity']);
        $this->assertEquals('kg', $normalizedFractionalGrams['unit']);

        $normalizedMl = $this->service->normalizeForRecipe(500, 'مليلتر');
        $this->assertEquals(0.5, $normalizedMl['quantity']);
        $this->assertEquals('l', $normalizedMl['unit']);
    }
}
