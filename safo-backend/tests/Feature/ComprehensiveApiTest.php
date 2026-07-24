<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ─────────────────────────────────────────

    private function createUser(string $type = 'customer', string $phone = '772000001'): User
    {
        return User::create([
            'name' => "test_{$type}",
            'phone' => $phone,
            'password' => 'password123',
            'type' => $type,
            'is_verified' => true,
            'is_active' => true,
        ]);
    }

    private function createSupplier(string $phone = '771000001'): array
    {
        $user = $this->createUser('supplier', $phone);
        $supplier = Supplier::create([
            'user_id' => $user->id,
            'company_name' => 'Test Supplier',
            'is_verified' => true,
            'is_active' => true,
            'delivery_fee' => 500,
            'free_delivery_threshold' => 10000,
        ]);
        return [$user, $supplier];
    }

    private function createProduct(Supplier $supplier, Category $category, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product-' . uniqid(),
            'price' => 1000,
            'unit' => 'piece',
            'stock_quantity' => 50,
            'min_order_quantity' => 1,
            'low_stock_threshold' => 10,
            'is_active' => true,
        ], $overrides));
    }

    private function createOrderFor(User $customer, Supplier $supplier, Product $product, Address $address): int
    {
        \App\Models\CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'quantity' => 2,
            'unit_price' => $product->price,
            'total_price' => $product->price * 2,
        ]);

        $response = $this->actingAs($customer)->postJson('/api/v1/orders', [
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ]);

        return $response->json('data.id');
    }

    // ═══════════════════════════════════════════════════════
    // PRODUCT BROWSING
    // ═══════════════════════════════════════════════════════

    public function test_product_filter_by_category(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000101');
        $cat1 = Category::create(['name' => 'Food', 'slug' => 'food-' . uniqid()]);
        $cat2 = Category::create(['name' => 'Drinks', 'slug' => 'drinks-' . uniqid()]);
        $this->createProduct($supplier, $cat1);
        $this->createProduct($supplier, $cat2);

        $response = $this->getJson("/api/v1/products?category_id={$cat1->id}");
        $response->assertOk();
        $this->assertEquals(1, count($response->json('data')));
    }

    public function test_product_filter_by_price_range(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000102');
        $cat = Category::create(['name' => 'Cat', 'slug' => 'cat-' . uniqid()]);
        $this->createProduct($supplier, $cat, ['price' => 500]);
        $this->createProduct($supplier, $cat, ['price' => 1500]);
        $this->createProduct($supplier, $cat, ['price' => 3000]);

        $response = $this->getJson('/api/v1/products?min_price=1000&max_price=2000');
        $response->assertOk();
        $this->assertEquals(1, count($response->json('data')));
    }

    public function test_product_sort_by_price(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000103');
        $cat = Category::create(['name' => 'Sort', 'slug' => 'sort-' . uniqid()]);
        $this->createProduct($supplier, $cat, ['price' => 3000]);
        $this->createProduct($supplier, $cat, ['price' => 500]);

        $response = $this->getJson('/api/v1/products?sort=price&order=asc');
        $response->assertOk();
        $prices = array_column($response->json('data'), 'price');
        $this->assertTrue($prices[0] < $prices[1]);
    }

    public function test_product_pagination(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000104');
        $cat = Category::create(['name' => 'Page', 'slug' => 'page-' . uniqid()]);
        for ($i = 0; $i < 15; $i++) {
            $this->createProduct($supplier, $cat, ['name' => "P{$i}", 'slug' => "p{$i}-" . uniqid()]);
        }

        $response = $this->getJson('/api/v1/products?per_page=5');
        $response->assertOk();
        $this->assertEquals(5, count($response->json('data')));
    }

    public function test_product_inactive_not_shown(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000105');
        $cat = Category::create(['name' => 'Hidden', 'slug' => 'hidden-' . uniqid()]);
        $this->createProduct($supplier, $cat, ['is_active' => false]);

        $response = $this->getJson('/api/v1/products');
        $response->assertOk();
        $this->assertEquals(0, count($response->json('data')));
    }

    public function test_product_out_of_stock_not_shown(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000106');
        $cat = Category::create(['name' => 'OOS', 'slug' => 'oos-' . uniqid()]);
        $this->createProduct($supplier, $cat, ['stock_quantity' => 0]);

        $response = $this->getJson('/api/v1/products');
        $response->assertOk();
        $this->assertEquals(0, count($response->json('data')));
    }

    public function test_product_new_arrivals(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000107');
        $cat = Category::create(['name' => 'New', 'slug' => 'new-' . uniqid()]);
        $this->createProduct($supplier, $cat);

        $response = $this->getJson('/api/v1/products/new-arrivals');
        $response->assertOk();
    }

    public function test_product_best_sellers(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000108');
        $cat = Category::create(['name' => 'Best', 'slug' => 'best-' . uniqid()]);
        $this->createProduct($supplier, $cat, ['sales_count' => 100]);
        $this->createProduct($supplier, $cat, ['sales_count' => 5]);

        $response = $this->getJson('/api/v1/products/best-sellers');
        $response->assertOk();
        $sales = array_column($response->json('data'), 'sales_count');
        $this->assertTrue($sales[0] >= $sales[1]);
    }

    public function test_product_search_no_results(): void
    {
        $response = $this->getJson('/api/v1/products/search?q=nonexistent_product_xyz');
        $response->assertOk();
        $this->assertEquals(0, count($response->json('data')));
    }

    public function test_product_404(): void
    {
        $response = $this->getJson('/api/v1/products/99999');
        $response->assertStatus(404);
    }

    // ═══════════════════════════════════════════════════════
    // CART LIFECYCLE
    // ═══════════════════════════════════════════════════════

    public function test_cart_add_below_minimum_quantity(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000201');
        $cat = Category::create(['name' => 'Min', 'slug' => 'min-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat, ['min_order_quantity' => 5]);
        $user = $this->createUser('customer', '772000201');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 2]);

        $response->assertStatus(422);
    }

    public function test_cart_add_exceeds_stock(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000202');
        $cat = Category::create(['name' => 'Stock', 'slug' => 'stock-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat, ['stock_quantity' => 5]);
        $user = $this->createUser('customer', '772000202');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 10]);

        $response->assertStatus(422);
    }

    public function test_cart_add_inactive_product(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000203');
        $cat = Category::create(['name' => 'Inact', 'slug' => 'inact-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat, ['is_active' => false]);
        $user = $this->createUser('customer', '772000203');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 1]);

        $response->assertStatus(422);
    }

    public function test_cart_duplicate_product_increases_quantity(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000204');
        $cat = Category::create(['name' => 'Dup', 'slug' => 'dup-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat);
        $user = $this->createUser('customer', '772000204');

        $this->actingAs($user)->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 2]);
        $this->actingAs($user)->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 3]);

        $cart = $this->actingAs($user)->getJson('/api/v1/cart');
        $cart->assertOk();
        $this->assertEquals(5, $cart->json('data.items')[0]['quantity']);
    }

    public function test_cart_delete_nonexistent_item(): void
    {
        $user = $this->createUser('customer', '772000205');

        $response = $this->actingAs($user)->deleteJson('/api/v1/cart/99999');
        $response->assertStatus(404);
    }

    // ═══════════════════════════════════════════════════════
    // ORDER LIFECYCLE — FULL END-TO-END
    // ═══════════════════════════════════════════════════════

    public function test_order_creation_deducts_stock(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000301');
        $cat = Category::create(['name' => 'Stock', 'slug' => 'stock-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat, ['stock_quantity' => 20]);
        $customer = $this->createUser('customer', '772000301');
        $address = Address::create(['user_id' => $customer->id, 'title' => 'H', 'address' => 'St', 'is_default' => true]);

        $this->createOrderFor($customer, $supplier, $product, $address);

        $product->refresh();
        $this->assertEquals(18, $product->stock_quantity); // 20 - 2
    }

    public function test_order_cancel_restores_stock(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000302');
        $cat = Category::create(['name' => 'Rest', 'slug' => 'rest-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat, ['stock_quantity' => 20]);
        $customer = $this->createUser('customer', '772000302');
        $address = Address::create(['user_id' => $customer->id, 'title' => 'H', 'address' => 'St', 'is_default' => true]);

        $orderId = $this->createOrderFor($customer, $supplier, $product, $address);

        $product->refresh();
        $this->assertEquals(18, $product->stock_quantity);

        $this->actingAs($customer)->postJson("/api/v1/orders/{$orderId}/cancel", ['reason' => 'test']);
        $product->refresh();
        $this->assertEquals(20, $product->stock_quantity); // restored
    }

    public function test_order_reject_restores_stock(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000303');
        $cat = Category::create(['name' => 'Rej', 'slug' => 'rej-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat, ['stock_quantity' => 20]);
        $customer = $this->createUser('customer', '772000303');
        $address = Address::create(['user_id' => $customer->id, 'title' => 'H', 'address' => 'St', 'is_default' => true]);

        $orderId = $this->createOrderFor($customer, $supplier, $product, $address);

        $this->actingAs($sUser)->postJson("/api/v1/supplier/orders/{$orderId}/reject", ['reason' => 'Out of stock']);
        $product->refresh();
        $this->assertEquals(20, $product->stock_quantity); // restored
    }

    public function test_order_cannot_cancel_when_shipped(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000304');
        $cat = Category::create(['name' => 'Ship', 'slug' => 'ship-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat);
        $customer = $this->createUser('customer', '772000304');
        $address = Address::create(['user_id' => $customer->id, 'title' => 'H', 'address' => 'St', 'is_default' => true]);

        $orderId = $this->createOrderFor($customer, $supplier, $product, $address);

        // Move through lifecycle to shipped
        $this->actingAs($sUser)->postJson("/api/v1/supplier/orders/{$orderId}/accept");
        $this->actingAs($sUser)->postJson("/api/v1/supplier/orders/{$orderId}/process");
        $this->actingAs($sUser)->postJson("/api/v1/supplier/orders/{$orderId}/ready");
        $this->actingAs($sUser)->postJson("/api/v1/supplier/orders/{$orderId}/ship");

        // Customer tries to cancel shipped order
        $response = $this->actingAs($customer)->postJson("/api/v1/orders/{$orderId}/cancel", ['reason' => 'too late']);
        $response->assertStatus(403);
    }

    public function test_order_cannot_confirm_delivery_before_shipped(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000305');
        $cat = Category::create(['name' => 'Early', 'slug' => 'early-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat);
        $customer = $this->createUser('customer', '772000305');
        $address = Address::create(['user_id' => $customer->id, 'title' => 'H', 'address' => 'St', 'is_default' => true]);

        $orderId = $this->createOrderFor($customer, $supplier, $product, $address);

        $this->actingAs($sUser)->postJson("/api/v1/supplier/orders/{$orderId}/accept");

        $response = $this->actingAs($customer)->postJson("/api/v1/orders/{$orderId}/confirm-delivery");
        $response->assertStatus(422);
    }

    public function test_order_status_history_recorded(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000306');
        $cat = Category::create(['name' => 'Hist', 'slug' => 'hist-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat);
        $customer = $this->createUser('customer', '772000306');
        $address = Address::create(['user_id' => $customer->id, 'title' => 'H', 'address' => 'St', 'is_default' => true]);

        $orderId = $this->createOrderFor($customer, $supplier, $product, $address);

        $this->actingAs($sUser)->postJson("/api/v1/supplier/orders/{$orderId}/accept");

        $order = Order::find($orderId);
        $history = $order->statusHistory;
        $this->assertGreaterThanOrEqual(2, $history->count()); // pending + confirmed
    }

    // ═══════════════════════════════════════════════════════
    // STOCK LOCKING (race condition prevention)
    // ═══════════════════════════════════════════════════════

    public function test_stock_prevents_order_when_insufficient(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000401');
        $cat = Category::create(['name' => 'Lock', 'slug' => 'lock-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat, ['stock_quantity' => 3]);
        $customer = $this->createUser('customer', '772000401');
        $address = Address::create(['user_id' => $customer->id, 'title' => 'H', 'address' => 'St', 'is_default' => true]);

        // Try to order more than stock
        \App\Models\CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'quantity' => 5,
            'unit_price' => $product->price,
            'total_price' => $product->price * 5,
        ]);

        $response = $this->actingAs($customer)->postJson('/api/v1/orders', [
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(422);
    }

    // ═══════════════════════════════════════════════════════
    // SUPPLIER ISOLATION
    // ═══════════════════════════════════════════════════════

    public function test_supplier_cannot_see_other_supplier_orders(): void
    {
        [$s1User, $s1] = $this->createSupplier('771000501');
        [$s2User, $s2] = $this->createSupplier('771000502');
        $cat = Category::create(['name' => 'Iso', 'slug' => 'iso-' . uniqid()]);
        $product = $this->createProduct($s1, $cat);
        $customer = $this->createUser('customer', '772000501');
        $address = Address::create(['user_id' => $customer->id, 'title' => 'H', 'address' => 'St', 'is_default' => true]);

        $orderId = $this->createOrderFor($customer, $s1, $product, $address);

        // Supplier 2 tries to view Supplier 1's order
        $response = $this->actingAs($s2User)->getJson("/api/v1/supplier/orders/{$orderId}");
        $response->assertStatus(403);
    }

    public function test_supplier_cannot_accept_other_supplier_order(): void
    {
        [$s1User, $s1] = $this->createSupplier('771000503');
        [$s2User, $s2] = $this->createSupplier('771000504');
        $cat = Category::create(['name' => 'Iso2', 'slug' => 'iso2-' . uniqid()]);
        $product = $this->createProduct($s1, $cat);
        $customer = $this->createUser('customer', '772000503');
        $address = Address::create(['user_id' => $customer->id, 'title' => 'H', 'address' => 'St', 'is_default' => true]);

        $orderId = $this->createOrderFor($customer, $s1, $product, $address);

        $response = $this->actingAs($s2User)->postJson("/api/v1/supplier/orders/{$orderId}/accept");
        $response->assertStatus(403);
    }

    public function test_supplier_products_only_own(): void
    {
        [$s1User, $s1] = $this->createSupplier('771000505');
        [$s2User, $s2] = $this->createSupplier('771000506');
        $cat = Category::create(['name' => 'Own', 'slug' => 'own-' . uniqid()]);
        $this->createProduct($s1, $cat);
        $this->createProduct($s2, $cat);

        $response = $this->actingAs($s1User)->getJson('/api/v1/supplier/products');
        $response->assertOk();
        $this->assertEquals(1, count($response->json('data')));
    }

    // ═══════════════════════════════════════════════════════
    // ADDRESS OWNERSHIP
    // ═══════════════════════════════════════════════════════

    public function test_address_default_logic(): void
    {
        $user = $this->createUser('customer', '772000601');

        // First address auto-defaults
        $r1 = $this->actingAs($user)->postJson('/api/v1/addresses', [
            'title' => 'Home', 'address' => 'St1',
        ]);
        $this->assertTrue($r1->json('data.is_default'));

        // Second address does not auto-default
        $r2 = $this->actingAs($user)->postJson('/api/v1/addresses', [
            'title' => 'Office', 'address' => 'St2',
        ]);
        $this->assertNotTrue($r2->json('data.is_default')); // null or false
    }

    public function test_address_cannot_update_other_user(): void
    {
        $u1 = $this->createUser('customer', '772000602');
        $u2 = $this->createUser('customer', '772000603');
        $addr = Address::create(['user_id' => $u1->id, 'title' => 'X', 'address' => 'Y']);

        $this->actingAs($u2)->putJson("/api/v1/addresses/{$addr->id}", ['title' => 'Hacked'])
            ->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════
    // PROFILE SECURITY
    // ═══════════════════════════════════════════════════════

    public function test_profile_requires_current_password_to_change(): void
    {
        $user = $this->createUser('customer', '772000701');

        $response = $this->actingAs($user)->postJson('/api/v1/profile/change-password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ]);

        $response->assertStatus(422);
    }

    public function test_profile_requires_password_to_delete(): void
    {
        $user = $this->createUser('customer', '772000702');

        $response = $this->actingAs($user)->deleteJson('/api/v1/profile', [
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_profile_delete_with_correct_password(): void
    {
        $user = $this->createUser('customer', '772000703');

        $response = $this->actingAs($user)->deleteJson('/api/v1/profile', [
            'password' => 'password123',
        ]);

        $response->assertOk();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    // ═══════════════════════════════════════════════════════
    // STATUS TRANSITIONS
    // ═══════════════════════════════════════════════════════

    public function test_supplier_cannot_ship_before_ready(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000801');
        $cat = Category::create(['name' => 'Trans', 'slug' => 'trans-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat);
        $customer = $this->createUser('customer', '772000801');
        $address = Address::create(['user_id' => $customer->id, 'title' => 'H', 'address' => 'St', 'is_default' => true]);

        $orderId = $this->createOrderFor($customer, $supplier, $product, $address);
        $this->actingAs($sUser)->postJson("/api/v1/supplier/orders/{$orderId}/accept");
        $this->actingAs($sUser)->postJson("/api/v1/supplier/orders/{$orderId}/process");

        // Try to ship (should fail, must be ready first)
        $response = $this->actingAs($sUser)->postJson("/api/v1/supplier/orders/{$orderId}/ship");
        $response->assertStatus(403);
    }

    public function test_supplier_cannot_process_before_confirmed(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000802');
        $cat = Category::create(['name' => 'Trans2', 'slug' => 'trans2-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat);
        $customer = $this->createUser('customer', '772000802');
        $address = Address::create(['user_id' => $customer->id, 'title' => 'H', 'address' => 'St', 'is_default' => true]);

        $orderId = $this->createOrderFor($customer, $supplier, $product, $address);

        // Try to process (should fail, must be confirmed first)
        $response = $this->actingAs($sUser)->postJson("/api/v1/supplier/orders/{$orderId}/process");
        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════
    // ORDER NUMBER UNIQUENESS (concurrent)
    // ═══════════════════════════════════════════════════════

    public function test_order_numbers_unique_under_rapid_creation(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771000901');
        $cat = Category::create(['name' => 'Uniq', 'slug' => 'uniq-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat, ['stock_quantity' => 1000]);

        $numbers = [];
        for ($i = 0; $i < 10; $i++) {
            $customer = $this->createUser('customer', "7720009{$i}");
            $address = Address::create(['user_id' => $customer->id, 'title' => "H{$i}", 'address' => "S{$i}", 'is_default' => true]);
            \App\Models\CartItem::create([
                'user_id' => $customer->id,
                'product_id' => $product->id,
                'supplier_id' => $supplier->id,
                'quantity' => 1,
                'unit_price' => 500,
                'total_price' => 500,
            ]);
            $r = $this->actingAs($customer)->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'cash',
            ]);
            $numbers[] = $r->json('data.order_number');
        }

        $this->assertCount(10, array_unique($numbers), 'Duplicate order numbers: ' . implode(', ', $numbers));
    }

    // ═══════════════════════════════════════════════════════
    // EDGE CASES
    // ═══════════════════════════════════════════════════════

    public function test_empty_cart_order_fails(): void
    {
        $customer = $this->createUser('customer', '772001001');
        $address = Address::create(['user_id' => $customer->id, 'title' => 'H', 'address' => 'St', 'is_default' => true]);

        $response = $this->actingAs($customer)->postJson('/api/v1/orders', [
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(422);
    }

    public function test_order_with_invalid_address_fails(): void
    {
        $customer = $this->createUser('customer', '772001002');

        $response = $this->actingAs($customer)->postJson('/api/v1/orders', [
            'address_id' => 99999,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(422);
    }

    public function test_order_with_other_users_address_fails(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771001003');
        $cat = Category::create(['name' => 'Edge', 'slug' => 'edge-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat);
        $u1 = $this->createUser('customer', '772001003');
        $u2 = $this->createUser('customer', '772001004');
        $address = Address::create(['user_id' => $u2->id, 'title' => 'H', 'address' => 'St', 'is_default' => true]);

        \App\Models\CartItem::create([
            'user_id' => $u1->id,
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'quantity' => 1,
            'unit_price' => $product->price,
            'total_price' => $product->price,
        ]);

        $response = $this->actingAs($u1)->postJson('/api/v1/orders', [
            'address_id' => $address->id,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(404); // Address not found for this user
    }

    public function test_category_with_children_count(): void
    {
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent-' . uniqid()]);
        Category::create(['name' => 'Child1', 'slug' => 'child1-' . uniqid(), 'parent_id' => $parent->id]);
        Category::create(['name' => 'Child2', 'slug' => 'child2-' . uniqid(), 'parent_id' => $parent->id]);

        $response = $this->getJson('/api/v1/categories');
        $response->assertOk();
    }

    public function test_supplier_product_stock_update(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771001101');
        $cat = Category::create(['name' => 'Stock', 'slug' => 'stock-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat, ['stock_quantity' => 50]);

        // Set stock
        $this->actingAs($sUser)->patchJson("/api/v1/supplier/products/{$product->id}/stock", [
            'quantity' => 100, 'action' => 'set',
        ])->assertOk();

        $product->refresh();
        $this->assertEquals(100, $product->stock_quantity);

        // Add stock
        $this->actingAs($sUser)->patchJson("/api/v1/supplier/products/{$product->id}/stock", [
            'quantity' => 25, 'action' => 'add',
        ])->assertOk();

        $product->refresh();
        $this->assertEquals(125, $product->stock_quantity);

        // Subtract stock
        $this->actingAs($sUser)->patchJson("/api/v1/supplier/products/{$product->id}/stock", [
            'quantity' => 10, 'action' => 'subtract',
        ])->assertOk();

        $product->refresh();
        $this->assertEquals(115, $product->stock_quantity);
    }

    public function test_views_increment_on_product_show(): void
    {
        [$sUser, $supplier] = $this->createSupplier('771001201');
        $cat = Category::create(['name' => 'Views', 'slug' => 'views-' . uniqid()]);
        $product = $this->createProduct($supplier, $cat, ['views_count' => 0]);

        $this->getJson("/api/v1/products/{$product->id}");
        $this->getJson("/api/v1/products/{$product->id}");

        $product->refresh();
        $this->assertEquals(2, $product->views_count);
    }
}
