package com.plantaria.app.ui.screens

import android.Manifest
import android.annotation.SuppressLint
import android.content.Context
import android.content.pm.PackageManager
import android.location.Location
import android.location.LocationManager
import android.net.Uri
import android.os.Build
import android.os.CancellationSignal
import android.os.Handler
import android.os.Looper
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.PickVisualMediaRequest
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.Add
import androidx.compose.material.icons.outlined.ContentCopy
import androidx.compose.material.icons.outlined.Image
import androidx.compose.material.icons.outlined.MyLocation
import androidx.compose.material.icons.outlined.PhotoCamera
import androidx.compose.material.icons.outlined.Update
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.core.content.FileProvider
import com.plantaria.app.ui.theme.PlantariaColors
import java.io.File
import java.util.Locale

@Composable
fun ActionsScreen(
    contentPadding: PaddingValues,
    prefilledObservationRecordId: String?,
    observationPrefillVersion: Int,
    isCreateRecordLoading: Boolean,
    isCreateObservationLoading: Boolean,
    message: String?,
    error: String?,
    onCreateRecord: (
        provisionalCommonName: String,
        description: String,
        photoUri: Uri?,
        latitudeText: String,
        longitudeText: String,
    ) -> Unit,
    onCreateObservation: (
        recordPublicId: String,
        note: String,
        photoUri: Uri?,
        latitudeText: String,
        longitudeText: String,
    ) -> Unit,
) {
    val context = LocalContext.current
    var provisionalName by rememberSaveable { mutableStateOf("") }
    var description by rememberSaveable { mutableStateOf("") }
    var selectedPhotoUri by rememberSaveable { mutableStateOf<Uri?>(null) }
    var selectedObservationPhotoUri by rememberSaveable { mutableStateOf<Uri?>(null) }
    var latitude by rememberSaveable { mutableStateOf("41.3874") }
    var longitude by rememberSaveable { mutableStateOf("2.1686") }
    var recordId by rememberSaveable { mutableStateOf("") }
    var observationNote by rememberSaveable { mutableStateOf("") }
    var observationLatitude by rememberSaveable { mutableStateOf("41.3874") }
    var observationLongitude by rememberSaveable { mutableStateOf("2.1686") }
    var localStatus by rememberSaveable { mutableStateOf<String?>(null) }
    var pendingLocationTarget by rememberSaveable { mutableStateOf<String?>(null) }
    var pendingCameraTarget by rememberSaveable { mutableStateOf<String?>(null) }
    var pendingCameraUri by rememberSaveable { mutableStateOf<Uri?>(null) }
    var reportSubmitted by rememberSaveable { mutableStateOf(false) }
    var observationSubmitted by rememberSaveable { mutableStateOf(false) }

    val reportNameError = if (reportSubmitted) requiredError(provisionalName, "El nombre provisional") else null
    val reportDescriptionError = maxLengthError(description, 2000, "La descripcion")
    val reportPhotoError = if (reportSubmitted && selectedPhotoUri == null) "Selecciona o captura una foto." else null
    val reportLatitudeError = if (reportSubmitted) coordinateError(latitude, "Latitud", -90.0, 90.0) else null
    val reportLongitudeError = if (reportSubmitted) coordinateError(longitude, "Longitud", -180.0, 180.0) else null
    val observationIdError = if (observationSubmitted) requiredError(recordId, "El ID del registro") else null
    val observationNoteError = maxLengthError(observationNote, 2000, "La nota")
    val observationPhotoError = if (observationSubmitted && selectedObservationPhotoUri == null) {
        "Selecciona o captura una foto."
    } else {
        null
    }
    val observationLatitudeError = if (observationSubmitted) {
        coordinateError(observationLatitude, "Latitud", -90.0, 90.0)
    } else {
        null
    }
    val observationLongitudeError = if (observationSubmitted) {
        coordinateError(observationLongitude, "Longitud", -180.0, 180.0)
    } else {
        null
    }

    LaunchedEffect(prefilledObservationRecordId, observationPrefillVersion) {
        val publicId = prefilledObservationRecordId?.takeIf { it.isNotBlank() } ?: return@LaunchedEffect
        recordId = publicId
        observationSubmitted = false
        localStatus = "Registro seleccionado para observar: $publicId"
    }

    LaunchedEffect(message) {
        when {
            message?.startsWith("Reporte creado") == true -> {
                provisionalName = ""
                description = ""
                selectedPhotoUri = null
                reportSubmitted = false
            }
            message?.startsWith("Observación añadida") == true -> {
                recordId = ""
                observationNote = ""
                selectedObservationPhotoUri = null
                observationSubmitted = false
            }
        }
    }

    fun applyLocation(location: Location, target: LocationTarget) {
        val latitudeText = location.latitude.toCoordinateText()
        val longitudeText = location.longitude.toCoordinateText()

        when (target) {
            LocationTarget.Report -> {
                latitude = latitudeText
                longitude = longitudeText
            }
            LocationTarget.Observation -> {
                observationLatitude = latitudeText
                observationLongitude = longitudeText
            }
        }

        localStatus = "Ubicación aplicada."
    }

    fun fillLocation(target: LocationTarget) {
        localStatus = "Buscando ubicación..."
        context.fetchPlantariaLocation(
            onLocation = { location -> applyLocation(location, target) },
            onUnavailable = {
                localStatus = "No hay ubicación disponible. Revisa GPS o usa coordenadas manuales."
            },
        )
    }

    val locationPermissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestMultiplePermissions(),
    ) { permissions ->
        val granted = permissions[Manifest.permission.ACCESS_FINE_LOCATION] == true ||
            permissions[Manifest.permission.ACCESS_COARSE_LOCATION] == true

        if (granted) {
            pendingLocationTarget
                ?.let { runCatching { LocationTarget.valueOf(it) }.getOrNull() }
                ?.let(::fillLocation)
        } else {
            localStatus = "Permiso de ubicación denegado."
        }
    }

    fun requestLocation(target: LocationTarget) {
        pendingLocationTarget = target.name
        if (context.hasPlantariaLocationPermission()) {
            fillLocation(target)
        } else {
            locationPermissionLauncher.launch(
                arrayOf(
                    Manifest.permission.ACCESS_FINE_LOCATION,
                    Manifest.permission.ACCESS_COARSE_LOCATION,
                )
            )
        }
    }

    val cameraLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.TakePicture(),
    ) { success ->
        val target = pendingCameraTarget
            ?.let { runCatching { LocationTarget.valueOf(it) }.getOrNull() }
        val uri = pendingCameraUri

        if (success && target != null && uri != null) {
            when (target) {
                LocationTarget.Report -> selectedPhotoUri = uri
                LocationTarget.Observation -> selectedObservationPhotoUri = uri
            }
            localStatus = "Foto capturada."
        } else {
            localStatus = "No se capturó imagen."
        }
    }

    fun launchCamera(target: LocationTarget) {
        runCatching {
            context.createPlantariaCameraUri()
        }.onSuccess { uri ->
            pendingCameraTarget = target.name
            pendingCameraUri = uri
            cameraLauncher.launch(uri)
        }.onFailure {
            localStatus = "No se pudo preparar la cámara."
        }
    }

    val cameraPermissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestPermission(),
    ) { granted ->
        val target = pendingCameraTarget
            ?.let { runCatching { LocationTarget.valueOf(it) }.getOrNull() }

        if (granted && target != null) {
            launchCamera(target)
        } else {
            localStatus = "Permiso de cámara denegado."
        }
    }

    fun requestCamera(target: LocationTarget) {
        pendingCameraTarget = target.name
        if (context.hasPlantariaCameraPermission()) {
            launchCamera(target)
        } else {
            cameraPermissionLauncher.launch(Manifest.permission.CAMERA)
        }
    }

    val photoPicker = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.PickVisualMedia(),
    ) { uri ->
        selectedPhotoUri = uri
    }
    val observationPhotoPicker = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.PickVisualMedia(),
    ) { uri ->
        selectedObservationPhotoUri = uri
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .padding(contentPadding)
            .verticalScroll(rememberScrollState())
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        Text(
            text = "Acciones",
            style = MaterialTheme.typography.headlineSmall,
            fontWeight = FontWeight.SemiBold,
        )
        localStatus?.let {
            Text(
                text = it,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }

        Card(
            shape = RoundedCornerShape(8.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        ) {
            Column(
                modifier = Modifier.padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    Icon(
                        imageVector = Icons.Outlined.PhotoCamera,
                        contentDescription = null,
                        tint = PlantariaColors.Leaf,
                    )
                    Text(
                        text = "Nuevo reporte",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                    )
                }
                OutlinedTextField(
                    value = provisionalName,
                    onValueChange = { provisionalName = it },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Nombre provisional") },
                    singleLine = true,
                    isError = reportNameError != null,
                    supportingText = reportNameError?.let { message -> { Text(message) } },
                )
                OutlinedTextField(
                    value = description,
                    onValueChange = { description = it },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Descripción") },
                    minLines = 3,
                    isError = reportDescriptionError != null,
                    supportingText = reportDescriptionError?.let { message -> { Text(message) } },
                )
                OutlinedButton(
                    onClick = { requestCamera(LocationTarget.Report) },
                    modifier = Modifier.fillMaxWidth(),
                ) {
                    Icon(
                        imageVector = Icons.Outlined.PhotoCamera,
                        contentDescription = null,
                    )
                    Text(
                        text = "Hacer foto",
                        modifier = Modifier.padding(start = 8.dp),
                    )
                }
                OutlinedButton(
                    onClick = {
                        photoPicker.launch(
                            PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageOnly)
                        )
                    },
                    modifier = Modifier.fillMaxWidth(),
                ) {
                    Icon(
                        imageVector = Icons.Outlined.Image,
                        contentDescription = null,
                    )
                    Text(
                        text = selectedPhotoUri?.let { "Imagen seleccionada" } ?: "Seleccionar imagen",
                        modifier = Modifier.padding(start = 8.dp),
                    )
                }
                reportPhotoError?.let {
                    Text(
                        text = it,
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.error,
                    )
                }
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value = latitude,
                        onValueChange = { latitude = it },
                        modifier = Modifier.weight(1f),
                        label = { Text("Latitud") },
                        singleLine = true,
                        isError = reportLatitudeError != null,
                        supportingText = reportLatitudeError?.let { message -> { Text(message) } },
                    )
                    OutlinedTextField(
                        value = longitude,
                        onValueChange = { longitude = it },
                        modifier = Modifier.weight(1f),
                        label = { Text("Longitud") },
                        singleLine = true,
                        isError = reportLongitudeError != null,
                        supportingText = reportLongitudeError?.let { message -> { Text(message) } },
                    )
                }
                OutlinedButton(
                    onClick = { requestLocation(LocationTarget.Report) },
                    modifier = Modifier.fillMaxWidth(),
                ) {
                    Icon(
                        imageVector = Icons.Outlined.MyLocation,
                        contentDescription = null,
                    )
                    Text(
                        text = "Usar ubicación actual",
                        modifier = Modifier.padding(start = 8.dp),
                    )
                }
                StatusText(message = message, error = error)
                Button(
                    onClick = {
                        reportSubmitted = true
                        val currentErrors = listOf(
                            requiredError(provisionalName, "El nombre provisional"),
                            maxLengthError(description, 2000, "La descripcion"),
                            if (selectedPhotoUri == null) "Selecciona o captura una foto." else null,
                            coordinateError(latitude, "Latitud", -90.0, 90.0),
                            coordinateError(longitude, "Longitud", -180.0, 180.0),
                        )
                        if (currentErrors.all { it == null }) {
                            onCreateRecord(
                                provisionalName,
                                description,
                                selectedPhotoUri,
                                latitude,
                                longitude,
                            )
                        }
                    },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !isCreateRecordLoading,
                ) {
                    Icon(
                        imageVector = Icons.Outlined.Add,
                        contentDescription = null,
                    )
                    Text(
                        text = "Crear reporte",
                        modifier = Modifier.padding(start = 8.dp),
                    )
                }
            }
        }

        Card(
            shape = RoundedCornerShape(8.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        ) {
            Column(
                modifier = Modifier.padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    Icon(
                        imageVector = Icons.Outlined.Update,
                        contentDescription = null,
                        tint = PlantariaColors.Earth,
                    )
                    Text(
                        text = "Actualizar registro",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                    )
                }
                OutlinedTextField(
                    value = recordId,
                    onValueChange = { recordId = it },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("ID del registro") },
                    singleLine = true,
                    isError = observationIdError != null,
                    supportingText = observationIdError?.let { message -> { Text(message) } },
                    trailingIcon = {
                        Icon(
                            imageVector = Icons.Outlined.ContentCopy,
                            contentDescription = "Pegar ID",
                        )
                    },
                )
                OutlinedTextField(
                    value = observationNote,
                    onValueChange = { observationNote = it },
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Nota") },
                    minLines = 2,
                    isError = observationNoteError != null,
                    supportingText = observationNoteError?.let { message -> { Text(message) } },
                )
                OutlinedButton(
                    onClick = { requestCamera(LocationTarget.Observation) },
                    modifier = Modifier.fillMaxWidth(),
                ) {
                    Icon(
                        imageVector = Icons.Outlined.PhotoCamera,
                        contentDescription = null,
                    )
                    Text(
                        text = "Hacer foto",
                        modifier = Modifier.padding(start = 8.dp),
                    )
                }
                OutlinedButton(
                    onClick = {
                        observationPhotoPicker.launch(
                            PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageOnly)
                        )
                    },
                    modifier = Modifier.fillMaxWidth(),
                ) {
                    Icon(
                        imageVector = Icons.Outlined.Image,
                        contentDescription = null,
                    )
                    Text(
                        text = selectedObservationPhotoUri?.let { "Imagen seleccionada" } ?: "Seleccionar imagen",
                        modifier = Modifier.padding(start = 8.dp),
                    )
                }
                observationPhotoError?.let {
                    Text(
                        text = it,
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.error,
                    )
                }
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value = observationLatitude,
                        onValueChange = { observationLatitude = it },
                        modifier = Modifier.weight(1f),
                        label = { Text("Latitud") },
                        singleLine = true,
                        isError = observationLatitudeError != null,
                        supportingText = observationLatitudeError?.let { message -> { Text(message) } },
                    )
                    OutlinedTextField(
                        value = observationLongitude,
                        onValueChange = { observationLongitude = it },
                        modifier = Modifier.weight(1f),
                        label = { Text("Longitud") },
                        singleLine = true,
                        isError = observationLongitudeError != null,
                        supportingText = observationLongitudeError?.let { message -> { Text(message) } },
                    )
                }
                OutlinedButton(
                    onClick = { requestLocation(LocationTarget.Observation) },
                    modifier = Modifier.fillMaxWidth(),
                ) {
                    Icon(
                        imageVector = Icons.Outlined.MyLocation,
                        contentDescription = null,
                    )
                    Text(
                        text = "Usar ubicación actual",
                        modifier = Modifier.padding(start = 8.dp),
                    )
                }
                OutlinedButton(
                    onClick = {
                        observationSubmitted = true
                        val currentErrors = listOf(
                            requiredError(recordId, "El ID del registro"),
                            maxLengthError(observationNote, 2000, "La nota"),
                            if (selectedObservationPhotoUri == null) "Selecciona o captura una foto." else null,
                            coordinateError(observationLatitude, "Latitud", -90.0, 90.0),
                            coordinateError(observationLongitude, "Longitud", -180.0, 180.0),
                        )
                        if (currentErrors.all { it == null }) {
                            onCreateObservation(
                                recordId,
                                observationNote,
                                selectedObservationPhotoUri,
                                observationLatitude,
                                observationLongitude,
                            )
                        }
                    },
                    modifier = Modifier.fillMaxWidth(),
                    enabled = !isCreateObservationLoading,
                ) {
                    Icon(
                        imageVector = Icons.Outlined.PhotoCamera,
                        contentDescription = null,
                    )
                    Text(
                        text = "Añadir observación",
                        modifier = Modifier.padding(start = 8.dp),
                    )
                }
            }
        }

        Spacer(modifier = Modifier.height(8.dp))
    }
}

private enum class LocationTarget {
    Report,
    Observation,
}

private fun Context.hasPlantariaLocationPermission(): Boolean {
    return checkSelfPermission(Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED ||
        checkSelfPermission(Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED
}

private fun Context.hasPlantariaCameraPermission(): Boolean {
    return checkSelfPermission(Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED
}

private fun Context.createPlantariaCameraUri(): Uri {
    val directory = File(cacheDir, "camera").apply { mkdirs() }
    val file = File.createTempFile("plantaria-${System.currentTimeMillis()}-", ".jpg", directory)
    return FileProvider.getUriForFile(this, "$packageName.fileprovider", file)
}

@SuppressLint("MissingPermission")
private fun Context.fetchPlantariaLocation(
    onLocation: (Location) -> Unit,
    onUnavailable: () -> Unit,
) {
    val locationManager = getSystemService(Context.LOCATION_SERVICE) as? LocationManager
    if (locationManager == null) {
        onUnavailable()
        return
    }

    val allowFineLocation = checkSelfPermission(Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED
    val provider = locationManager.bestPlantariaProvider(allowFineLocation)
    if (provider == null) {
        locationManager.latestKnownPlantariaLocation(allowFineLocation)?.let(onLocation) ?: onUnavailable()
        return
    }

    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
        runCatching {
            locationManager.getCurrentLocation(
                provider,
                CancellationSignal(),
                Handler(Looper.getMainLooper()).asExecutor(),
            ) { location ->
                location
                    ?.let(onLocation)
                    ?: locationManager.latestKnownPlantariaLocation(allowFineLocation)?.let(onLocation)
                    ?: onUnavailable()
            }
        }.onFailure {
            locationManager.latestKnownPlantariaLocation(allowFineLocation)?.let(onLocation) ?: onUnavailable()
        }
    } else {
        locationManager.latestKnownPlantariaLocation(allowFineLocation)?.let(onLocation) ?: onUnavailable()
    }
}

private fun LocationManager.bestPlantariaProvider(allowFineLocation: Boolean): String? {
    val enabledProviders = runCatching { getProviders(true) }.getOrDefault(emptyList())
    return plantariaProviders(allowFineLocation)
        .firstOrNull { provider -> provider in enabledProviders }
}

@SuppressLint("MissingPermission")
private fun LocationManager.latestKnownPlantariaLocation(allowFineLocation: Boolean): Location? {
    return plantariaProviders(allowFineLocation).mapNotNull { provider ->
        runCatching { getLastKnownLocation(provider) }.getOrNull()
    }.maxByOrNull { location -> location.time }
}

private fun plantariaProviders(allowFineLocation: Boolean): List<String> {
    return if (allowFineLocation) {
        listOf(
            LocationManager.GPS_PROVIDER,
            LocationManager.NETWORK_PROVIDER,
            LocationManager.PASSIVE_PROVIDER,
        )
    } else {
        listOf(
            LocationManager.NETWORK_PROVIDER,
            LocationManager.PASSIVE_PROVIDER,
        )
    }
}

private fun Handler.asExecutor(): java.util.concurrent.Executor {
    return java.util.concurrent.Executor { command -> post(command) }
}

private fun Double.toCoordinateText(): String {
    return String.format(Locale.US, "%.7f", this)
}

private fun requiredError(value: String, label: String): String? {
    return if (value.isBlank()) "$label es obligatorio." else null
}

private fun maxLengthError(value: String, maxLength: Int, label: String): String? {
    return if (value.length > maxLength) "$label no puede superar $maxLength caracteres." else null
}

private fun coordinateError(
    value: String,
    label: String,
    min: Double,
    max: Double,
): String? {
    val number = value.replace(',', '.').toDoubleOrNull()
        ?: return "$label debe ser un numero valido."

    return if (number !in min..max) {
        "$label debe estar entre ${min.toInt()} y ${max.toInt()}."
    } else {
        null
    }
}
