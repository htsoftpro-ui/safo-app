package com.safo.app.ui

import androidx.compose.runtime.Composable
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import com.safo.app.ui.auth.LoginScreen
import com.safo.app.ui.auth.RegisterScreen
import com.safo.app.ui.cart.CartScreen
import com.safo.app.ui.home.HomeScreen
import com.safo.app.ui.orders.OrderDetailScreen
import com.safo.app.ui.orders.OrdersScreen
import com.safo.app.ui.products.ProductDetailScreen
import com.safo.app.ui.products.ProductsScreen

sealed class Screen(val route: String) {
    data object Login : Screen("login")
    data object Register : Screen("register")
    data object Home : Screen("home")
    data object Products : Screen("products?category_id={category_id}") {
        fun createRoute(categoryId: Int? = null) =
            if (categoryId != null) "products?category_id=$categoryId" else "products"
    }
    data object ProductDetail : Screen("product/{id}") {
        fun createRoute(id: Int) = "product/$id"
    }
    data object Cart : Screen("cart")
    data object Orders : Screen("orders")
    data object OrderDetail : Screen("order/{id}") {
        fun createRoute(id: Int) = "order/$id"
    }
    data object Addresses : Screen("addresses")
    data object Profile : Screen("profile")
}

@Composable
fun SafoNavigation(startDestination: String = Screen.Home.route) {
    val navController = rememberNavController()

    NavHost(navController = navController, startDestination = startDestination) {
        composable(Screen.Login.route) {
            LoginScreen(
                onLoginSuccess = {
                    navController.navigate(Screen.Home.route) {
                        popUpTo(Screen.Login.route) { inclusive = true }
                    }
                },
                onRegister = { navController.navigate(Screen.Register.route) }
            )
        }

        composable(Screen.Register.route) {
            RegisterScreen(
                onRegisterSuccess = {
                    navController.navigate(Screen.Home.route) {
                        popUpTo(Screen.Login.route) { inclusive = true }
                    }
                },
                onBack = { navController.popBackStack() }
            )
        }

        composable(Screen.Home.route) {
            HomeScreen(
                onProductClick = { navController.navigate(Screen.ProductDetail.createRoute(it)) },
                onCategoryClick = { navController.navigate(Screen.Products.createRoute(it)) },
                onCartClick = { navController.navigate(Screen.Cart.route) },
                onOrdersClick = { navController.navigate(Screen.Orders.route) },
                onSearch = { navController.navigate(Screen.Products.createRoute()) }
            )
        }

        composable(
            Screen.Products.route,
            arguments = listOf(navArgument("category_id") { type = NavType.IntType; defaultValue = 0 })
        ) {
            ProductsScreen(
                onProductClick = { navController.navigate(Screen.ProductDetail.createRoute(it)) },
                onBack = { navController.popBackStack() }
            )
        }

        composable(
            Screen.ProductDetail.route,
            arguments = listOf(navArgument("id") { type = NavType.IntType })
        ) {
            ProductDetailScreen(
                onBack = { navController.popBackStack() },
                onCartClick = { navController.navigate(Screen.Cart.route) }
            )
        }

        composable(Screen.Cart.route) {
            CartScreen(
                onCheckout = { navController.navigate(Screen.Orders.route) },
                onBack = { navController.popBackStack() }
            )
        }

        composable(Screen.Orders.route) {
            OrdersScreen(
                onOrderClick = { navController.navigate(Screen.OrderDetail.createRoute(it)) },
                onBack = { navController.popBackStack() }
            )
        }

        composable(
            Screen.OrderDetail.route,
            arguments = listOf(navArgument("id") { type = NavType.IntType })
        ) {
            OrderDetailScreen(
                onBack = { navController.popBackStack() }
            )
        }
    }
}
