package com.plantaria.app.ui.state

import android.app.Application
import android.content.ContentResolver
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.ImageDecoder
import android.net.Uri
import android.os.Build
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.plantaria.app.BuildConfig
import com.plantaria.app.data.api.ApiException
import com.plantaria.app.data.api.PlantariaApiClient
import com.plantaria.app.data.model.PlaceSearchResult
import com.plantaria.app.data.model.PlantRecord
import com.plantaria.app.data.session.AppSession
import com.plantaria.app.data.session.SessionStore
import java.io.ByteArrayOutputStream
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import kotlin.math.max

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
                    uiState = uiState.copy(
                        records = emptyList(),
                        recordSearchQuery = "",
                        locationQuery = "",
                        placeResults = emptyList(),
                        selectedPlaceResult = null,
                        mapSearchMessage = null,
                    )
                }
            }
        }
    }

    fun updateRecordSearchQuery(value: String) {
        uiState = uiState.copy(recordSearchQuery = value)
    }

    fun clearRecordSearch() {
        uiState = uiState.copy(
            recordSearchQuery = "",
            mapSearchMessage = null,
            error = null,
            recordDetailError = null,
        )
        refreshRecords(query = null)
    }

    fun submitRecordSearch() {
        val query = uiState.recordSearchQuery.trim()
        uiState = uiState.copy(
            mapSearchMessage = if (query.isBlank()) null else "Buscando plantas por \"$query\".",
            error = null,
            recordDetailError = null,
        )
        refreshRecords(query = query.ifBlank { null })
    }

    fun updateLocationQuery(value: String) {
        uiState = uiState.copy(locationQuery = value)
    }

    fun clearLocationSearch() {
        uiState = uiState.copy(
            locationQuery = "",
            placeResults = emptyList(),
            selectedPlaceResult = null,
            mapSearchMessage = null,
            error = null,
            recordDetailError = null,
        )
    }

    fun submitLocationSearch() {
        val query = uiState.locationQuery.trim()
        if (query.isBlank()) {
            uiState = uiState.copy(
                placeResults = emptyList(),
                selectedPlaceResult = null,
                mapSearchMessage = null,
            )
            return
        }

        query.toCoordinateSearchResult()?.let { coordinates ->
            uiState = uiState.copy(
                placeResults = listOf(coordinates),
                selectedPlaceResult = coordinates,
                mapSearchMessage = "Mapa centrado en las coordenadas buscadas.",
                error = null,
            )
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(
                isPlaceSearchLoading = true,
                error = null,
                mapSearchMessage = null,
            )

            runCatching {
                apiClient().searchPlaces(query)
            }.onSuccess { results ->
                val selectedPlaceResult = results.firstOrNull()
                uiState = uiState.copy(
                    placeResults = results,
                    selectedPlaceResult = selectedPlaceResult,
                    mapSearchMessage = when {
                        selectedPlaceResult != null -> "Mapa centrado en ${selectedPlaceResult.shortLabel()}."
                        else -> "No se encontro una zona para \"$query\"."
                    },
                )
            }.onFailure { throwable ->
                uiState = uiState.copy(error = throwable.readableMessage())
            }

            uiState = uiState.copy(isPlaceSearchLoading = false)
        }
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
        refreshRecords(query = uiState.recordSearchQuery)
    }

    fun focusPlaceResult(placeResult: PlaceSearchResult) {
        uiState = uiState.copy(
            selectedPlaceResult = placeResult,
            mapSearchMessage = "Mapa centrado en ${placeResult.shortLabel()}.",
            error = null,
        )
    }

    fun openRecordDetail(publicId: String) {
        viewModelScope.launch {
            uiState = uiState.copy(
                isRecordDetailLoading = true,
                selectedRecordDetail = uiState.records.firstOrNull { it.publicId == publicId },
                recordDetailError = null,
            )
            runCatching {
                apiClient().record(publicId)
            }.onSuccess { record ->
                uiState = uiState.copy(selectedRecordDetail = record)
            }.onFailure { throwable ->
                uiState = uiState.copy(recordDetailError = throwable.readableMessage())
            }
            uiState = uiState.copy(isRecordDetailLoading = false)
        }
    }

    fun closeRecordDetail() {
        uiState = uiState.copy(
            selectedRecordDetail = null,
            isRecordDetailLoading = false,
            recordDetailError = null,
        )
    }

    fun prepareObservationForRecord(publicId: String) {
        uiState = uiState.copy(
            selectedRecordDetail = null,
            recordDetailError = null,
            observationRecordPrefillId = publicId,
            observationRecordPrefillVersion = uiState.observationRecordPrefillVersion + 1,
        )
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
            uiState = uiState.copy(
                isCreateRecordLoading = true,
                error = null,
                message = "Preparando foto...",
            )
            runCatching {
                val photo = prepareUploadPhoto(photoUri)
                uiState = uiState.copy(message = "Subiendo foto del reporte...")
                val photoPath = apiClient().uploadPhoto(
                    token = token,
                    bytes = photo.bytes,
                    fileName = photo.fileName,
                    mimeType = photo.mimeType,
                )

                uiState = uiState.copy(message = "Creando reporte...")
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
                uiState = uiState.copy(error = throwable.readableMessage(), message = null)
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
            uiState = uiState.copy(
                isCreateObservationLoading = true,
                error = null,
                message = "Preparando foto...",
            )
            runCatching {
                val photo = prepareUploadPhoto(photoUri)
                uiState = uiState.copy(message = "Subiendo foto de la observacion...")
                val photoPath = apiClient().uploadPhoto(
                    token = token,
                    bytes = photo.bytes,
                    fileName = photo.fileName,
                    mimeType = photo.mimeType,
                )

                uiState = uiState.copy(message = "Guardando observacion...")
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
                    observationRecordPrefillId = null,
                )
                refreshRecords()
            }.onFailure { throwable ->
                uiState = uiState.copy(error = throwable.readableMessage(), message = null)
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
        if (this is ApiException) {
            val normalized = message.lowercase()
            if ("photo" in normalized && "required" in normalized) {
                return "La foto no llego bien al servidor. Revisa la imagen y vuelve a intentarlo."
            }
            if ("photo" in normalized && ("greater than" in normalized || "too large" in normalized || "kilobytes" in normalized)) {
                return "La foto pesa demasiado para el servidor. La app intenta optimizarla antes de subirla; prueba con otra imagen si vuelve a fallar."
            }
            if (statusCode == 413) {
                return "La foto es demasiado grande para el servidor."
            }
        }

        return message?.takeIf { it.isNotBlank() } ?: "No se pudo completar la operación."
    }

    private fun refreshRecords(query: String?) {
        viewModelScope.launch {
            uiState = uiState.copy(isRecordsLoading = true, error = null)
            runCatching {
                apiClient().records(query?.trim()?.takeIf { it.isNotBlank() })
            }.onSuccess { records ->
                uiState = uiState.copy(records = records)
            }.onFailure { throwable ->
                uiState = uiState.copy(error = throwable.readableMessage())
            }
            uiState = uiState.copy(isRecordsLoading = false)
        }
    }

    private fun apiClient(apiBaseUrl: String = uiState.session.apiBaseUrl): PlantariaApiClient {
        return PlantariaApiClient(apiBaseUrl.ifBlank { BuildConfig.PLANTARIA_API_BASE_URL })
    }

    private suspend fun prepareUploadPhoto(uri: Uri): SelectedPhoto = withContext(Dispatchers.IO) {
        val resolver = getApplication<Application>().contentResolver

        runCatching {
            resolver.compressPlantariaPhoto(uri)
        }.getOrElse {
            val mimeType = resolver.getType(uri) ?: "image/jpeg"
            val extension = when (mimeType) {
                "image/png" -> "png"
                "image/webp" -> "webp"
                else -> "jpg"
            }
            val bytes = resolver.openInputStream(uri)?.use { input ->
                input.readBytes()
            } ?: error("No se pudo leer la imagen seleccionada.")

            SelectedPhoto(
                bytes = bytes,
                fileName = "plantaria-${System.currentTimeMillis()}.$extension",
                mimeType = mimeType,
            )
        }
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
    val recordSearchQuery: String = "",
    val locationQuery: String = "",
    val placeResults: List<PlaceSearchResult> = emptyList(),
    val selectedPlaceResult: PlaceSearchResult? = null,
    val selectedRecordDetail: PlantRecord? = null,
    val observationRecordPrefillId: String? = null,
    val observationRecordPrefillVersion: Int = 0,
    val isAuthLoading: Boolean = false,
    val isRecordsLoading: Boolean = false,
    val isPlaceSearchLoading: Boolean = false,
    val isRecordDetailLoading: Boolean = false,
    val isCreateRecordLoading: Boolean = false,
    val isCreateObservationLoading: Boolean = false,
    val mapSearchMessage: String? = null,
    val message: String? = null,
    val error: String? = null,
    val recordDetailError: String? = null,
)

private fun String.toCoordinateSearchResult(): PlaceSearchResult? {
    val match = Regex("^\\s*(-?\\d+(?:[\\.,]\\d+)?)\\s*[,;]\\s*(-?\\d+(?:[\\.,]\\d+)?)\\s*$").matchEntire(this)
        ?: return null

    val latitude = match.groupValues[1].replace(',', '.').toDoubleOrNull() ?: return null
    val longitude = match.groupValues[2].replace(',', '.').toDoubleOrNull() ?: return null

    if (latitude !in -90.0..90.0 || longitude !in -180.0..180.0) {
        return null
    }

    return PlaceSearchResult(
        displayName = String.format("%.5f, %.5f", latitude, longitude),
        latitude = latitude,
        longitude = longitude,
        type = "coordinates",
        category = "manual",
    )
}

private fun PlaceSearchResult.shortLabel(): String {
    return displayName.substringBefore(',').trim().ifBlank { displayName }
}

private fun ContentResolver.compressPlantariaPhoto(uri: Uri): SelectedPhoto {
    val bitmap = decodePlantariaBitmap(uri)
    val bytes = bitmap.toPlantariaUploadBytes()
    if (!bitmap.isRecycled) {
        bitmap.recycle()
    }

    return SelectedPhoto(
        bytes = bytes,
        fileName = "plantaria-${System.currentTimeMillis()}.jpg",
        mimeType = "image/jpeg",
    )
}

private fun ContentResolver.decodePlantariaBitmap(uri: Uri): Bitmap {
    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
        val source = ImageDecoder.createSource(this, uri)
        return ImageDecoder.decodeBitmap(source) { decoder, info, _ ->
            val maxDimension = max(info.size.width, info.size.height)
            if (maxDimension > 1600) {
                val ratio = 1600f / maxDimension.toFloat()
                decoder.setTargetSize(
                    (info.size.width * ratio).toInt().coerceAtLeast(1),
                    (info.size.height * ratio).toInt().coerceAtLeast(1),
                )
            }
        }
    }

    val bounds = BitmapFactory.Options().apply { inJustDecodeBounds = true }
    openInputStream(uri)?.use { BitmapFactory.decodeStream(it, null, bounds) }

    var sampleSize = 1
    while (
        bounds.outWidth / sampleSize > 1600 ||
        bounds.outHeight / sampleSize > 1600
    ) {
        sampleSize *= 2
    }

    val options = BitmapFactory.Options().apply { inSampleSize = sampleSize }
    return openInputStream(uri)?.use { input ->
        BitmapFactory.decodeStream(input, null, options)
    } ?: error("No se pudo decodificar la imagen seleccionada.")
}

private fun Bitmap.toPlantariaUploadBytes(): ByteArray {
    val output = ByteArrayOutputStream()
    var quality = 88
    var bytes: ByteArray

    do {
        output.reset()
        compress(Bitmap.CompressFormat.JPEG, quality, output)
        bytes = output.toByteArray()
        quality -= 8
    } while (bytes.size > 1_800_000 && quality >= 52)

    return bytes
}
