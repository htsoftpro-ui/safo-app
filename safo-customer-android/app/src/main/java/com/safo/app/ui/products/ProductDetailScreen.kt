package com.safo.app.ui.products

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import coil.compose.AsyncImage
import com.safo.app.data.api.SafoApi
import com.safo.app.data.model.AddToCartRequest
import com.safo.app.data.model.Product
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ProductDetailViewModel @Inject constructor(
    private val api: SafoApi,
    savedStateHandle: SavedStateHandle
) : ViewModel() {
    private val productId: Int = savedStateHandle.get<Int>("id") ?: 0

    data class UiState(
        val product: Product? = null,
        val isLoading: Boolean = true,
        val error: String? = null,
        val addingToCart: Boolean = false,
        val addedToCart: Boolean = false,
        val cartError: String? = null
    )

    private val _state = MutableStateFlow(UiState())
    val state: StateFlow<UiState> = _state

    init { load() }

    fun load() {
        viewModelScope.launch {
            _state.value = UiState(isLoading = true)
            try {
                val response = api.getProduct(productId)
                if (response.isSuccessful && response.body()?.success == true) {
                    _state.value = UiState(product = response.body()!!.data)
                } else {
                    _state.value = UiState(error = "المنتج غير موجود")
                }
            } catch (e: Exception) {
                _state.value = UiState(error = "خطأ: ${e.message}")
            }
        }
    }

    fun addToCart(quantity: Int) {
        viewModelScope.launch {
            _state.value = _state.value.copy(addingToCart = true, cartError = null)
            try {
                val response = api.addToCart(AddToCartRequest(productId, quantity))
                if (response.isSuccessful && response.body()?.success == true) {
                    _state.value = _state.value.copy(addingToCart = false, addedToCart = true)
                } else {
                    _state.value = _state.value.copy(addingToCart = false, cartError = response.body()?.message ?: "فشل الإضافة")
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(addingToCart = false, cartError = "خطأ: ${e.message}")
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProductDetailScreen(
    viewModel: ProductDetailViewModel = hiltViewModel(),
    onBack: () -> Unit,
    onCartClick: () -> Unit
) {
    val state by viewModel.state.collectAsState()
    var quantity by remember { mutableIntStateOf(1) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(state.product?.name ?: "تفاصيل المنتج") },
                navigationIcon = { IconButton(onClick = onBack) { Icon(Icons.Default.ArrowBack, "رجوع") } },
                actions = { IconButton(onClick = onCartClick) { Icon(Icons.Default.ShoppingCart, "السلة") } }
            )
        }
    ) { padding ->
        when {
            state.isLoading -> Box(Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) { CircularProgressIndicator() }
            state.error != null -> Box(Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(state.error!!, color = MaterialTheme.colorScheme.error)
                    Button(onClick = { viewModel.load() }) { Text("إعادة المحاولة") }
                }
            }
            state.product != null -> {
                val product = state.product!!
                Column(
                    modifier = Modifier.fillMaxSize().padding(padding).verticalScroll(rememberScrollState())
                ) {
                    // Image
                    AsyncImage(
                        model = product.thumbnail ?: product.images?.firstOrNull(),
                        contentDescription = product.name,
                        modifier = Modifier.fillMaxWidth().height(300.dp).clip(MaterialTheme.shapes.large),
                        contentScale = ContentScale.Crop
                    )

                    Column(Modifier.padding(16.dp)) {
                        // Name + Supplier
                        Text(product.name, style = MaterialTheme.typography.headlineSmall)
                        if (product.supplier != null) {
                            Text(product.supplier.companyName, style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            if (product.supplier.isVerified) {
                                Text("✓ مورد موثق", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.primary)
                            }
                        }

                        Spacer(Modifier.height(12.dp))

                        // Price
                        Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer)) {
                            Row(Modifier.padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
                                Column(Modifier.weight(1f)) {
                                    Text("السعر", style = MaterialTheme.typography.labelMedium)
                                    Text(
                                        "${String.format("%.0f", product.price)} ﷼ / ${product.unit}",
                                        style = MaterialTheme.typography.headlineSmall,
                                        color = MaterialTheme.colorScheme.primary
                                    )
                                    if (product.comparePrice != null && product.comparePrice > product.price) {
                                        Text(
                                            "${String.format("%.0f", product.comparePrice)} ﷼",
                                            style = MaterialTheme.typography.bodySmall,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }
                                if (product.discountPercent > 0) {
                                    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.error)) {
                                        Text(
                                            "${product.discountPercent}% خصم",
                                            Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                                            color = MaterialTheme.colorScheme.onError
                                        )
                                    }
                                }
                            }
                        }

                        Spacer(Modifier.height(12.dp))

                        // Stock
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(
                                if (product.isOutOfStock) Icons.Default.Error else Icons.Default.CheckCircle,
                                null,
                                tint = if (product.isOutOfStock) MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.primary
                            )
                            Spacer(Modifier.width(8.dp))
                            Text(
                                if (product.isOutOfStock) "غير متوفر"
                                else if (product.isLowStock) "مخزون محدود (${product.stockQuantity} ${product.unit})"
                                else "متوفر (${product.stockQuantity} ${product.unit})"
                            )
                        }

                        // Min order
                        if (product.minOrderQuantity > 1) {
                            Text("الحد الأدنى للطلب: ${product.minOrderQuantity} ${product.unit}", style = MaterialTheme.typography.bodySmall)
                        }

                        Spacer(Modifier.height(12.dp))

                        // Description
                        if (!product.description.isNullOrBlank()) {
                            Text("الوصف", style = MaterialTheme.typography.titleMedium)
                            Text(product.description, style = MaterialTheme.typography.bodyMedium)
                            Spacer(Modifier.height(12.dp))
                        }

                        // Rating
                        if (product.totalRatings > 0) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Default.Star, null, tint = MaterialTheme.colorScheme.tertiary)
                                Text(" ${product.rating} (${product.totalRatings} تقييم)")
                            }
                            Spacer(Modifier.height(12.dp))
                        }

                        // Quantity selector
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Text("الكمية:", style = MaterialTheme.typography.titleMedium)
                            Spacer(Modifier.weight(1f))
                            IconButton(onClick = { if (quantity > product.minOrderQuantity) quantity-- }) {
                                Icon(Icons.Default.Remove, "تقليل")
                            }
                            Text("$quantity", style = MaterialTheme.typography.titleLarge, modifier = Modifier.padding(horizontal = 16.dp))
                            IconButton(onClick = { if (quantity < product.stockQuantity) quantity++ }) {
                                Icon(Icons.Default.Add, "زيادة")
                            }
                        }

                        Spacer(Modifier.height(16.dp))

                        // Add to cart button
                        Button(
                            onClick = { viewModel.addToCart(quantity) },
                            enabled = !product.isOutOfStock && !state.addingToCart,
                            modifier = Modifier.fillMaxWidth().height(50.dp)
                        ) {
                            if (state.addingToCart) {
                                CircularProgressIndicator(Modifier.size(24.dp), color = MaterialTheme.colorScheme.onPrimary)
                            } else {
                                Icon(Icons.Default.ShoppingCart, null)
                                Spacer(Modifier.width(8.dp))
                                Text("إضافة للسلة — ${String.format("%.0f", product.price * quantity)} ﷼")
                            }
                        }

                        if (state.addedToCart) {
                            Spacer(Modifier.height(8.dp))
                            Text("✓ تمت الإضافة للسلة", color = MaterialTheme.colorScheme.primary, style = MaterialTheme.typography.bodyMedium)
                        }

                        if (state.cartError != null) {
                            Spacer(Modifier.height(8.dp))
                            Text(state.cartError!!, color = MaterialTheme.colorScheme.error)
                        }
                    }
                }
            }
        }
    }
}
