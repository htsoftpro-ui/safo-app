<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all()->keyBy('name');
        $suppliers = Supplier::all();

        if ($suppliers->isEmpty()) {
            return;
        }

        $products = [
            // مواد غذائية
            ['name' => 'أرز بسمتي 5 كجم', 'cat' => 'مواد غذائية', 'price' => 3500, 'unit' => 'كيس', 'stock' => 200],
            ['name' => 'زيت طبخ 1.5 لتر', 'cat' => 'مواد غذائية', 'price' => 1800, 'unit' => 'زجاجة', 'stock' => 150],
            ['name' => 'سكر 2 كجم', 'cat' => 'مواد غذائية', 'price' => 1200, 'unit' => 'كيس', 'stock' => 300],
            ['name' => 'دقيق 2 كجم', 'cat' => 'مواد غذائية', 'price' => 900, 'unit' => 'كيس', 'stock' => 250],
            ['name' => 'معكرونة 500جم', 'cat' => 'مواد غذائية', 'price' => 450, 'unit' => 'علبة', 'stock' => 500],

            // مشروبات
            ['name' => 'مياه معدنية 1.5 لتر', 'cat' => 'مشروبات', 'price' => 150, 'unit' => 'زجاجة', 'stock' => 1000],
            ['name' => 'عصير برتقال 1 لتر', 'cat' => 'مشروبات', 'price' => 600, 'unit' => 'كرتون', 'stock' => 100],
            ['name' => 'شاي أحمر 500جم', 'cat' => 'مشروبات', 'price' => 800, 'unit' => 'علبة', 'stock' => 200],
            ['name' => 'قهوة يمنية 250جم', 'cat' => 'مشروبات', 'price' => 2500, 'unit' => 'علبة', 'stock' => 50],
            ['name' => 'مشروب غازي 330مل', 'cat' => 'مشروبات', 'price' => 200, 'unit' => 'علبة', 'stock' => 800],

            // منظفات
            ['name' => 'مسحوق غسيل 2 كجم', 'cat' => 'منظفات', 'price' => 1500, 'unit' => 'علبة', 'stock' => 120],
            ['name' => 'سائل تنظيف أرضيات 1 لتر', 'cat' => 'منظفات', 'price' => 700, 'unit' => 'زجاجة', 'stock' => 80],
            ['name' => 'صابون أطباق 500مل', 'cat' => 'منظفات', 'price' => 350, 'unit' => 'زجاجة', 'stock' => 200],

            // عناية شخصية
            ['name' => 'شامبو 400مل', 'cat' => 'عناية شخصية', 'price' => 900, 'unit' => 'زجاجة', 'stock' => 60],
            ['name' => 'معجون أسنان 100مل', 'cat' => 'عناية شخصية', 'price' => 400, 'unit' => 'أنبوب', 'stock' => 150],
            ['name' => 'صابون وجه 100جم', 'cat' => 'عناية شخصية', 'price' => 300, 'unit' => 'قطعة', 'stock' => 200],

            // ألبان وأجبان
            ['name' => 'حليب طويل الأمد 1 لتر', 'cat' => 'ألبان وأجبان', 'price' => 650, 'unit' => 'كرتون', 'stock' => 300],
            ['name' => 'جبنة بيضاء 500جم', 'cat' => 'ألبان وأجبان', 'price' => 1200, 'unit' => 'علبة', 'stock' => 80],
            ['name' => 'زبادي 170جم', 'cat' => 'ألبان وأجبان', 'price' => 200, 'unit' => 'كوب', 'stock' => 400],
            ['name' => 'زبدة 250جم', 'cat' => 'ألبان وأجبان', 'price' => 800, 'unit' => 'علبة', 'stock' => 60],

            // مخبوزات
            ['name' => 'خبز عربي', 'cat' => 'مخبوزات', 'price' => 100, 'unit' => 'كيس', 'stock' => 500],
            ['name' => 'كعك 6 قطع', 'cat' => 'مخبوزات', 'price' => 500, 'unit' => 'كيس', 'stock' => 200],

            // توابل
            ['name' => 'فلفل أسود 100جم', 'cat' => 'توابل وبهارات', 'price' => 400, 'unit' => 'علبة', 'stock' => 100],
            ['name' => 'كمون 100جم', 'cat' => 'توابل وبهارات', 'price' => 300, 'unit' => 'علبة', 'stock' => 120],
            ['name' => 'هيل 50جم', 'cat' => 'توابل وبهارات', 'price' => 600, 'unit' => 'علبة', 'stock' => 80],

            // معلبات
            ['name' => 'تونة 185جم', 'cat' => 'معلبات', 'price' => 500, 'unit' => 'علبة', 'stock' => 300],
            ['name' => 'حمص مسلوق 400جم', 'cat' => 'معلبات', 'price' => 350, 'unit' => 'علبة', 'stock' => 200],
            ['name' => 'صلصة طماطم 400جم', 'cat' => 'معلبات', 'price' => 250, 'unit' => 'علبة', 'stock' => 400],

            // حلويات
            ['name' => 'شوكولاتة 100جم', 'cat' => 'حلويات', 'price' => 600, 'unit' => 'قطعة', 'stock' => 150],
            ['name' => 'بسكويت 200جم', 'cat' => 'حلويات', 'price' => 350, 'unit' => 'علبة', 'stock' => 200],
        ];

        foreach ($products as $i => $p) {
            $categoryId = $categories[$p['cat']]?->id;
            $supplierId = $suppliers[$i % $suppliers->count()]->id;

            Product::create([
                'supplier_id' => $supplierId,
                'category_id' => $categoryId,
                'name' => $p['name'],
                'slug' => \Str::slug($p['name'] . '-' . ($i + 1)),
                'price' => $p['price'],
                'unit' => $p['unit'],
                'stock_quantity' => $p['stock'],
                'min_order_quantity' => 1,
                'low_stock_threshold' => 10,
                'is_active' => true,
                'is_featured' => $i < 5,
                'views_count' => rand(10, 500),
                'sales_count' => rand(0, 100),
                'rating' => rand(300, 500) / 100,
                'total_ratings' => rand(0, 50),
            ]);
        }
    }
}
