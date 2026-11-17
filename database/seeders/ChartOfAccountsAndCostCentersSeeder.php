<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\Contract;
use App\Models\ContractMealPrice;
use App\Models\CostCenter;
use App\Models\Hospital;
use App\Models\MealType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ChartOfAccountsAndCostCentersSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Chart of Accounts (دليل الحسابات المعتمد لشركات الكيترينج)
        $accounts = [
            // Assets
            ['code' => '100000', 'name' => 'الأصول', 'type' => ChartOfAccount::TYPE_ASSET, 'level' => 1, 'is_system' => true],
            ['code' => '101000', 'name' => 'الأصول المتداولة', 'type' => ChartOfAccount::TYPE_ASSET, 'level' => 2, 'parent_code' => '100000', 'is_system' => true],
            ['code' => '101100', 'name' => 'الخزائن والصناديق النقدية', 'type' => ChartOfAccount::TYPE_ASSET, 'level' => 3, 'parent_code' => '101000', 'is_system' => true],
            ['code' => '101200', 'name' => 'الحسابات البنكية', 'type' => ChartOfAccount::TYPE_ASSET, 'level' => 3, 'parent_code' => '101000', 'is_system' => true],
            ['code' => '101300', 'name' => 'مخزون الخامات الغذائية الرئيسي', 'type' => ChartOfAccount::TYPE_ASSET, 'level' => 3, 'parent_code' => '101000', 'is_system' => true],
            ['code' => '101400', 'name' => 'مخزون عهدة سيارات التوزيع المتنقلة', 'type' => ChartOfAccount::TYPE_ASSET, 'level' => 3, 'parent_code' => '101000', 'is_system' => true],
            ['code' => '101500', 'name' => 'مخزون المواقع الفرعية بالمستشفيات', 'type' => ChartOfAccount::TYPE_ASSET, 'level' => 3, 'parent_code' => '101000', 'is_system' => true],
            ['code' => '101600', 'name' => 'مدينو المستشفيات والجهات المتعاقدة (AR)', 'type' => ChartOfAccount::TYPE_ASSET, 'level' => 3, 'parent_code' => '101000', 'is_system' => true],
            ['code' => '101700', 'name' => 'عهد مديري المواقع النقدية (شراء الخبز)', 'type' => ChartOfAccount::TYPE_ASSET, 'level' => 3, 'parent_code' => '101000', 'is_system' => true],

            // Liabilities
            ['code' => '200000', 'name' => 'الخصوم والالتزامات', 'type' => ChartOfAccount::TYPE_LIABILITY, 'level' => 1, 'is_system' => true],
            ['code' => '201000', 'name' => 'دائنو وموردو الخامات والتوريدات (AP)', 'type' => ChartOfAccount::TYPE_LIABILITY, 'level' => 2, 'parent_code' => '200000', 'is_system' => true],
            ['code' => '202000', 'name' => 'مصروفات ومستحقات تشغيلية مؤجلة', 'type' => ChartOfAccount::TYPE_LIABILITY, 'level' => 2, 'parent_code' => '200000', 'is_system' => true],

            // Equity
            ['code' => '300000', 'name' => 'حقوق الملكية ورأس المال', 'type' => ChartOfAccount::TYPE_EQUITY, 'level' => 1, 'is_system' => true],
            ['code' => '301000', 'name' => 'حساب جاري المالك والشركاء', 'type' => ChartOfAccount::TYPE_EQUITY, 'level' => 2, 'parent_code' => '300000', 'is_system' => true],

            // Revenues
            ['code' => '400000', 'name' => 'الإيرادات التشغيلية', 'type' => ChartOfAccount::TYPE_REVENUE, 'level' => 1, 'is_system' => true],
            ['code' => '401000', 'name' => 'إيرادات عقود تغذية المستشفيات المعتمدة', 'type' => ChartOfAccount::TYPE_REVENUE, 'level' => 2, 'parent_code' => '400000', 'is_system' => true],

            // Expenses & COGS
            ['code' => '500000', 'name' => 'تكلفة النشاط والمصروفات التشغيلية', 'type' => ChartOfAccount::TYPE_EXPENSE, 'level' => 1, 'is_system' => true],
            ['code' => '501000', 'name' => 'تكلفة خامات الأغذية المستهلكة (Food COGS)', 'type' => ChartOfAccount::TYPE_EXPENSE, 'level' => 2, 'parent_code' => '500000', 'is_system' => true],
            ['code' => '502000', 'name' => 'أجور ومرتبات عمالة مطابخ المستشفيات (Site Labor)', 'type' => ChartOfAccount::TYPE_EXPENSE, 'level' => 2, 'parent_code' => '500000', 'is_system' => true],
            ['code' => '503000', 'name' => 'مصاريف وقود ولوجستيات سيارات التوزيع', 'type' => ChartOfAccount::TYPE_EXPENSE, 'level' => 2, 'parent_code' => '500000', 'is_system' => true],
            ['code' => '504000', 'name' => 'مصروفات تشغيل الموقع ومشتريات الطوارئ', 'type' => ChartOfAccount::TYPE_EXPENSE, 'level' => 2, 'parent_code' => '500000', 'is_system' => true],
            ['code' => '505000', 'name' => 'خسائر الهدر والتلف المطبخي والمخزني', 'type' => ChartOfAccount::TYPE_EXPENSE, 'level' => 2, 'parent_code' => '500000', 'is_system' => true],
            ['code' => '506000', 'name' => 'مصروفات إدارية وعمومية', 'type' => ChartOfAccount::TYPE_EXPENSE, 'level' => 2, 'parent_code' => '500000', 'is_system' => true],
        ];

        $codeToIdMap = [];
        foreach ($accounts as $accData) {
            $parentId = isset($accData['parent_code']) ? ($codeToIdMap[$accData['parent_code']] ?? null) : null;
            $account = ChartOfAccount::updateOrCreate(
                ['code' => $accData['code']],
                [
                    'name' => $accData['name'],
                    'type' => $accData['type'],
                    'parent_id' => $parentId,
                    'level' => $accData['level'],
                    'is_system' => $accData['is_system'] ?? false,
                    'is_active' => true,
                ]
            );
            $codeToIdMap[$accData['code']] = $account->id;
        }

        // 2. Cost Centers for General Operations and Hospitals
        $generalCostCenters = [
            ['code' => 'CC-ADMIN', 'name' => 'الإدارة العامة والمصروفات المركزية', 'type' => CostCenter::TYPE_ADMIN],
            ['code' => 'CC-WH-MAIN', 'name' => 'المخزن اللوجستي المركزي', 'type' => CostCenter::TYPE_CENTRAL_WAREHOUSE],
            ['code' => 'CC-FLEET', 'name' => 'أسطول سيارات التوزيع والنقل', 'type' => CostCenter::TYPE_VEHICLE],
        ];

        foreach ($generalCostCenters as $gcc) {
            CostCenter::updateOrCreate(
                ['code' => $gcc['code']],
                [
                    'name' => $gcc['name'],
                    'type' => $gcc['type'],
                    'is_active' => true,
                ]
            );
        }

        // Create Cost Centers & Contracts for all existing Hospitals
        $admin = User::first();
        $mealTypes = MealType::all();

        $hospitals = Hospital::all();
        foreach ($hospitals as $hosp) {
            $costCenter = CostCenter::updateOrCreate(
                ['code' => 'CC-HOSP-'.$hosp->id],
                [
                    'name' => 'مركز تكلفة: '.$hosp->name,
                    'type' => CostCenter::TYPE_HOSPITAL,
                    'hospital_id' => $hosp->id,
                    'is_active' => true,
                ]
            );

            // Create initial active contract
            $contractNumber = 'CTR-'.date('Y').'-'.str_pad((string) $hosp->id, 4, '0', STR_PAD_LEFT);
            $contract = Contract::updateOrCreate(
                ['contract_number' => $contractNumber],
                [
                    'hospital_id' => $hosp->id,
                    'cost_center_id' => $costCenter->id,
                    'title' => 'عقد توريد وتغذية نزلاء '.$hosp->name,
                    'start_date' => Carbon::now()->subMonths(2),
                    'end_date' => Carbon::now()->addMonths(10),
                    'status' => Contract::STATUS_ACTIVE,
                    'contract_value' => 3500000.00,
                    'billing_cycle' => 'monthly',
                    'payment_terms_days' => 30,
                    'penalty_terms' => 'غرامة 5% عن كل يوم تأخير في مواعيد تقديم الوجبات الرسمية.',
                    'approved_by' => $admin?->id,
                    'approved_at' => Carbon::now()->subMonths(2),
                    'notes' => 'عقد سنوي سارٍ معتمد من الشؤون القانونية والمبيعات.',
                ]
            );

            // Meal prices matrix for this contract
            foreach ($mealTypes as $mt) {
                $basePrice = 85.00;
                if (str_contains($mt->name, 'لحوم') || str_contains($mt->name, 'لحم')) {
                    $basePrice = 120.00;
                } elseif (str_contains($mt->name, 'دواجن') || str_contains($mt->name, 'فراخ')) {
                    $basePrice = 95.00;
                } elseif (str_contains($mt->name, 'إفطار') || str_contains($mt->name, 'عشاء')) {
                    $basePrice = 45.00;
                }

                ContractMealPrice::updateOrCreate(
                    [
                        'contract_id' => $contract->id,
                        'meal_type_id' => $mt->id,
                        'diet_category' => 'standard',
                    ],
                    [
                        'price_per_meal' => $basePrice,
                        'minimum_daily_quantity' => (int) ($hosp->daily_patient_capacity * 0.8),
                    ]
                );
            }
        }
    }
}
