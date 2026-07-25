package com.safo.app.data.model

import com.google.gson.annotations.SerializedName

// ─── API Response Wrappers ───────────────────────────

data class ApiResponse<T>(
    val success: Boolean,
    val message: String? = null,
    val data: T? = null,
    val errors: Map<String, List<String>>? = null
)

data class PaginatedResponse<T>(
    val data: List<T>,
    val meta: PaginationMeta? = null
)

data class PaginationMeta(
    @SerializedName("current_page") val currentPage: Int,
    @SerializedName("last_page") val lastPage: Int,
    @SerializedName("per_page") val perPage: Int,
    val total: Int
)

// ─── Auth ────────────────────────────────────────────

data class LoginRequest(val phone: String, val password: String)

data class RegisterRequest(
    val name: String,
    val phone: String,
    val password: String,
    @SerializedName("password_confirmation") val passwordConfirmation: String,
    val city: String? = null
)

data class AuthData(
    val user: User,
    val token: String,
    @SerializedName("token_type") val tokenType: String
)

// ─── User ────────────────────────────────────────────

data class User(
    val id: Int,
    val name: String,
    val phone: String,
    val type: String,
    @SerializedName("store_name") val storeName: String? = null,
    val city: String? = null,
    @SerializedName("is_verified") val isVerified: Boolean = false
)

// ─── Category ────────────────────────────────────────

data class Category(
    val id: Int,
    val name: String,
    @SerializedName("name_en") val nameEn: String? = null,
    val slug: String,
    val icon: String? = null,
    @SerializedName("children_count") val childrenCount: Int? = null,
    @SerializedName("products_count") val productsCount: Int? = null,
    val children: List<Category>? = null
)

// ─── Product ─────────────────────────────────────────

data class Product(
    val id: Int,
    val name: String,
    @SerializedName("name_en") val nameEn: String? = null,
    val slug: String,
    val description: String? = null,
    val price: Double,
    @SerializedName("compare_price") val comparePrice: Double? = null,
    @SerializedName("discount_percent") val discountPercent: Int = 0,
    val unit: String,
    @SerializedName("unit_quantity") val unitQuantity: Int = 1,
    @SerializedName("min_order_quantity") val minOrderQuantity: Int = 1,
    @SerializedName("stock_quantity") val stockQuantity: Int = 0,
    @SerializedName("is_low_stock") val isLowStock: Boolean = false,
    @SerializedName("is_out_of_stock") val isOutOfStock: Boolean = false,
    val images: List<String>? = null,
    val thumbnail: String? = null,
    @SerializedName("is_featured") val isFeatured: Boolean = false,
    @SerializedName("views_count") val viewsCount: Int = 0,
    @SerializedName("sales_count") val salesCount: Int = 0,
    val rating: Double = 0.0,
    @SerializedName("total_ratings") val totalRatings: Int = 0,
    val supplier: Supplier? = null,
    val category: Category? = null
)

data class Supplier(
    val id: Int,
    @SerializedName("company_name") val companyName: String,
    val logo: String? = null,
    val rating: Double = 0.0,
    @SerializedName("is_verified") val isVerified: Boolean = false
)

// ─── Cart ────────────────────────────────────────────

data class CartItem(
    val id: Int,
    @SerializedName("product_id") val productId: Int,
    val quantity: Int,
    @SerializedName("unit_price") val unitPrice: Double,
    @SerializedName("total_price") val totalPrice: Double,
    val notes: String? = null,
    val product: Product? = null,
    val supplier: Supplier? = null
)

data class CartData(
    val items: List<CartItem>,
    @SerializedName("items_count") val itemsCount: Int,
    val subtotal: Double
)

data class AddToCartRequest(
    @SerializedName("product_id") val productId: Int,
    val quantity: Int,
    val notes: String? = null
)

data class UpdateCartRequest(val quantity: Int)

// ─── Address ─────────────────────────────────────────

data class Address(
    val id: Int,
    val title: String,
    val address: String,
    val city: String? = null,
    val area: String? = null,
    val building: String? = null,
    val floor: String? = null,
    val apartment: String? = null,
    val landmark: String? = null,
    val latitude: Double? = null,
    val longitude: Double? = null,
    @SerializedName("is_default") val isDefault: Boolean = false,
    @SerializedName("full_address") val fullAddress: String? = null
)

data class CreateAddressRequest(
    val title: String,
    val address: String,
    val city: String? = null,
    val area: String? = null,
    @SerializedName("is_default") val isDefault: Boolean = false
)

// ─── Order ───────────────────────────────────────────

data class Order(
    val id: Int,
    @SerializedName("order_number") val orderNumber: String,
    val status: String,
    @SerializedName("status_label") val statusLabel: String,
    val subtotal: Double,
    @SerializedName("delivery_fee") val deliveryFee: Double,
    @SerializedName("discount_amount") val discountAmount: Double,
    @SerializedName("total_amount") val totalAmount: Double,
    @SerializedName("payment_method") val paymentMethod: String,
    @SerializedName("payment_status") val paymentStatus: String,
    @SerializedName("delivery_address") val deliveryAddress: String? = null,
    @SerializedName("delivery_notes") val deliveryNotes: String? = null,
    @SerializedName("items_count") val itemsCount: Int = 0,
    val items: List<OrderItem>? = null,
    val supplier: Supplier? = null,
    @SerializedName("status_history") val statusHistory: List<StatusHistory>? = null,
    @SerializedName("confirmed_at") val confirmedAt: String? = null,
    @SerializedName("shipped_at") val shippedAt: String? = null,
    @SerializedName("delivered_at") val deliveredAt: String? = null,
    @SerializedName("created_at") val createdAt: String
)

data class OrderItem(
    val id: Int,
    @SerializedName("product_id") val productId: Int,
    @SerializedName("product_name") val productName: String,
    @SerializedName("product_image") val productImage: String? = null,
    @SerializedName("product_unit") val productUnit: String,
    val quantity: Int,
    @SerializedName("unit_price") val unitPrice: Double,
    @SerializedName("total_price") val totalPrice: Double
)

data class StatusHistory(
    @SerializedName("from_status") val fromStatus: String? = null,
    @SerializedName("to_status") val toStatus: String,
    val note: String? = null,
    @SerializedName("changed_by") val changedBy: ChangedBy? = null,
    @SerializedName("created_at") val createdAt: String
)

data class ChangedBy(
    val id: Int,
    val name: String,
    val type: String
)

data class CreateOrderRequest(
    @SerializedName("address_id") val addressId: Int,
    @SerializedName("payment_method") val paymentMethod: String = "cash"
)
