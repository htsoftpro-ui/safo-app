package com.safo.app.ui.cart

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
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
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import coil.compose.AsyncImage
import com.safo.app.data.api.SafoApi
import com.safo.app.data.model.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class CartUiState(
    val items: List<CartItem> = emptyList(),
    val subtotal: Double = 0.0,
    val itemsCount: Int = 0,
    val isLoading: Boolean = true,
    val error: String? = null,
    val addresses: List<Address> = emptyList(),
    val checkingOut: Boolean = false,
    val checkoutSuccess: Boolean = false,
    val checkoutError: String? = null
)

@HiltViewModel
class CartViewModel @Inject constructor(private val api: SafoApi) : ViewModel() {
    private val _state = MutableStateFlow(CartUiState())
    val state: StateFlow<CartUiState> = _state

    init { load() }

    fun load() {
        viewModelScope.launch {
            _state.value = _state.value.copy(isLoading = true)
            try {
                val cart = api.getCart()
                val addresses = api.getAddresses()
                _state.value = _state.value.copy(
                    items = cart.body()?.data?.items ?: emptyList(),
                    subtotal = cart.body()?.data?.subtotal ?: 0.0,
                    itemsCount = cart.body()?.data?.itemsCount ?: 0,
                    addresses = addresses.body()?.data ?: emptyList(),
                    isLoading = false
                )
            } catch (e: Exception) {
                _state.value = _state.value.copy(isLoading = false, error = "خطأ: ${e.message}")
            }
        }
    }

    fun updateQuantity(cartItemId: Int, newQty: Int) {
        if (newQty < 1) return
        viewModelScope.launch {
            try {
                api.updateCartItem(cartItemId, UpdateCartRequest(newQty))
                load()
            } catch (_: Exception) {}
        }
    }

    fun removeItem(cartItemId: Int) {
        viewModelScope.launch {
            try {
                api.removeCartItem(cartItemId)
                load()
            } catch (_: Exception) {}
        }
    }

    fun checkout(addressId: Int) {
        viewModelScope.launch {
            _state.value = _state.value.copy(checkingOut = true, checkoutError = null)
            try {
                val response = api.createOrder(CreateOrderRequest(addressId))
                if (response.isSuccessful && response.body()?.success == true) {
                    _state.value = _state.value.copy(checkingOut = false, checkoutSuccess = true)
                } else {
                    _state.value = _state.value.copy(checkingOut = false, checkoutError = response.body()?.message ?: "فشل إنشاء الطلب")
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(checkingOut = false, checkoutError = "خطأ: ${e.message}")
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CartScreen(
    viewModel: CartViewModel = hiltViewModel(),
    onCheckout: () -> Unit,
    onBack: () -> Unit
) {
    val state by viewModel.state.collectAsState()
    var showCheckout by remember { mutableStateOf(false) }

    LaunchedEffect(state.checkoutSuccess) {
        if (state.checkoutSuccess) onCheckout()
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("السلة (${state.itemsCount})") },
                navigationIcon = { IconButton(onClick = onBack) { Icon(Icons.Default.ArrowBack, "رجوع") } }
            )
        }
    ) { padding ->
        when {
            state.isLoading -> Box(Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) { CircularProgressIndicator() }
            state.items.isEmpty() -> Box(Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text("🛒", style = MaterialTheme.typography.displayMedium)
                    Text("السلة فارغة", style = MaterialTheme.typography.bodyLarge)
                }
            }
            else -> {
                Column(Modifier.fillMaxSize().padding(padding)) {
                    LazyColumn(
                        modifier = Modifier.weight(1f),
                        contentPadding = PaddingValues(16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        items(state.items) { item ->
                            CartItemCard(
                                item = item,
                                onUpdateQty = { viewModel.updateQuantity(item.id, it) },
                                onRemove = { viewModel.removeItem(item.id) }
                            )
                        }
                    }

                    // Bottom bar
                    Card(modifier = Modifier.fillMaxWidth()) {
                        Column(Modifier.padding(16.dp)) {
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                Text("المجموع الفرعي", style = MaterialTheme.typography.bodyLarge)
                                Text("${String.format("%.0f", state.subtotal)} ﷼", style = MaterialTheme.typography.titleMedium)
                            }
                            Spacer(Modifier.height(4.dp))
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                Text("رسوم التوصيل", style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                Text("500 ﷼", style = MaterialTheme.typography.bodyMedium)
                            }
                            Divider(Modifier.padding(vertical = 8.dp))
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                Text("الإجمالي", style = MaterialTheme.typography.titleLarge)
                                Text("${String.format("%.0f", state.subtotal + 500)} ﷼", style = MaterialTheme.typography.titleLarge, color = MaterialTheme.colorScheme.primary)
                            }
                            Spacer(Modifier.height(12.dp))
                            Button(
                                onClick = { showCheckout = true },
                                modifier = Modifier.fillMaxWidth().height(50.dp),
                                enabled = state.addresses.isNotEmpty()
                            ) {
                                Text("متابعة الطلب")
                            }
                            if (state.addresses.isEmpty()) {
                                Text("أضف عنوان توصيل أولاً", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.error)
                            }
                            if (state.checkoutError != null) {
                                Text(state.checkoutError!!, color = MaterialTheme.colorScheme.error)
                            }
                        }
                    }
                }
            }
        }
    }

    // Checkout dialog
    if (showCheckout) {
        AlertDialog(
            onDismissRequest = { showCheckout = false },
            title = { Text("اختر عنوان التوصيل") },
            text = {
                Column {
                    state.addresses.forEach { addr ->
                        Row(
                            Modifier.fillMaxWidth().padding(vertical = 4.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            RadioButton(
                                selected = addr.isDefault,
                                onClick = {
                                    viewModel.checkout(addr.id)
                                    showCheckout = false
                                }
                            )
                            Column(Modifier.padding(start = 8.dp)) {
                                Text(addr.title, style = MaterialTheme.typography.bodyMedium)
                                Text(addr.fullAddress ?: addr.address, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                        }
                    }
                }
            },
            confirmButton = {},
            dismissButton = { TextButton(onClick = { showCheckout = false }) { Text("إلغاء") } }
        )
    }
}

@Composable
fun CartItemCard(item: CartItem, onUpdateQty: (Int) -> Unit, onRemove: () -> Unit) {
    Card {
        Row(Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
            AsyncImage(
                model = item.product?.thumbnail,
                contentDescription = null,
                modifier = Modifier.size(60.dp).clip(MaterialTheme.shapes.small),
                contentScale = ContentScale.Crop
            )
            Column(Modifier.weight(1f).padding(horizontal = 12.dp)) {
                Text(item.product?.name ?: "منتج", style = MaterialTheme.typography.bodyMedium)
                Text("${String.format("%.0f", item.unitPrice)} ﷼", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.primary)
                Row(verticalAlignment = Alignment.CenterVertically) {
                    IconButton(onClick = { onUpdateQty(item.quantity - 1) }, modifier = Modifier.size(32.dp)) {
                        Icon(Icons.Default.Remove, "تقليل", modifier = Modifier.size(16.dp))
                    }
                    Text("${item.quantity}", modifier = Modifier.padding(horizontal = 8.dp))
                    IconButton(onClick = { onUpdateQty(item.quantity + 1) }, modifier = Modifier.size(32.dp)) {
                        Icon(Icons.Default.Add, "زيادة", modifier = Modifier.size(16.dp))
                    }
                }
            }
            Column(horizontalAlignment = Alignment.End) {
                Text("${String.format("%.0f", item.totalPrice)} ﷼", style = MaterialTheme.typography.titleMedium)
                IconButton(onClick = onRemove) {
                    Icon(Icons.Default.Delete, "حذف", tint = MaterialTheme.colorScheme.error)
                }
            }
        }
    }
}
