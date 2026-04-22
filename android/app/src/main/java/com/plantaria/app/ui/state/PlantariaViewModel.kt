package com.plantaria.app.ui.state

import android.app.Application
import android.net.Uri
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.plantaria.app.BuildConfig
import com.plantaria.app.data.api.ApiException
import com.plantaria.app.data.api.PlantariaApiClient
import com.plantaria.app.data.model.PlantRecord
import com.plantaria.app.data.session.AppSession
import com.plantaria.app.data.session.SessionStore
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch

class PlantariaViewModel(application: Application) : AndroidViewModel(application) {
    private val sessionStore = SessionStore(application, BuildConfig.PLANTARIA_API_BASE_URL)

    var uiState by mutableStateOf(PlantariaUiState())
        private set

    init {
        viewModelScope.launch {
            sessionStore.session.collectLatest { session ->
                val previousToken = uiState.session.token
                uiState = uiState.copy(
                    session = session,
                    authChecked = true,
                )

                if (session.token != null && session.token != previousToken) {
                    refreshCurrentUser()
                    refreshRecords()
                } else if (session.token == null) {
                    uiState = uiState.copy(records = emptyList())
                }
            }
        }
    }

    fun updateSearchQuery(value: String) {
        uiState = uiState.copy(searchQuery = value)
    }

    fun updateApiBaseUrl(value: String) {
        uiState = uiState.copy(
            session = uiState.session.copy(apiBaseUrl = value),
        )
    }

    fun login(apiBaseUrl: String, handle: String, password: String) {
        if (handle.isBlank() || password.isBlank()) {
            uiState = uiState.copy(error = "Introduce usuario y contraseña.")
            return
        }

        val normalizedApiBaseUrl = apiBaseUrl.normalizedApiBaseUrl()
        if (normalizedApiBaseUrl == null) {
            uiState = uiState.copy(error = "Introduce una URL de API válida.")
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(isAuthLoading = true, error = null, message = null)
            runCatching {
                sessionStore.saveApiBaseUrl(normalizedApiBaseUrl)
                apiClient(normalizedApiBaseUrl).login(handle.trim(), password)
            }.onSuccess { auth ->
                sessionStore.save(auth.token, auth.user)
                uiState = uiState.copy(message = "Sesión iniciada.")
            }.onFailure { throwable ->
                uiState = uiState.copy(error = throwable.readableMessage())
            }
            uiState = uiState.copy(isAuthLoading = false)
        }
    }

    fun register(
        apiBaseUrl: String,
        handle: String,
        displayName: String,
        email: String,
        password: String,
        passwordConfirmation: String,
        country: String,
        province: String,
        city: String,
    ) {
        if (handle.isBlank() || displayName.isBlank() || email.isBlank() || password.isBlank() || country.isBlank()) {
            uiState = uiState.copy(error = "Completa los campos obligatorios.")
            return
        }

        val normalizedApiBaseUrl = apiBaseUrl.normalizedApiBaseUrl()
        if (normalizedApiBaseUrl == null) {
            uiState = uiState.copy(error = "Introduce una URL de API válida.")
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(isAuthLoading = true, error = null, message = null)
            runCatching {
                sessionStore.saveApiBaseUrl(normalizedApiBaseUrl)
                apiClient(normalizedApiBaseUrl).register(
                    handle = handle.trim(),
                    displayName = displayName.trim(),
                    email = email.trim(),
                    password = password,
                    passwordConfirmation = passwordConfirmation,
                    country = country.trim(),
                    province = province.trim().takeIf { it.isNotBlank() },
                    city = city.trim().takeIf { it.isNotBlank() },
                )
            }.onSuccess { auth ->
                sessionStore.save(auth.token, auth.user)
                uiState = uiState.copy(message = "Cuenta creada.")
            }.onFailure { throwable ->
                uiState = uiState.copy(error = throwable.readableMessage())
            }
            uiState = uiState.copy(isAuthLoading = false)
        }
    }

    fun logout() {
        viewModelScope.launch {
            val token = uiState.session.token
            if (token != null) {
                runCatching { apiClient().logout(token) }
            }
            sessionStore.clear()
            uiState = PlantariaUiState(authChecked = true)
        }
    }

    fun refreshRecords() {
        viewModelScope.launch {
            uiState = uiState.copy(isRecordsLoading = true, error = null)
            runCatching {
                apiClient().records(uiState.searchQuery)
            }.onSuccess { records ->
                uiState = uiState.copy(records = records)
            }.onFailure { throwable ->
                uiState = uiState.copy(error = throwable.readableMessage())
            }
            uiState = uiState.copy(isRecordsLoading = false)
        }
    }

    fun createRecord(
        provisionalCommonName: String,
        description: String,
        photoUri: Uri?,
        latitudeText: String,
        longitudeText: String,
    ) {
        val token = uiState.session.token
        if (token == null) {
            uiState = uiState.copy(error = "Inicia sesión para crear reportes.")
            return
        }

        val latitude = latitudeText.replace(',', '.').toDoubleOrNull()
        val longitude = longitudeText.replace(',', '.').toDoubleOrNull()

        if (provisionalCommonName.isBlank() || photoUri == null || latitude == null || longitude == null) {
            uiState = uiState.copy(error = "Completa nombre, foto y coordenadas válidas.")
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(isCreateRecordLoading = true, error = null, message = null)
            runCatching {
                val photo = readPhoto(photoUri)
                val photoPath = apiClient().uploadPhoto(
                    token = token,
                    bytes = photo.bytes,
                    fileName = photo.fileName,
                    mimeType = photo.mimeType,
                )

                apiClient().createRecord(
                    token = token,
                    provisionalCommonName = provisionalCommonName.trim(),
                    description = description.trim().takeIf { it.isNotBlank() },
                    primaryPhotoPath = photoPath,
                    latitude = latitude,
                    longitude = longitude,
                )
            }.onSuccess { record ->
                uiState = uiState.copy(
                    records = listOf(record) + uiState.records,
                    message = "Reporte creado: ${record.publicId}",
                )
            }.onFailure { throwable ->
                uiState = uiState.copy(error = throwable.readableMessage())
            }
            uiState = uiState.copy(isCreateRecordLoading = false)
        }
    }

    fun createObservation(
        recordPublicId: String,
        note: String,
        photoUri: Uri?,
        latitudeText: String,
        longitudeText: String,
    ) {
        val token = uiState.session.token
        if (token == null) {
            uiState = uiState.copy(error = "Inicia sesión para actualizar registros.")
            return
        }

        val latitude = latitudeText.replace(',', '.').toDoubleOrNull()
        val longitude = longitudeText.replace(',', '.').toDoubleOrNull()

        if (recordPublicId.isBlank() || photoUri == null || latitude == null || longitude == null) {
            uiState = uiState.copy(error = "Completa ID, foto y coordenadas válidas.")
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(isCreateObservationLoading = true, error = null, message = null)
            runCatching {
                val photo = readPhoto(photoUri)
                val photoPath = apiClient().uploadPhoto(
                    token = token,
                    bytes = photo.bytes,
                    fileName = photo.fileName,
                    mimeType = photo.mimeType,
                )

                apiClient().createObservation(
                    token = token,
                    recordPublicId = recordPublicId.trim(),
                    photoPath = photoPath,
                    note = note.trim().takeIf { it.isNotBlank() },
                    latitude = latitude,
                    longitude = longitude,
                )
            }.onSuccess { observation ->
                uiState = uiState.copy(
                    message = "Observación añadida: ${observation.publicId}",
                )
                refreshRecords()
            }.onFailure { throwable ->
                uiState = uiState.copy(error = throwable.readableMessage())
            }
            uiState = uiState.copy(isCreateObservationLoading = false)
        }
    }

    private fun refreshCurrentUser() {
        val token = uiState.session.token ?: return
        viewModelScope.launch {
            runCatching {
                apiClient().me(token)
            }.onSuccess { user ->
                sessionStore.save(token, user)
            }.onFailure { throwable ->
                if (throwable is ApiException && throwable.statusCode == 401) {
                    sessionStore.clear()
                }
            }
        }
    }

    private fun Throwable.readableMessage(): String {
        return message?.takeIf { it.isNotBlank() } ?: "No se pudo completar la operación."
    }

    private fun apiClient(apiBaseUrl: String = uiState.session.apiBaseUrl): PlantariaApiClient {
        return PlantariaApiClient(apiBaseUrl.ifBlank { BuildConfig.PLANTARIA_API_BASE_URL })
    }

    private fun readPhoto(uri: Uri): SelectedPhoto {
        val resolver = getApplication<Application>().contentResolver
        val mimeType = resolver.getType(uri) ?: "image/jpeg"
        val extension = when (mimeType) {
            "image/png" -> "png"
            "image/webp" -> "webp"
            else -> "jpg"
        }
        val bytes = resolver.openInputStream(uri)?.use { input ->
            input.readBytes()
        } ?: error("No se pudo leer la imagen seleccionada.")

        return SelectedPhoto(
            bytes = bytes,
            fileName = "plantaria-${System.currentTimeMillis()}.$extension",
            mimeType = mimeType,
        )
    }

    private fun String.normalizedApiBaseUrl(): String? {
        val trimmed = trim()
        if (!trimmed.startsWith("http://") && !trimmed.startsWith("https://")) {
            return null
        }

        return when {
            trimmed.endsWith("/api/") -> trimmed
            trimmed.endsWith("/api") -> "$trimmed/"
            trimmed.endsWith("/") -> "${trimmed}api/"
            else -> "$trimmed/api/"
        }
    }
}

private data class SelectedPhoto(
    val bytes: ByteArray,
    val fileName: String,
    val mimeType: String,
)

data class PlantariaUiState(
    val authChecked: Boolean = false,
    val session: AppSession = AppSession(),
    val records: List<PlantRecord> = emptyList(),
    val searchQuery: String = "",
    val isAuthLoading: Boolean = false,
    val isRecordsLoading: Boolean = false,
    val isCreateRecordLoading: Boolean = false,
    val isCreateObservationLoading: Boolean = false,
    val message: String? = null,
    val error: String? = null,
)
