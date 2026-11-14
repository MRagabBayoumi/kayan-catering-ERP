<?php

namespace App\Console\Commands;

use App\Services\KayanErpSyncService;
use Illuminate\Console\Command;

class SyncKayanErpBarcodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:sync-barcodes {--import-new : Also import non-existing products into catering catalogue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and synchronize barcodes from KAYAN ERP database into Kayan Catering';

    /**
     * Execute the console command.
     */
    public function handle(KayanErpSyncService $syncService): int
    {
        $this->info('جاري فحص الاتصال بقاعدة بيانات KAYAN ERP...');

        $test = $syncService->testConnection();
        if (! $test['success']) {
            $this->error('فشل الاتصال: '.$test['message']);

            return self::FAILURE;
        }

        $this->info("تم الاتصال بنجاح. إجمالي الأصناف بالـ ERP: {$test['total_products']}، بأكواد باركود: {$test['with_barcode']}");

        $importNew = (bool) $this->option('import-new');
        $this->info($importNew ? 'وضع الاستيراد: مزامنة الباركود + استيراد الأصناف الجديدة' : 'وضع الاستيراد: تحديث باركود الأصناف المطابقة فقط');

        $result = $syncService->syncBarcodes($importNew);

        $this->table(
            ['المؤشر', 'العدد'],
            [
                ['إجمالي أصناف KAYAN ERP بباركود', $result['total_erp']],
                ['تم تحديث باركودها محلياً', $result['updated_barcodes']],
                ['أصناف جديدة تم استيرادها', $result['imported_products']],
                ['مطابقة ومحدثة مسبقاً', $result['already_in_sync']],
            ]
        );

        $this->info('تمت المزامنة بنجاح!');

        return self::SUCCESS;
    }
}
