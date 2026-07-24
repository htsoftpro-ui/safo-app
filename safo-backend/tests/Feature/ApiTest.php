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

class ApiTest extends TestCase
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
        ]);
        return [$user, $supplier];
    }

    private function createProduct(Supplier $supplier, Category $category): Product
    {
        return Product::create([
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product-' . uniqid(),
            'price' => 1000,
            'unit' => 'piece',
            'stock_quantity' => 50,
            'min_order_quantity' => 1,
            'is_active' => true,
        ]);
    }

    // ─── Authentication Tests ────────────────────────────

    public function test_register_success(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'new_user',
            'phone' => '779000001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_register_duplicate_phone(): void
    {
        $this->createUser('customer', '779000001');

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'dup_user',
            'phone' => '779000001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_register_validation_failure(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'phone' => '123',
            'password' => '12',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'password']);
    }

    public function test_login_success(): void
    {
        $this->createUser('customer', '779000002');

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '779000002',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_wrong_password(): void
    {
        $this->createUser('customer', '779000003');

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '779000003',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_login_inactive_user(): void
    {
        $user = $this->createUser('customer', '779000004');
        $user->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '779000004',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_logout(): void
    {
        $user = $this->createUser('customer', '779000005');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_unauthenticated_access(): void
    {
        $response = $this->getJson('/api/v1/orders');
        $response->assertStatus(401);
    }

    // ─── Products Tests ──────────────────────────────────

    public function test_products_index(): void
    {
        [$supplierUser, $supplier] = $this->createSupplier('771000010');
        $category = Category::create(['name' => 'Test Cat', 'slug' => 'test-cat-' . uniqid()]);
        $this->createProduct($supplier, $category);

        $response = $this->getJson('/api/v1/products');
        $response->assertOk();
    }

    public function test_products_search(): void
    {
        [$supplierUser, $supplier] = $this->createSupplier('771000011');
        $category = Category::create(['name' => 'Food', 'slug' => 'food-' . uniqid()]);
        Product::create([
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'name' => 'Rice Premium',
            'slug' => 'rice-premium-' . uniqid(),
            'price' => 2000,
            'unit' => 'bag',
            'stock_quantity' => 100,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/products/search?q=Rice');
        $response->assertOk();
    }

    public function test_product_show(): void
    {
        [$supplierUser, $supplier] = $this->createSupplier('771000012');
        $category = Category::create(['name' => 'Cat2', 'slug' => 'cat2-' . uniqid()]);
        $product = $this->createProduct($supplier, $category);

        $response = $this->getJson("/api/v1/products/{$product->id}");
        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    // ─── Categories Tests ────────────────────────────────

    public function test_categories_index(): void
    {
        Category::create(['name' => 'Food', 'slug' => 'food-' . uniqid()]);

        $response = $this->getJson('/api/v1/categories');
        $response->assertOk();
    }

    // ─── Cart Tests ──────────────────────────────────────

    public function test_cart_add_and_view(): void
    {
        [$supplierUser, $supplier] = $this->createSupplier('771000020');
        $category = Category::create(['name' => 'CartCat', 'slug' => 'cart-cat-' . uniqid()]);
        $product = $this->createProduct($supplier, $category);
        $user = $this->createUser('customer', '772000020');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/cart', [
                'product_id' => $product->id,
                'quantity' => 3,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/cart');

        $response->assertOk()
            ->assertJsonPath('data.items_count', 3);
    }

    public function test_cart_update_quantity(): void
    {
        [$supplierUser, $supplier] = $this->createSupplier('771000021');
        $category = Category::create(['name' => 'CatU', 'slug' => 'catu-' . uniqid()]);
        $product = $this->createProduct($supplier, $category);
        $user = $this->createUser('customer', '772000021');

        $this->actingAs($user)
            ->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 2]);

        $cartItem = \App\Models\CartItem::where('user_id', $user->id)->first();

        $response = $this->actingAs($user)
            ->putJson("/api/v1/cart/{$cartItem->id}", ['quantity' => 5]);

        $response->assertOk();
    }

    public function test_cart_clear(): void
    {
        [$supplierUser, $supplier] = $this->createSupplier('771000022');
        $category = Category::create(['name' => 'CatC', 'slug' => 'catc-' . uniqid()]);
        $product = $this->createProduct($supplier, $category);
        $user = $this->createUser('customer', '772000022');

        $this->actingAs($user)
            ->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 2]);

        $response = $this->actingAs($user)
            ->deleteJson('/api/v1/cart');

        $response->assertOk();
    }

    // ─── Address Tests ───────────────────────────────────

    public function test_address_crud(): void
    {
        $user = $this->createUser('customer', '772000030');

        $response = $this->actingAs($user)->postJson('/api/v1/addresses', [
            'title' => 'Home',
            'address' => 'Zubairi St',
            'city' => 'Sanaa',
        ]);
        $response->assertStatus(201);
        $addressId = $response->json('data.id');

        $response = $this->actingAs($user)->getJson('/api/v1/addresses');
        $response->assertOk();

        $response = $this->actingAs($user)->putJson("/api/v1/addresses/{$addressId}", [
            'title' => 'Office',
        ]);
        $response->assertOk();

        $response = $this->actingAs($user)->deleteJson("/api/v1/addresses/{$addressId}");
        $response->assertOk();
    }

    public function test_address_forbidden_other_user(): void
    {
        $user1 = $this->createUser('customer', '772000031');
        $user2 = $this->createUser('customer', '772000032');

        $address = Address::create([
            'user_id' => $user1->id,
            'title' => 'Secret',
            'address' => 'Hidden place',
        ]);

        $response = $this->actingAs($user2)
            ->putJson("/api/v1/addresses/{$address->id}", ['title' => 'Hacked']);

        $response->assertStatus(403);
    }

    // ─── Order Tests ─────────────────────────────────────

    public function test_order_full_lifecycle(): void
    {
        [$supplierUser, $supplier] = $this->createSupplier('771000040');
        $category = Category::create(['name' => 'OrdCat', 'slug' => 'ordcat-' . uniqid()]);
        $product = $this->createProduct($supplier, $category);
        $customer = $this->createUser('customer', '772000040');

        $address = Address::create([
            'user_id' => $customer->id,
            'title' => 'Home',
            'address' => 'Test St',
            'city' => 'Sanaa',
            'is_default' => true,
        ]);

        \App\Models\CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'quantity' => 2,
            'unit_price' => $product->price,
            'total_price' => $product->price * 2,
        ]);

        // Create order
        $response = $this->actingAs($customer)
            ->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
        $orderId = $response->json('data.id');

        // Supplier accepts
        $this->actingAs($supplierUser)
            ->postJson("/api/v1/supplier/orders/{$orderId}/accept")
            ->assertOk();

        // Supplier processes
        $this->actingAs($supplierUser)
            ->postJson("/api/v1/supplier/orders/{$orderId}/process")
            ->assertOk();

        // Supplier marks ready
        $this->actingAs($supplierUser)
            ->postJson("/api/v1/supplier/orders/{$orderId}/ready")
            ->assertOk();

        // Supplier ships
        $this->actingAs($supplierUser)
            ->postJson("/api/v1/supplier/orders/{$orderId}/ship")
            ->assertOk();

        // Customer confirms delivery
        $this->actingAs($customer)
            ->postJson("/api/v1/orders/{$orderId}/confirm-delivery")
            ->assertOk();

        // Verify final status
        $order = Order::find($orderId);
        $this->assertEquals('delivered', $order->status);
        $this->assertNotNull($order->delivered_at);
    }

    public function test_order_cancel(): void
    {
        [$supplierUser, $supplier] = $this->createSupplier('771000041');
        $category = Category::create(['name' => 'CanCat', 'slug' => 'cancat-' . uniqid()]);
        $product = $this->createProduct($supplier, $category);
        $customer = $this->createUser('customer', '772000041');

        $address = Address::create([
            'user_id' => $customer->id,
            'title' => 'Home',
            'address' => 'Test St',
            'is_default' => true,
        ]);

        \App\Models\CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'quantity' => 1,
            'unit_price' => $product->price,
            'total_price' => $product->price,
        ]);

        $response = $this->actingAs($customer)
            ->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'cash',
            ]);

        $orderId = $response->json('data.id');

        $response = $this->actingAs($customer)
            ->postJson("/api/v1/orders/{$orderId}/cancel", [
                'reason' => 'Changed my mind',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_order_forbidden_other_user(): void
    {
        [$supplierUser, $supplier] = $this->createSupplier('771000042');
        $category = Category::create(['name' => 'ForbCat', 'slug' => 'forbcat-' . uniqid()]);
        $product = $this->createProduct($supplier, $category);
        $customer = $this->createUser('customer', '772000042');
        $other = $this->createUser('customer', '772000043');

        $address = Address::create([
            'user_id' => $customer->id,
            'title' => 'Home',
            'address' => 'Test St',
            'is_default' => true,
        ]);

        \App\Models\CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'quantity' => 1,
            'unit_price' => $product->price,
            'total_price' => $product->price,
        ]);

        $response = $this->actingAs($customer)
            ->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'cash',
            ]);

        $orderId = $response->json('data.id');

        // Other user tries to view
        $response = $this->actingAs($other)
            ->getJson("/api/v1/orders/{$orderId}");

        $response->assertStatus(403);
    }

    // ─── Supplier Authorization Tests ────────────────────

    public function test_supplier_cannot_update_other_product(): void
    {
        [$supplier1User, $supplier1] = $this->createSupplier('771000050');
        [$supplier2User, $supplier2] = $this->createSupplier('771000051');
        $category = Category::create(['name' => 'AuthCat', 'slug' => 'authcat-' . uniqid()]);
        $product = $this->createProduct($supplier1, $category);

        $response = $this->actingAs($supplier2User)
            ->putJson("/api/v1/supplier/products/{$product->id}", [
                'name' => 'Hacked Product',
            ]);

        $response->assertStatus(403);
    }

    public function test_supplier_cannot_accept_already_accepted_order(): void
    {
        [$supplierUser, $supplier] = $this->createSupplier('771000052');
        $category = Category::create(['name' => 'TransCat', 'slug' => 'transcat-' . uniqid()]);
        $product = $this->createProduct($supplier, $category);
        $customer = $this->createUser('customer', '772000052');

        $address = Address::create([
            'user_id' => $customer->id,
            'title' => 'Home',
            'address' => 'Test',
            'is_default' => true,
        ]);

        \App\Models\CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'total_price' => 1000,
        ]);

        $response = $this->actingAs($customer)
            ->postJson('/api/v1/orders', [
                'address_id' => $address->id,
                'payment_method' => 'cash',
            ]);

        $orderId = $response->json('data.id');

        // Accept once
        $this->actingAs($supplierUser)
            ->postJson("/api/v1/supplier/orders/{$orderId}/accept")
            ->assertOk();

        // Try to accept again — should fail (not pending anymore)
        $response = $this->actingAs($supplierUser)
            ->postJson("/api/v1/supplier/orders/{$orderId}/accept");

        $response->assertStatus(403);
    }

    // ─── Profile Tests ───────────────────────────────────

    public function test_profile_update(): void
    {
        $user = $this->createUser('customer', '772000060');

        $response = $this->actingAs($user)
            ->putJson('/api/v1/profile', ['name' => 'Updated Name']);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_profile_change_password(): void
    {
        $user = $this->createUser('customer', '772000061');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/profile/change-password', [
                'current_password' => 'password123',
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ]);

        $response->assertOk();
    }

    // ─── Order Number Uniqueness Test ────────────────────

    public function test_order_numbers_are_unique(): void
    {
        [$supplierUser, $supplier] = $this->createSupplier('771000070');
        $category = Category::create(['name' => 'UniqCat', 'slug' => 'uniqcat-' . uniqid()]);

        Product::create([
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'name' => 'Bulk Product',
            'slug' => 'bulk-product-' . uniqid(),
            'price' => 500,
            'unit' => 'piece',
            'stock_quantity' => 1000,
            'is_active' => true,
        ]);

        $numbers = [];
        for ($i = 0; $i < 5; $i++) {
            $customer = $this->createUser('customer', "77200007{$i}");
            $address = Address::create([
                'user_id' => $customer->id,
                'title' => "Home {$i}",
                'address' => "St {$i}",
                'is_default' => true,
            ]);

            $product = Product::where('supplier_id', $supplier->id)->first();

            \App\Models\CartItem::create([
                'user_id' => $customer->id,
                'product_id' => $product->id,
                'supplier_id' => $supplier->id,
                'quantity' => 1,
                'unit_price' => 500,
                'total_price' => 500,
            ]);

            $response = $this->actingAs($customer)
                ->postJson('/api/v1/orders', [
                    'address_id' => $address->id,
                    'payment_method' => 'cash',
                ]);

            $this->assertNotNull($response->json('data.order_number'), 'Order creation failed: ' . $response->json('message'));
            $numbers[] = $response->json('data.order_number');
        }

        $this->assertCount(5, array_unique($numbers), 'Order numbers are not unique! Got: ' . implode(', ', $numbers));
    }
}
