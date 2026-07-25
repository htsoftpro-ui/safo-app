package com.safo.app.ui.orders

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.safo.app.data.api.SafoApi
import com.safo.app.data.model.Order
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class OrderDetailViewModel @Inject constructor(
    private val api: SafoApi,
    savedStateHandle: SavedStateHandle
) : ViewModel() {
    private val orderId: Int = savedStateHandle.get<Int>("id") ?: 0

    data class UiState(
        val order: Order? = null,
        val isLoading: Boolean = true,
        val error: String? = null,
        val actionLoading: Boolean = false,
        val actionSuccess: Boolean = false
    )

    private val _state = MutableStateFlow(UiState())
    val state: StateFlow<UiState> = _state

    init { load() }

    fun load() {
        viewModelScope.launch {
            _state.value = UiState(isLoading = true)
            try {
                val response = api.getOrder(orderId)
                if (response.isSuccessful && response.body()?.success == true) {
                    _state.value = UiState(order = response.body()!!.data)
                } else {
                    _state.value = UiState(error = "الطلب غير موجود")
                }
            } catch (e: Exception) {
                _state.value = UiState(error = "خطأ: ${e.message}")
            }
        }
    }

    fun cancelOrder(reason: String) {
        viewModelScope.launch {
            _state.value = _state.value.copy(actionLoading = true)
            try {
                val response = api.cancelOrder(orderId, mapOf("reason" to reason))
                if (response.isSuccessful && response.body()?.success == true) {
                    _state.value = _state.value.copy(order = response.body()!!.data, actionLoading = false, actionSuccess = true)
                } else {
                    _state.value = _state.value.copy(actionLoading = false, error = response.body()?.message ?: "فشل الإلغاء")
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(actionLoading = false, error = "خطأ: ${e.message}")
            }
        }
    }

    fun confirmDelivery() {
        viewModelScope.launch {
            _state.value = _state.value.copy(actionLoading = true)
            try {
                val response = api.confirmDelivery(orderId)
                if (response.isSuccessful && response.body()?.success == true) {
                    _state.value = _state.value.copy(order = response.body()!!.data, actionLoading = false)
                } else {
                    _state.value = _state.value.copy(actionLoading = false, error = response.body()?.message)
                }
            } catch (e: Exception) {
                _state.value = _state.value.copy(actionLoading = false, error = "خطأ: ${e.message}")
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun OrderDetailScreen(
    viewModel: OrderDetailViewModel = hiltViewModel(),
    onBack: () -> Unit
) {
    val state by viewModel.state.collectAsState()
    var showCancelDialog by remember { mutableStateOf(false) }
    var cancelReason by remember { mutableStateOf("") }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(state.order?.order_number ?: "تفاصيل الطلب") },
                navigationIcon = { IconButton(onClick = onBack) { Icon(Icons.Default.ArrowBack, "رجوع") } }
            )
        }
    ) { padding ->
        when {
            state.isLoading -> Box(Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) { CircularProgressIndicator() }
            state.error != null && state.order == null -> Box(Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) {
                Text(state.error!!, color = MaterialTheme.colorScheme.error)
            }
            state.order != null -> {
                val order = state.order!!
                Column(
                    modifier = Modifier.fillMaxSize().padding(padding).verticalScroll(rememberScrollState()).padding(16.dp)
                ) {
                    // Status card
                    val statusColor = when (order.status) {
                        "delivered" -> MaterialTheme.colorScheme.primary
                        "cancelled" -> MaterialTheme.colorScheme.error
                        else -> MaterialTheme.colorScheme.tertiary
                    }
                    Card(colors = CardDefaults.cardColors(containerColor = statusColor.copy(alpha = 0.1f))) {
                        Row(Modifier.fillMaxWidth().padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
                            Column(Modifier.weight(1f)) {
                                Text(order.orderNumber, style = MaterialTheme.typography.titleLarge)
                                Text(order.statusLabel, style = MaterialTheme.typography.titleMedium, color = statusColor)
                            }
                            Text("${String.format("%.0f", order.totalAmount)} ﷼", style = MaterialTheme.typography.headlineSmall, color = statusColor)
                        }
                    }

                    Spacer(Modifier.height(16.dp))

                    // Items
                    Text("المنتجات", style = MaterialTheme.typography.titleMedium)
                    Spacer(Modifier.height(8.dp))
                    order.items?.forEach { item ->
                        Row(Modifier.fillMaxWidth().padding(vertical = 4.dp), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text("${item.productName} × ${item.quantity}", style = MaterialTheme.typography.bodyMedium)
                            Text("${String.format("%.0f", item.totalPrice)} ﷼", style = MaterialTheme.typography.bodyMedium)
                        }
                    }

                    Divider(Modifier.padding(vertical = 12.dp))

                    // Totals
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text("المجموع الفرعي"); Text("${String.format("%.0f", order.subtotal)} ﷼")
                    }
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text("رسوم التوصيل"); Text("${String.format("%.0f", order.deliveryFee)} ﷼")
                    }
                    if (order.discountAmount > 0) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text("الخصم", color = MaterialTheme.colorScheme.error); Text("-${String.format("%.0f", order.discountAmount)} ﷼", color = MaterialTheme.colorScheme.error)
                        }
                    }

                    Spacer(Modifier.height(16.dp))

                    // Delivery address
                    if (order.deliveryAddress != null) {
                        Text("عنوان التوصيل", style = MaterialTheme.typography.titleMedium)
                        Text(order.deliveryAddress, style = MaterialTheme.typography.bodyMedium)
                        Spacer(Modifier.height(16.dp))
                    }

                    // Timeline
                    if (!order.statusHistory.isNullOrEmpty()) {
                        Text("تتبع الطلب", style = MaterialTheme.typography.titleMedium)
                        Spacer(Modifier.height(8.dp))
                        order.statusHistory.reversed().forEachIndexed { idx, entry ->
                            Row(Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
                                Box(
                                    Modifier.size(12.dp).clip(CircleShape).background(
                                        if (idx == 0) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.outline
                                    )
                                )
                                Spacer(Modifier.width(12.dp))
                                Column {
                                    Text(entry.toStatus, style = MaterialTheme.typography.bodyMedium)
                                    if (entry.note != null) Text(entry.note, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                    Text(OrdersScreenKt.formatDate(entry.createdAt), style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                }
                            }
                        }
                    }

                    Spacer(Modifier.height(16.dp))

                    // Actions
                    if (order.status == "pending" || order.status == "confirmed") {
                        OutlinedButton(
                            onClick = { showCancelDialog = true },
                            modifier = Modifier.fillMaxWidth(),
                            enabled = !state.actionLoading
                        ) {
                            Icon(Icons.Default.Cancel, null, tint = MaterialTheme.colorScheme.error)
                            Text(" إلغاء الطلب", color = MaterialTheme.colorScheme.error)
                        }
                    }

                    if (order.status == "shipped") {
                        Button(
                            onClick = { viewModel.confirmDelivery() },
                            modifier = Modifier.fillMaxWidth(),
                            enabled = !state.actionLoading
                        ) {
                            Icon(Icons.Default.CheckCircle, null)
                            Text(" تأكيد الاستلام")
                        }
                    }

                    if (state.error != null) {
                        Spacer(Modifier.height(8.dp))
                        Text(state.error!!, color = MaterialTheme.colorScheme.error)
                    }
                }
            }
        }
    }

    // Cancel dialog
    if (showCancelDialog) {
        AlertDialog(
            onDismissRequest = { showCancelDialog = false },
            title = { Text("إلغاء الطلب") },
            text = {
                OutlinedTextField(
                    value = cancelReason,
                    onValueChange = { cancelReason = it },
                    label = { Text("سبب الإلغاء") },
                    modifier = Modifier.fillMaxWidth()
                )
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        viewModel.cancelOrder(cancelReason)
                        showCancelDialog = false
                        cancelReason = ""
                    },
                    enabled = cancelReason.isNotBlank()
                ) { Text("إلغاء الطلب", color = MaterialTheme.colorScheme.error) }
            },
            dismissButton = { TextButton(onClick = { showCancelDialog = false }) { Text("تراجع") } }
        )
    }
}
