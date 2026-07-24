<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        // Suppliers are created in UserSeeder alongside their users.
        // This seeder updates additional supplier details.

        $details = [
            'سوبر ماركت الرائد' => [
                'description' => 'سوبر ماركت شامل يقدم جميع المنتجات الغذائية واليومية بأسعار منافسة',
                'min_order_amount' => 2000,
                'working_hours' => ['sat' => '08:00-22:00', 'sun' => '08:00-22:00', 'mon' => '08:00-22:00', 'tue' => '08:00-22:00', 'wed' => '08:00-22:00', 'thu' => '08:00-22:00', 'fri' => '14:00-22:00'],
                'delivery_areas' => ['صنعاء', 'أمانة العاصمة'],
            ],
            'بقالة الخير' => [
                'description' => 'بقالة متنوعة تخدم منطقة كريتر وعدن',
                'min_order_amount' => 1000,
                'working_hours' => ['sat' => '07:00-23:00', 'sun' => '07:00-23:00', 'mon' => '07:00-23:00', 'tue' => '07:00-23:00', 'wed' => '07:00-23:00', 'thu' => '07:00-23:00', 'fri' => '13:00-23:00'],
                'delivery_areas' => ['عدن', 'كريتر', 'المعلا'],
            ],
            'مطعم اليمان' => [
                'description' => 'مطعم يمني أصيل يقدم أشهى المأكولات التقليدية',
                'min_order_amount' => 3000,
                'working_hours' => ['sat' => '10:00-23:00', 'sun' => '10:00-23:00', 'mon' => '10:00-23:00', 'tue' => '10:00-23:00', 'wed' => '10:00-23:00', 'thu' => '10:00-23:00', 'fri' => '12:00-23:00'],
                'delivery_areas' => ['تعز', 'المدينة'],
            ],
        ];

        foreach ($details as $companyName => $data) {
            Supplier::where('company_name', $companyName)->update($data);
        }
    }
}
