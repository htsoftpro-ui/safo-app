package com.safo.app.data.api

import com.safo.app.data.model.*
import retrofit2.Response
import retrofit2.http.*

interface SafoApi {

    // ─── Auth ────────────────────────────────────────

    @POST("auth/login")
    suspend fun login(@Body request: LoginRequest): Response<ApiResponse<AuthData>>

    @POST("auth/register")
    suspend fun register(@Body request: RegisterRequest): Response<ApiResponse<AuthData>>

    @POST("auth/logout")
    suspend fun logout(): Response<ApiResponse<Unit>>

    @GET("auth/me")
    suspend fun getMe(): Response<ApiResponse<User>>

    // ─── Categories ──────────────────────────────────

    @GET("categories")
    suspend fun getCategories(): Response<ApiResponse<List<Category>>>

    @GET("categories/{id}")
    suspend fun getCategory(@Path("id") id: Int): Response<ApiResponse<Category>>

    @GET("categories/{id}/products")
    suspend fun getCategoryProducts(
        @Path("id") id: Int,
        @Query("page") page: Int? = null,
        @Query("per_page") perPage: Int? = null
    ): Response<PaginatedResponse<Product>>

    // ─── Products ────────────────────────────────────

    @GET("products")
    suspend fun getProducts(
        @Query("category_id") categoryId: Int? = null,
        @Query("min_price") minPrice: Double? = null,
        @Query("max_price") maxPrice: Double? = null,
        @Query("search") search: String? = null,
        @Query("sort") sort: String? = null,
        @Query("order") order: String? = null,
        @Query("page") page: Int? = null,
        @Query("per_page") perPage: Int? = null
    ): Response<PaginatedResponse<Product>>

    @GET("products/featured")
    suspend fun getFeaturedProducts(
        @Query("page") page: Int? = null
    ): Response<PaginatedResponse<Product>>

    @GET("products/new-arrivals")
    suspend fun getNewArrivals(
        @Query("page") page: Int? = null
    ): Response<PaginatedResponse<Product>>

    @GET("products/best-sellers")
    suspend fun getBestSellers(
        @Query("page") page: Int? = null
    ): Response<PaginatedResponse<Product>>

    @GET("products/search")
    suspend fun searchProducts(
        @Query("q") query: String,
        @Query("category_id") categoryId: Int? = null,
        @Query("page") page: Int? = null
    ): Response<PaginatedResponse<Product>>

    @GET("products/{id}")
    suspend fun getProduct(@Path("id") id: Int): Response<ApiResponse<Product>>

    // ─── Cart ────────────────────────────────────────

    @GET("cart")
    suspend fun getCart(): Response<ApiResponse<CartData>>

    @POST("cart")
    suspend fun addToCart(@Body request: AddToCartRequest): Response<ApiResponse<CartItem>>

    @PUT("cart/{id}")
    suspend fun updateCartItem(
        @Path("id") id: Int,
        @Body request: UpdateCartRequest
    ): Response<ApiResponse<CartItem>>

    @DELETE("cart/{id}")
    suspend fun removeCartItem(@Path("id") id: Int): Response<ApiResponse<Unit>>

    @DELETE("cart")
    suspend fun clearCart(): Response<ApiResponse<Unit>>

    // ─── Addresses ───────────────────────────────────

    @GET("addresses")
    suspend fun getAddresses(): Response<ApiResponse<List<Address>>>

    @POST("addresses")
    suspend fun createAddress(@Body request: CreateAddressRequest): Response<ApiResponse<Address>>

    @PUT("addresses/{id}")
    suspend fun updateAddress(
        @Path("id") id: Int,
        @Body request: CreateAddressRequest
    ): Response<ApiResponse<Address>>

    @DELETE("addresses/{id}")
    suspend fun deleteAddress(@Path("id") id: Int): Response<ApiResponse<Unit>>

    // ─── Orders ──────────────────────────────────────

    @GET("orders")
    suspend fun getOrders(
        @Query("status") status: String? = null,
        @Query("page") page: Int? = null
    ): Response<PaginatedResponse<Order>>

    @POST("orders")
    suspend fun createOrder(@Body request: CreateOrderRequest): Response<ApiResponse<Order>>

    @GET("orders/{id}")
    suspend fun getOrder(@Path("id") id: Int): Response<ApiResponse<Order>>

    @POST("orders/{id}/cancel")
    suspend fun cancelOrder(
        @Path("id") id: Int,
        @Body reason: Map<String, String>
    ): Response<ApiResponse<Order>>

    @POST("orders/{id}/confirm-delivery")
    suspend fun confirmDelivery(@Path("id") id: Int): Response<ApiResponse<Order>>

    // ─── Profile ─────────────────────────────────────

    @GET("profile")
    suspend fun getProfile(): Response<ApiResponse<User>>

    @PUT("profile")
    suspend fun updateProfile(@Body data: Map<String, String>): Response<ApiResponse<User>>

    @POST("profile/change-password")
    suspend fun changePassword(@Body data: Map<String, String>): Response<ApiResponse<Unit>>
}
