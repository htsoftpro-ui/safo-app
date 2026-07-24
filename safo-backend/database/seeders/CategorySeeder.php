<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'مواد غذائية', 'name_en' => 'Food', 'icon' => 'utensils', 'sort' => 1],
            ['name' => 'مشروبات', 'name_en' => 'Beverages', 'icon' => 'cup', 'sort' => 2],
            ['name' => 'منظفات', 'name_en' => 'Cleaning', 'icon' => 'broom', 'sort' => 3],
            ['name' => 'عناية شخصية', 'name_en' => 'Personal Care', 'icon' => 'heart', 'sort' => 4],
            ['name' => 'ألبان وأجبان', 'name_en' => 'Dairy', 'icon' => 'cheese', 'sort' => 5],
            ['name' => 'مخبوزات', 'name_en' => 'Bakery', 'icon' => 'bread', 'sort' => 6],
            ['name' => 'توابل وبهارات', 'name_en' => 'Spices', 'icon' => 'fire', 'sort' => 7],
            ['name' => 'معلبات', 'name_en' => 'Canned Food', 'icon' => 'can', 'sort' => 8],
            ['name' => 'حلويات', 'name_en' => 'Sweets', 'icon' => 'candy', 'sort' => 9],
            ['name' => 'إلكترونيات', 'name_en' => 'Electronics', 'icon' => 'plug', 'sort' => 10],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'name' => $cat['name'],
                'name_en' => $cat['name_en'],
                'icon' => $cat['icon'],
                'sort_order' => $cat['sort'],
                'is_active' => true,
            ]);
        }
    }
}
