<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function update(User $user, Product $product): bool
    {
        return $user->supplier && $user->supplier->id === $product->supplier_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->supplier && $user->supplier->id === $product->supplier_id;
    }

    public function updateStock(User $user, Product $product): bool
    {
        return $user->supplier && $user->supplier->id === $product->supplier_id;
    }
}
