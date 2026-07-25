package com.safo.app.ui.auth

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.safo.app.data.api.SafoApi
import com.safo.app.data.model.RegisterRequest
import com.safo.app.data.repository.TokenManager
import com.google.gson.Gson
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class RegisterViewModel @Inject constructor(
    private val api: SafoApi,
    private val tokenManager: TokenManager
) : ViewModel() {
    data class UiState(val isLoading: Boolean = false, val error: String? = null, val success: Boolean = false)

    private val _state = MutableStateFlow(UiState())
    val state: StateFlow<UiState> = _state

    fun register(name: String, phone: String, password: String, confirmPassword: String) {
        if (name.isBlank()) { _state.value = UiState(error = "الاسم مطلوب"); return }
        if (phone.length != 9) { _state.value = UiState(error = "رقم الهاتف يجب أن يكون 9 أرقام"); return }
        if (password.length < 6) { _state.value = UiState(error = "كلمة المرور 6 أحرف على الأقل"); return }
        if (password != confirmPassword) { _state.value = UiState(error = "كلمتا المرور غير متطابقتين"); return }

        viewModelScope.launch {
            _state.value = UiState(isLoading = true)
            try {
                val response = api.register(RegisterRequest(name, phone, password, confirmPassword))
                if (response.isSuccessful && response.body()?.success == true) {
                    val data = response.body()!!.data!!
                    tokenManager.saveToken(data.token)
                    tokenManager.saveUser(Gson().toJson(data.user))
                    _state.value = UiState(success = true)
                } else {
                    _state.value = UiState(error = response.body()?.message ?: "فشل التسجيل")
                }
            } catch (e: Exception) {
                _state.value = UiState(error = "خطأ في الاتصال: ${e.message}")
            }
        }
    }
}

@Composable
fun RegisterScreen(
    viewModel: RegisterViewModel = hiltViewModel(),
    onRegisterSuccess: () -> Unit,
    onBack: () -> Unit
) {
    val state by viewModel.state.collectAsState()
    var name by remember { mutableStateOf("") }
    var phone by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var confirmPassword by remember { mutableStateOf("") }

    LaunchedEffect(state.success) { if (state.success) onRegisterSuccess() }

    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Spacer(modifier = Modifier.height(48.dp))
        Text("إنشاء حساب جديد", style = MaterialTheme.typography.headlineMedium)
        Spacer(modifier = Modifier.height(32.dp))

        OutlinedTextField(
            value = name, onValueChange = { name = it },
            label = { Text("اسم المستخدم") },
            leadingIcon = { Icon(Icons.Default.Person, null) },
            singleLine = true, modifier = Modifier.fillMaxWidth()
        )
        Spacer(modifier = Modifier.height(12.dp))

        OutlinedTextField(
            value = phone, onValueChange = { if (it.length <= 9) phone = it },
            label = { Text("رقم الهاتف") },
            leadingIcon = { Icon(Icons.Default.Phone, null) },
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone),
            singleLine = true, modifier = Modifier.fillMaxWidth()
        )
        Spacer(modifier = Modifier.height(12.dp))

        OutlinedTextField(
            value = password, onValueChange = { password = it },
            label = { Text("كلمة المرور") },
            leadingIcon = { Icon(Icons.Default.Lock, null) },
            visualTransformation = PasswordVisualTransformation(),
            singleLine = true, modifier = Modifier.fillMaxWidth()
        )
        Spacer(modifier = Modifier.height(12.dp))

        OutlinedTextField(
            value = confirmPassword, onValueChange = { confirmPassword = it },
            label = { Text("تأكيد كلمة المرور") },
            leadingIcon = { Icon(Icons.Default.Lock, null) },
            visualTransformation = PasswordVisualTransformation(),
            singleLine = true, modifier = Modifier.fillMaxWidth()
        )

        if (state.error != null) {
            Spacer(modifier = Modifier.height(8.dp))
            Text(state.error!!, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodySmall)
        }

        Spacer(modifier = Modifier.height(24.dp))

        Button(
            onClick = { viewModel.register(name, phone, password, confirmPassword) },
            enabled = !state.isLoading,
            modifier = Modifier.fillMaxWidth().height(50.dp)
        ) {
            if (state.isLoading) CircularProgressIndicator(Modifier.size(24.dp), color = MaterialTheme.colorScheme.onPrimary)
            else Text("تسجيل")
        }

        Spacer(modifier = Modifier.height(12.dp))
        TextButton(onClick = onBack) { Text("لديك حساب؟ تسجيل الدخول") }
    }
}
