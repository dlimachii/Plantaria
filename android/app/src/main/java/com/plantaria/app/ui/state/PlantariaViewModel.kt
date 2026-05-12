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
import com.plantaria.app.data.model.UserActivityItem
import com.plantaria.app.data.session.AppSession
import com.plantaria.app.data.session.SessionStore
import java.io.File
import java.io.ByteArrayOutputStream
import java.net.HttpURLConnection
import java.net.URL
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import kotlin.math.max
import org.json.JSONObject

/**
 * ViewModel principal del cliente Android.
 *
 * Orquesta autenticación, sesión persistida, caché local ligera, consumo de API y acciones
 * del usuario en mapa, observaciones y perfil.
 */
class PlantariaViewModel(application: Application) : AndroidViewModel(application) {
    private val defaultApiBaseUrl = defaultPlantariaApiBaseUrl()
    private val sessionStore = SessionStore(application, defaultApiBaseUrl)
    private var legacyApiBaseUrlChecked = false
    private var bootstrapChecked = false

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

                if (!legacyApiBaseUrlChecked) {
                    legacyApiBaseUrlChecked = true
                    maybeResetLegacyLocalApiBaseUrl(session)
                }

                if (!bootstrapChecked) {
                    bootstrapChecked = true
                    maybeBootstrapApiBaseUrl(session)
                }

                if (session.token != null && session.token != previousToken) {
                    refreshCurrentUser()
                    maybeLoadCachedRecords()
                    refreshRecords()
                    refreshUserActivity()
                } else if (session.token == null) {
                    uiState = uiState.copy(
                        records = emptyList(),
                        userActivity = emptyList(),
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

    private fun maybeLoadCachedRecords() {
        if (uiState.records.isNotEmpty()) {
            return
        }

        viewModelScope.launch {
            val cached = withContext(Dispatchers.IO) { readRecordsCache() }.orEmpty()
            if (cached.isNotEmpty()) {
                uiState = uiState.copy(records = cached)
            }
        }
    }

    private fun maybeResetLegacyLocalApiBaseUrl(session: AppSession) {
        if (!session.isApiBaseUrlExplicit) {
            return
        }

        val explicitApiBaseUrl = session.apiBaseUrl.normalizedApiBaseUrl() ?: return
        val defaultNormalizedApiBaseUrl = defaultApiBaseUrl.normalizedApiBaseUrl() ?: return
        if (!explicitApiBaseUrl.isLegacyLocalApiBaseUrl() || defaultNormalizedApiBaseUrl.isLegacyLocalApiBaseUrl()) {
            return
        }

        viewModelScope.launch {
            runCatching {
                sessionStore.resetApiBaseUrlToDefault()
            }.onSuccess {
                uiState = uiState.copy(
                    message = "Servidor restablecido a la API publica.",
                    error = null,
                )
            }
        }
    }

    private fun maybeBootstrapApiBaseUrl(session: AppSession) {
        if (session.isApiBaseUrlExplicit) {
            return
        }

        val configUrl = BuildConfig.PLANTARIA_BOOTSTRAP_CONFIG_URL.trim()
        if (configUrl.isBlank()) {
            return
        }

        viewModelScope.launch {
            val bootstrapped = runCatching {
                fetchBootstrapApiBaseUrl(configUrl)
            }.getOrNull()

            val normalized = bootstrapped?.normalizedApiBaseUrl() ?: return@launch
            runCatching {
                sessionStore.saveApiBaseUrl(normalized)
            }.onSuccess {
                uiState = uiState.copy(message = "Servidor actualizado.", error = null)
            }
        }
    }

    private suspend fun fetchBootstrapApiBaseUrl(configUrl: String): String? = withContext(Dispatchers.IO) {
        runCatching {
            val connection = (URL(configUrl).openConnection() as HttpURLConnection).apply {
                connectTimeout = 5_000
                readTimeout = 10_000
                requestMethod = "GET"
                setRequestProperty("Accept", "application/json")
            }

            val response = try {
                connection.inputStream.bufferedReader().use { it.readText() }
            } finally {
                connection.disconnect()
            }

            val json = JSONObject(response)
            json.optString("api_base_url").ifBlank {
                json.optString("apiBaseUrl").ifBlank { "" }
            }.ifBlank { null }
        }.getOrNull()
    }

    fun refreshUserActivity() {
        val token = uiState.session.token ?: return

        viewModelScope.launch {
            uiState = uiState.copy(isUserActivityLoading = true, error = null)
            runCatching {
                apiClient().myActivity(token)
            }.onSuccess { activity ->
                uiState = uiState.copy(userActivity = activity)
            }.onFailure { throwable ->
                uiState = uiState.copy(error = throwable.readableMessage())
            }
            uiState = uiState.copy(isUserActivityLoading = false)
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

    fun login(handle: String, password: String) {
        if (handle.isBlank() || password.isBlank()) {
            uiState = uiState.copy(error = "Introduce usuario y contraseña.")
            return
        }

        val normalizedApiBaseUrl = uiState.session.apiBaseUrl.ifBlank { defaultApiBaseUrl }.normalizedApiBaseUrl()
        if (normalizedApiBaseUrl == null) {
            uiState = uiState.copy(error = "No hay una URL de API válida configurada para esta build.")
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(isAuthLoading = true, error = null, message = null)
            runCatching {
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

    fun setApiBaseUrl(value: String) {
        val normalized = value.normalizedApiBaseUrl()
        if (normalized == null) {
            uiState = uiState.copy(error = "URL de servidor no válida. Debe empezar por http:// o https://.")
            return
        }

        viewModelScope.launch {
            runCatching {
                sessionStore.saveApiBaseUrl(normalized)
            }.onSuccess {
                uiState = uiState.copy(message = "Servidor actualizado.", error = null)
            }.onFailure { throwable ->
                uiState = uiState.copy(error = throwable.readableMessage())
            }
        }
    }

    fun markMapTourSeen() {
        viewModelScope.launch {
            runCatching {
                sessionStore.markMapTourSeen()
            }.onFailure { throwable ->
                uiState = uiState.copy(error = throwable.readableMessage())
            }
        }
    }

    fun register(
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
            uiState = uiState.copy(error = "Rellena handle, nombre visible, email, contraseña y país.")
            return
        }

        val normalizedApiBaseUrl = uiState.session.apiBaseUrl.ifBlank { defaultApiBaseUrl }.normalizedApiBaseUrl()
        if (normalizedApiBaseUrl == null) {
            uiState = uiState.copy(error = "No hay una URL de API válida configurada para esta build.")
            return
        }

        viewModelScope.launch {
            uiState = uiState.copy(isAuthLoading = true, error = null, message = null)
            runCatching {
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

        val missing = buildList {
            if (provisionalCommonName.isBlank()) add("nombre")
            if (photoUri == null) add("foto")
            if (latitude == null || longitude == null) add("ubicación")
        }
        if (missing.isNotEmpty()) {
            uiState = uiState.copy(error = "Falta: ${missing.joinToString(", ")}.")
            return
        }

        val selectedPhotoUri = photoUri!!
        val latitudeValue = latitude!!
        val longitudeValue = longitude!!

        viewModelScope.launch {
            uiState = uiState.copy(
                isCreateRecordLoading = true,
                error = null,
                message = "Preparando foto...",
            )
            runCatching {
                val photo = prepareUploadPhoto(selectedPhotoUri)
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
                    latitude = latitudeValue,
                    longitude = longitudeValue,
                )
            }.onSuccess { record ->
                uiState = uiState.copy(
                    records = listOf(record) + uiState.records,
                    message = "Reporte creado: ${record.publicId}",
                )
                refreshUserActivity()
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

        val missing = buildList {
            if (recordPublicId.isBlank()) add("ID")
            if (photoUri == null) add("foto")
            if (latitude == null || longitude == null) add("ubicación")
        }
        if (missing.isNotEmpty()) {
            uiState = uiState.copy(error = "Falta: ${missing.joinToString(", ")}.")
            return
        }

        val selectedPhotoUri = photoUri!!
        val latitudeValue = latitude!!
        val longitudeValue = longitude!!

        viewModelScope.launch {
            uiState = uiState.copy(
                isCreateObservationLoading = true,
                error = null,
                message = "Preparando foto...",
            )
            runCatching {
                val photo = prepareUploadPhoto(selectedPhotoUri)
                uiState = uiState.copy(message = "Subiendo foto de la observación...")
                val photoPath = apiClient().uploadPhoto(
                    token = token,
                    bytes = photo.bytes,
                    fileName = photo.fileName,
                    mimeType = photo.mimeType,
                )

                uiState = uiState.copy(message = "Guardando observación...")
                apiClient().createObservation(
                    token = token,
                    recordPublicId = recordPublicId.trim(),
                    photoPath = photoPath,
                    note = note.trim().takeIf { it.isNotBlank() },
                    latitude = latitudeValue,
                    longitude = longitudeValue,
                )
            }.onSuccess { observation ->
                uiState = uiState.copy(
                    message = "Observación añadida: ${observation.publicId}",
                    observationRecordPrefillId = null,
                )
                refreshRecords()
                refreshUserActivity()
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
                if (query.isNullOrBlank()) {
                    viewModelScope.launch(Dispatchers.IO) {
                        runCatching { writeRecordsCache(records) }
                    }
                }
            }.onFailure { throwable ->
                uiState = uiState.copy(error = throwable.readableMessage())
            }
            uiState = uiState.copy(isRecordsLoading = false)
        }
    }

    private fun recordsCacheFile(): File {
        return File(getApplication<Application>().filesDir, "plantaria-records-cache.json")
    }

    private fun readRecordsCache(): List<PlantRecord>? {
        val file = recordsCacheFile()
        if (!file.exists()) {
            return null
        }

        val raw = runCatching { file.readText() }.getOrNull()?.trim().orEmpty()
        if (raw.isBlank()) {
            return null
        }

        val root = JSONObject(raw)
        val data = root.optJSONArray("data") ?: return null
        return buildList {
            for (i in 0 until data.length()) {
                val item = data.optJSONObject(i) ?: continue
                add(
                    PlantRecord(
                        uid = item.optString("uid").ifBlank { null },
                        publicId = item.optString("public_id"),
                        provisionalCommonName = item.optString("provisional_common_name"),
                        verifiedCommonName = item.optString("verified_common_name").ifBlank { null },
                        verifiedScientificName = item.optString("verified_scientific_name").ifBlank { null },
                        displayName = item.optString("display_name").ifBlank { item.optString("provisional_common_name") },
                        description = item.optString("description").ifBlank { null },
                        primaryPhotoPath = item.optString("primary_photo_path").ifBlank { null },
                        primaryPhotoUrl = item.optString("primary_photo_url").ifBlank { null },
                        plantCondition = item.optString("plant_condition").ifBlank { null },
                        verificationStatus = item.optString("verification_status").ifBlank { null },
                        latitude = item.optDouble("latitude"),
                        longitude = item.optDouble("longitude"),
                        latestObservationAt = item.optString("latest_observation_at").ifBlank { null },
                        createdAt = item.optString("created_at").ifBlank { null },
                        author = item.optJSONObject("author")?.let { author ->
                            com.plantaria.app.data.model.RecordAuthor(
                                handle = author.optString("handle").ifBlank { null },
                                displayName = author.optString("display_name").ifBlank { null },
                                photoPath = author.optString("photo_path").ifBlank { null },
                                photoUrl = author.optString("photo_url").ifBlank { null },
                            )
                        },
                        observations = emptyList(),
                    )
                )
            }
        }
    }

    private fun writeRecordsCache(records: List<PlantRecord>) {
        val root = JSONObject()
        root.put("saved_at", System.currentTimeMillis())
        val data = org.json.JSONArray()
        records.forEach { record ->
            val item = JSONObject()
            item.put("uid", record.uid)
            item.put("public_id", record.publicId)
            item.put("provisional_common_name", record.provisionalCommonName)
            item.put("verified_common_name", record.verifiedCommonName)
            item.put("verified_scientific_name", record.verifiedScientificName)
            item.put("display_name", record.displayName)
            item.put("description", record.description)
            item.put("primary_photo_path", record.primaryPhotoPath)
            item.put("primary_photo_url", record.primaryPhotoUrl)
            item.put("plant_condition", record.plantCondition)
            item.put("verification_status", record.verificationStatus)
            item.put("latitude", record.latitude)
            item.put("longitude", record.longitude)
            item.put("latest_observation_at", record.latestObservationAt)
            item.put("created_at", record.createdAt)
            record.author?.let { author ->
                val authorJson = JSONObject()
                authorJson.put("handle", author.handle)
                authorJson.put("display_name", author.displayName)
                authorJson.put("photo_path", author.photoPath)
                authorJson.put("photo_url", author.photoUrl)
                item.put("author", authorJson)
            }
            data.put(item)
        }
        root.put("data", data)

        val file = recordsCacheFile()
        runCatching {
            file.writeText(root.toString())
        }
    }

    private fun apiClient(apiBaseUrl: String = uiState.session.apiBaseUrl): PlantariaApiClient {
        return PlantariaApiClient(apiBaseUrl.ifBlank { defaultApiBaseUrl })
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
    val userActivity: List<UserActivityItem> = emptyList(),
    val recordSearchQuery: String = "",
    val locationQuery: String = "",
    val placeResults: List<PlaceSearchResult> = emptyList(),
    val selectedPlaceResult: PlaceSearchResult? = null,
    val selectedRecordDetail: PlantRecord? = null,
    val observationRecordPrefillId: String? = null,
    val observationRecordPrefillVersion: Int = 0,
    val isAuthLoading: Boolean = false,
    val isRecordsLoading: Boolean = false,
    val isUserActivityLoading: Boolean = false,
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

private fun defaultPlantariaApiBaseUrl(): String {
    // For demos we always prefer the build-config base URL and allow users to override it from the login screen.
    return BuildConfig.PLANTARIA_API_BASE_URL
}

private fun String.isLegacyLocalApiBaseUrl(): Boolean {
    return runCatching {
        URL(this).host.lowercase() in setOf("127.0.0.1", "10.0.2.2", "0.0.0.0", "localhost")
    }.getOrDefault(false)
}

private fun isProbablyEmulator(): Boolean {
    val fingerprint = Build.FINGERPRINT.lowercase()
    val model = Build.MODEL.lowercase()
    val manufacturer = Build.MANUFACTURER.lowercase()
    val brand = Build.BRAND.lowercase()
    val device = Build.DEVICE.lowercase()
    val product = Build.PRODUCT.lowercase()

    return fingerprint.startsWith("generic") ||
        fingerprint.contains("vbox") ||
        fingerprint.contains("test-keys") ||
        model.contains("sdk") ||
        model.contains("emulator") ||
        model.contains("android sdk built for") ||
        manufacturer.contains("genymotion") ||
        (brand.startsWith("generic") && device.startsWith("generic")) ||
        product.contains("sdk") ||
        product.contains("emulator")
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
