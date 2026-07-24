<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'مدير النظام',
            'phone' => '770000001',
            'password' => Hash::make('password123'),
            'type' => 'admin',
            'is_verified' => true,
            'is_active' => true,
            'city' => 'صنعاء',
        ]);

        // Suppliers
        $suppliers = [
            [
                'name' => 'محمد_supply',
                'phone' => '771000001',
                'store_name' => 'سوبر ماركت الرائد',
                'store_type' => 'supermarket',
                'city' => 'صنعاء',
                'area' => 'الحصبة',
                'company' => 'سوبر ماركت الرائد',
            ],
            [
                'name' => 'أحمد_supply',
                'phone' => '771000002',
                'store_name' => 'بقالة الخير',
                'store_type' => 'grocery',
                'city' => 'عدن',
                'area' => 'كريتر',
                'company' => 'بقالة الخير',
            ],
            [
                'name' => 'علي_supply',
                'phone' => '771000003',
                'store_name' => 'مطعم اليمان',
                'store_type' => 'restaurant',
                'city' => 'تعز',
                'area' => 'القاهرة',
                'company' => 'مطعم اليمان',
            ],
        ];

        foreach ($suppliers as $s) {
            $user = User::create([
                'name' => $s['name'],
                'phone' => $s['phone'],
                'password' => Hash::make('password123'),
                'type' => 'supplier',
                'store_name' => $s['store_name'],
                'store_type' => $s['store_type'],
                'city' => $s['city'],
                'area' => $s['area'],
                'is_verified' => true,
                'is_active' => true,
            ]);

            Supplier::create([
                'user_id' => $user->id,
                'company_name' => $s['company'],
                'is_verified' => true,
                'is_active' => true,
                'delivery_fee' => 500,
                'free_delivery_threshold' => 10000,
                'delivery_time_hours' => 24,
            ]);
        }

        // Customers
        $customers = [
            ['name' => 'سالم_customer', 'phone' => '772000001', 'city' => 'صنعاء', 'area' => 'السبعين'],
            ['name' => 'فاطمة_customer', 'phone' => '772000002', 'city' => 'عدن', 'area' => 'المعلا'],
            ['name' => 'خالد_customer', 'phone' => '772000003', 'city' => 'تعز', 'area' => 'المدينة'],
            ['name' => 'نورة_customer', 'phone' => '772000004', 'city' => 'الحديدة', 'area' => 'الميناء'],
            ['name' => 'يوسف_customer', 'phone' => '772000005', 'city' => 'إب', 'area' => 'المركز'],
        ];

        foreach ($customers as $c) {
            User::create(array_merge($c, [
                'password' => Hash::make('password123'),
                'type' => 'customer',
                'is_verified' => true,
                'is_active' => true,
            ]));
        }
    }
}
