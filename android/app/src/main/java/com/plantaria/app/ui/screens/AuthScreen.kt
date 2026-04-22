package com.plantaria.app.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.outlined.Login
import androidx.compose.material.icons.outlined.PersonAdd
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import com.plantaria.app.ui.theme.PlantariaColors

private enum class AuthMode {
    Login,
    Register,
}

@Composable
fun AuthScreen(
    apiBaseUrl: String,
    isLoading: Boolean,
    message: String?,
    error: String?,
    onApiBaseUrlChange: (String) -> Unit,
    onLogin: (apiBaseUrl: String, handle: String, password: String) -> Unit,
    onRegister: (
        apiBaseUrl: String,
        handle: String,
        displayName: String,
        email: String,
        password: String,
        passwordConfirmation: String,
        country: String,
        province: String,
        city: String,
    ) -> Unit,
) {
    var mode by rememberSaveable { mutableStateOf(AuthMode.Login) }
    var editableApiBaseUrl by rememberSaveable(apiBaseUrl) { mutableStateOf(apiBaseUrl) }
    var handle by rememberSaveable { mutableStateOf("") }
    var displayName by rememberSaveable { mutableStateOf("") }
    var email by rememberSaveable { mutableStateOf("") }
    var password by rememberSaveable { mutableStateOf("") }
    var passwordConfirmation by rememberSaveable { mutableStateOf("") }
    var country by rememberSaveable { mutableStateOf("España") }
    var province by rememberSaveable { mutableStateOf("") }
    var city by rememberSaveable { mutableStateOf("") }
    var submitted by rememberSaveable { mutableStateOf(false) }

    val isRegister = mode == AuthMode.Register
    val apiBaseUrlErrorMessage = if (submitted) apiBaseUrlError(editableApiBaseUrl) else null
    val handleErrorMessage = if (submitted) handleError(handle, isRegister) else null
    val displayNameErrorMessage = if (submitted && isRegister) requiredAuthError(displayName, "El nombre visible") else null
    val emailErrorMessage = if (submitted && isRegister) emailError(email) else null
    val passwordErrorMessage = if (submitted) passwordError(password, isRegister) else null
    val passwordConfirmationErrorMessage = if (submitted && isRegister) {
        passwordConfirmationError(password, passwordConfirmation)
    } else {
        null
    }
    val countryErrorMessage = if (submitted && isRegister) requiredAuthError(country, "El pais") else null

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .verticalScroll(rememberScrollState())
            .padding(20.dp),
        verticalArrangement = Arrangement.Center,
    ) {
        Text(
            text = "Plantaria",
            style = MaterialTheme.typography.headlineLarge,
            fontWeight = FontWeight.SemiBold,
            color = PlantariaColors.Leaf,
        )
        Text(
            text = "Mapa colaborativo de registros vegetales",
            style = MaterialTheme.typography.bodyLarge,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )

        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(top = 24.dp),
            shape = RoundedCornerShape(8.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        ) {
            Column(
                modifier = Modifier.padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    FilterChip(
                        selected = mode == AuthMode.Login,
                        onClick = {
                            mode = AuthMode.Login
                            submitted = false
                        },
                        label = { Text("Entrar") },
                        leadingIcon = {
                            Icon(
                                imageVector = Icons.AutoMirrored.Outlined.Login,
                                contentDescription = null,
                            )
                        },
                    )
                    FilterChip(
                        selected = mode == AuthMode.Register,
                        onClick = {
                            mode = AuthMode.Register
                            submitted = false
                        },
                        label = { Text("Registro") },
                        leadingIcon = {
                            Icon(
                                imageVector = Icons.Outlined.PersonAdd,
                                contentDescription = null,
                            )
                        },
                    )
                }

                OutlinedTextField(
                    value = editableApiBaseUrl,
                    onValueChange = {
                        editableApiBaseUrl = it
                        onApiBaseUrlChange(it)
                    },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("URL API") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Uri),
                    isError = apiBaseUrlErrorMessage != null,
                    supportingText = apiBaseUrlErrorMessage?.let { message -> { Text(message) } },
                )

                OutlinedTextField(
                    value = handle,
                    onValueChange = { handle = it },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Handle") },
                    singleLine = true,
                    isError = handleErrorMessage != null,
                    supportingText = handleErrorMessage?.let { message -> { Text(message) } },
                )

                if (mode == AuthMode.Register) {
                    OutlinedTextField(
                        value = displayName,
                        onValueChange = { displayName = it },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Nombre visible") },
                        singleLine = true,
                        isError = displayNameErrorMessage != null,
                        supportingText = displayNameErrorMessage?.let { message -> { Text(message) } },
                    )
                    OutlinedTextField(
                        value = email,
                        onValueChange = { email = it },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Correo") },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
                        isError = emailErrorMessage != null,
                        supportingText = emailErrorMessage?.let { message -> { Text(message) } },
                    )
                }

                OutlinedTextField(
                    value = password,
                    onValueChange = { password = it },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Contraseña") },
                    singleLine = true,
                    visualTransformation = PasswordVisualTransformation(),
                    isError = passwordErrorMessage != null,
                    supportingText = passwordErrorMessage?.let { message -> { Text(message) } },
                )

                if (mode == AuthMode.Register) {
                    OutlinedTextField(
                        value = passwordConfirmation,
                        onValueChange = { passwordConfirmation = it },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Repetir contraseña") },
                        singleLine = true,
                        visualTransformation = PasswordVisualTransformation(),
                        isError = passwordConfirmationErrorMessage != null,
                        supportingText = passwordConfirmationErrorMessage?.let { message -> { Text(message) } },
                    )
                    OutlinedTextField(
                        value = country,
                        onValueChange = { country = it },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("País") },
                        singleLine = true,
                        isError = countryErrorMessage != null,
                        supportingText = countryErrorMessage?.let { message -> { Text(message) } },
                    )
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        OutlinedTextField(
                            value = province,
                            onValueChange = { province = it },
                            modifier = Modifier.weight(1f),
                            label = { Text("Provincia") },
                            singleLine = true,
                        )
                        OutlinedTextField(
                            value = city,
                            onValueChange = { city = it },
                            modifier = Modifier.weight(1f),
                            label = { Text("Ciudad") },
                            singleLine = true,
                        )
                    }
                }

                StatusText(message = message, error = error)

                Button(
                    onClick = {
                        submitted = true
                        if (mode == AuthMode.Login) {
                            val currentErrors = listOf(
                                apiBaseUrlError(editableApiBaseUrl),
                                handleError(handle, isRegister = false),
                                passwordError(password, isRegister = false),
                            )
                            if (currentErrors.all { it == null }) {
                                onLogin(editableApiBaseUrl, handle, password)
                            }
                        } else {
                            val currentErrors = listOf(
                                apiBaseUrlError(editableApiBaseUrl),
                                handleError(handle, isRegister = true),
                                requiredAuthError(displayName, "El nombre visible"),
                                emailError(email),
                                passwordError(password, isRegister = true),
                                passwordConfirmationError(password, passwordConfirmation),
                                requiredAuthError(country, "El pais"),
                            )
                            if (currentErrors.all { it == null }) {
                                onRegister(
                                    editableApiBaseUrl,
                                    handle,
                                    displayName,
                                    email,
                                    password,
                                    passwordConfirmation,
                                    country,
                                    province,
                                    city,
                                )
                            }
                        }
                    },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !isLoading,
                ) {
                    Icon(
                        imageVector = if (mode == AuthMode.Login) {
                            Icons.AutoMirrored.Outlined.Login
                        } else {
                            Icons.Outlined.PersonAdd
                        },
                        contentDescription = null,
                    )
                    Text(
                        text = if (mode == AuthMode.Login) "Entrar" else "Crear cuenta",
                        modifier = Modifier.padding(start = 8.dp),
                    )
                }
            }
        }
    }
}

@Composable
fun StatusText(
    message: String?,
    error: String?,
) {
    when {
        error != null -> Text(
            text = error,
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.error,
        )
        message != null -> Text(
            text = message,
            style = MaterialTheme.typography.bodyMedium,
            color = PlantariaColors.Leaf,
        )
    }
}

private fun apiBaseUrlError(value: String): String? {
    val trimmed = value.trim()
    return when {
        trimmed.isBlank() -> "La URL de API es obligatoria."
        !trimmed.startsWith("http://") && !trimmed.startsWith("https://") -> {
            "La URL debe empezar por http:// o https://."
        }
        else -> null
    }
}

private fun handleError(value: String, isRegister: Boolean): String? {
    val trimmed = value.trim()
    return when {
        trimmed.isBlank() -> "El handle es obligatorio."
        trimmed.length > 16 -> "El handle no puede superar 16 caracteres."
        isRegister && trimmed.length < 3 -> "El handle debe tener al menos 3 caracteres."
        isRegister && !trimmed.matches(Regex("^[A-Za-z0-9_.]+$")) -> {
            "Usa solo letras, numeros, guion bajo o punto."
        }
        else -> null
    }
}

private fun requiredAuthError(value: String, label: String): String? {
    return if (value.isBlank()) "$label es obligatorio." else null
}

private fun emailError(value: String): String? {
    val trimmed = value.trim()
    return when {
        trimmed.isBlank() -> "El correo es obligatorio."
        !trimmed.contains('@') || !trimmed.substringAfter('@').contains('.') -> "Introduce un correo valido."
        else -> null
    }
}

private fun passwordError(value: String, isRegister: Boolean): String? {
    return when {
        value.isBlank() -> "La contrasena es obligatoria."
        isRegister && value.length < 8 -> "La contrasena debe tener al menos 8 caracteres."
        isRegister && !value.any { it.isLowerCase() } -> "La contrasena necesita una minuscula."
        isRegister && !value.any { it.isUpperCase() } -> "La contrasena necesita una mayuscula."
        isRegister && !value.any { it.isDigit() } -> "La contrasena necesita un numero."
        else -> null
    }
}

private fun passwordConfirmationError(password: String, confirmation: String): String? {
    return when {
        confirmation.isBlank() -> "Repite la contrasena."
        password != confirmation -> "Las contrasenas no coinciden."
        else -> null
    }
}
