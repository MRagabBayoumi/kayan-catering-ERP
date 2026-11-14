<?php

namespace App\Services;

use App\Models\Product;
use Exception;
use PDO;

class KayanErpSyncService
{
    /**
     * Get a PDO connection to the external Kayan ERP database.
     */
    public function getErpPdo(): PDO
    {
        $host = env('ERP_DB_HOST', '127.0.0.1');
        $port = env('ERP_DB_PORT', '3306');
        $database = env('ERP_DB_DATABASE', 'kayan_erp');
        $username = env('ERP_DB_USERNAME', 'root');
        $password = env('ERP_DB_PASSWORD', '');

        // Try MySQL first
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            return $pdo;
        } catch (Exception $e) {
            // Check if there is an SQLite fallback in sibling kayan-erp folder
            $sqlitePath = base_path('../kayan-erp/database/database.sqlite');
            if (file_exists($sqlitePath)) {
                $sqlitePdo = new PDO('sqlite:'.$sqlitePath);
                $sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $sqlitePdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                return $sqlitePdo;
            }

            throw new Exception("تعذر الاتصال بقاعدة بيانات KAYAN ERP: {$e->getMessage()}");
        }
    }

    /**
     * Test connection to Kayan ERP database.
     *
     * @return array{success: bool, message: string, total_products: int, with_barcode: int}
     */
    public function testConnection(): array
    {
        try {
            $pdo = $this->getErpPdo();
            $stmt = $pdo->query('SELECT COUNT(*) as total, COUNT(barcode) as with_barcode FROM products');
            $row = $stmt->fetch();

            return [
                'success' => true,
                'message' => 'تم الاتصال بقاعدة بيانات KAYAN ERP بنجاح.',
                'total_products' => (int) ($row['total'] ?? 0),
                'with_barcode' => (int) ($row['with_barcode'] ?? 0),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'total_products' => 0,
                'with_barcode' => 0,
            ];
        }
    }

    /**
     * Synchronize barcodes from Kayan ERP to Kayan Catering.
     *
     * @return array{
     *     total_erp: int,
     *     updated_barcodes: int,
     *     imported_products: int,
     *     already_in_sync: int,
     *     details: array<int, string>
     * }
     */
    public function syncBarcodes(bool $importNewProducts = false): array
    {
        $pdo = $this->getErpPdo();

        $stmt = $pdo->query(
            'SELECT id, sku, name, barcode, cost_price, unit, min_stock_alert, is_active FROM products WHERE barcode IS NOT NULL AND barcode != ""'
        );
        $erpProducts = $stmt->fetchAll();

        $updatedCount = 0;
        $importedCount = 0;
        $alreadyInSyncCount = 0;
        $details = [];

        foreach ($erpProducts as $erpItem) {
            $sku = trim((string) ($erpItem['sku'] ?? ''));
            $name = trim((string) ($erpItem['name'] ?? ''));
            $barcode = trim((string) ($erpItem['barcode'] ?? ''));

            if (empty($barcode)) {
                continue;
            }

            // Look up local product by SKU, name, or existing barcode
            $localProduct = Product::query()
                ->where(function ($q) use ($sku, $name, $barcode) {
                    if (! empty($sku)) {
                        $q->where('sku', $sku);
                    }
                    if (! empty($name)) {
                        $q->orWhere('name', $name);
                    }
                    $q->orWhere('barcode', $barcode);
                })
                ->first();

            if ($localProduct) {
                if ($localProduct->barcode !== $barcode) {
                    $localProduct->barcode = $barcode;
                    $localProduct->save();
                    $updatedCount++;
                    $details[] = "تم تحديث باركود الصنف [{$localProduct->name}] إلى ({$barcode})";
                } else {
                    $alreadyInSyncCount++;
                }
            } elseif ($importNewProducts) {
                // Ensure unique SKU
                $uniqueSku = ! empty($sku) ? $sku : 'ERP-'.str_pad((string) $erpItem['id'], 5, '0', STR_PAD_LEFT);
                if (Product::where('sku', $uniqueSku)->exists()) {
                    $uniqueSku = $uniqueSku.'-'.substr(md5(uniqid()), 0, 4);
                }

                $newProduct = Product::create([
                    'sku' => $uniqueSku,
                    'name' => $name ?: 'صنف مستورد من ERP '.$erpItem['id'],
                    'barcode' => $barcode,
                    'cost_price' => (float) ($erpItem['cost_price'] ?? 0.00),
                    'unit' => ! empty($erpItem['unit']) ? $erpItem['unit'] : 'kg',
                    'storage_type' => 'dry',
                    'shelf_life_days' => 180,
                    'acceptable_waste_pct' => 0.00,
                    'min_stock_alert' => (int) ($erpItem['min_stock_alert'] ?? 5),
                    'is_active' => (bool) ($erpItem['is_active'] ?? true),
                    'notes' => 'مستورد آلياً من KAYAN ERP',
                ]);

                $importedCount++;
                $details[] = "تم استيراد صنف جديد [{$newProduct->name}] بالباركود ({$barcode})";
            }
        }

        return [
            'total_erp' => count($erpProducts),
            'updated_barcodes' => $updatedCount,
            'imported_products' => $importedCount,
            'already_in_sync' => $alreadyInSyncCount,
            'details' => $details,
        ];
    }
}
