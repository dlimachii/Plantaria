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
                        onClick = { mode = AuthMode.Login },
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
                        onClick = { mode = AuthMode.Register },
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
                )

                OutlinedTextField(
                    value = handle,
                    onValueChange = { handle = it },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Handle") },
                    singleLine = true,
                )

                if (mode == AuthMode.Register) {
                    OutlinedTextField(
                        value = displayName,
                        onValueChange = { displayName = it },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Nombre visible") },
                        singleLine = true,
                    )
                    OutlinedTextField(
                        value = email,
                        onValueChange = { email = it },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Correo") },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
                    )
                }

                OutlinedTextField(
                    value = password,
                    onValueChange = { password = it },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Contraseña") },
                    singleLine = true,
                    visualTransformation = PasswordVisualTransformation(),
                )

                if (mode == AuthMode.Register) {
                    OutlinedTextField(
                        value = passwordConfirmation,
                        onValueChange = { passwordConfirmation = it },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Repetir contraseña") },
                        singleLine = true,
                        visualTransformation = PasswordVisualTransformation(),
                    )
                    OutlinedTextField(
                        value = country,
                        onValueChange = { country = it },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("País") },
                        singleLine = true,
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
                        if (mode == AuthMode.Login) {
                            onLogin(editableApiBaseUrl, handle, password)
                        } else {
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
