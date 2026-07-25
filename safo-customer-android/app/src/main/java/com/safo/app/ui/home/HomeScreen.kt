package com.safo.app.ui.home

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.style.TextOverflow
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

data class HomeUiState(
    val categories: List<Category> = emptyList(),
    val featured: List<Product> = emptyList(),
    val newArrivals: List<Product> = emptyList(),
    val isLoading: Boolean = true,
    val error: String? = null
)

@HiltViewModel
class HomeViewModel @Inject constructor(private val api: SafoApi) : ViewModel() {
    private val _state = MutableStateFlow(HomeUiState())
    val state: StateFlow<HomeUiState> = _state

    init { load() }

    fun load() {
        viewModelScope.launch {
            _state.value = HomeUiState(isLoading = true)
            try {
                val cats = api.getCategories()
                val featured = api.getFeaturedProducts()
                val arrivals = api.getNewArrivals()
                _state.value = HomeUiState(
                    categories = cats.body()?.data ?: emptyList(),
                    featured = featured.body()?.data ?: emptyList(),
                    newArrivals = arrivals.body()?.data ?: emptyList()
                )
            } catch (e: Exception) {
                _state.value = HomeUiState(error = "خطأ في الاتصال: ${e.message}")
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(
    viewModel: HomeViewModel = hiltViewModel(),
    onProductClick: (Int) -> Unit,
    onCategoryClick: (Int) -> Unit,
    onCartClick: () -> Unit,
    onOrdersClick: () -> Unit,
    onSearch: () -> Unit
) {
    val state by viewModel.state.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("📦 بضاعتي") },
                actions = {
                    IconButton(onClick = onCartClick) { Icon(Icons.Default.ShoppingCart, "السلة") }
                    IconButton(onClick = onOrdersClick) { Icon(Icons.Default.ListAlt, "الطلبات") }
                }
            )
        }
    ) { padding ->
        if (state.isLoading) {
            Box(Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
        } else if (state.error != null) {
            Box(Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(state.error!!, color = MaterialTheme.colorScheme.error)
                    Spacer(Modifier.height(16.dp))
                    Button(onClick = { viewModel.load() }) { Text("إعادة المحاولة") }
                }
            }
        } else {
            LazyColumn(
                modifier = Modifier.fillMaxSize().padding(padding),
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Search bar
                item {
                    OutlinedTextField(
                        value = "",
                        onValueChange = {},
                        placeholder = { Text("ابحث عن منتجات...") },
                        leadingIcon = { Icon(Icons.Default.Search, null) },
                        modifier = Modifier.fillMaxWidth().clickable { onSearch() },
                        enabled = false
                    )
                }

                // Categories
                if (state.categories.isNotEmpty()) {
                    item {
                        Text("الفئات", style = MaterialTheme.typography.titleMedium)
                    }
                    item {
                        LazyRow(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                            items(state.categories) { cat ->
                                CategoryChip(cat) { onCategoryClick(cat.id) }
                            }
                        }
                    }
                }

                // Featured
                if (state.featured.isNotEmpty()) {
                    item {
                        Text("المنتجات المميزة ⭐", style = MaterialTheme.typography.titleMedium)
                    }
                    item {
                        LazyRow(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                            items(state.featured) { product ->
                                ProductCard(product) { onProductClick(product.id) }
                            }
                        }
                    }
                }

                // New Arrivals
                if (state.newArrivals.isNotEmpty()) {
                    item {
                        Text("وصل حديثاً 🆕", style = MaterialTheme.typography.titleMedium)
                    }
                    items(state.newArrivals.chunked(2)) { row ->
                        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                            row.forEach { product ->
                                ProductCard(product, Modifier.weight(1f)) { onProductClick(product.id) }
                            }
                            if (row.size == 1) Spacer(Modifier.weight(1f))
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun CategoryChip(category: Category, onClick: () -> Unit) {
    AssistChip(
        onClick = onClick,
        label = { Text(category.name) },
        leadingIcon = { Text(category.icon ?: "📦") }
    )
}

@Composable
fun ProductCard(product: Product, modifier: Modifier = Modifier, onClick: () -> Unit) {
    Card(
        modifier = modifier.width(160.dp).clickable(onClick = onClick),
        elevation = CardDefaults.cardElevation(2.dp)
    ) {
        Column {
            AsyncImage(
                model = product.thumbnail ?: product.images?.firstOrNull(),
                contentDescription = product.name,
                modifier = Modifier.fillMaxWidth().height(120.dp).clip(MaterialTheme.shapes.medium),
                contentScale = ContentScale.Crop
            )
            Column(Modifier.padding(8.dp)) {
                Text(product.name, style = MaterialTheme.typography.bodyMedium, maxLines = 2, overflow = TextOverflow.Ellipsis)
                Spacer(Modifier.height(4.dp))
                Text(
                    "${String.format("%.0f", product.price)} ﷼",
                    style = MaterialTheme.typography.titleSmall,
                    color = MaterialTheme.colorScheme.primary
                )
                if (product.discountPercent > 0) {
                    Text("${product.discountPercent}% خصم", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.error)
                }
            }
        }
    }
}
