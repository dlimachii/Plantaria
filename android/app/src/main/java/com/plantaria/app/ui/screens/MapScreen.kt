package com.plantaria.app.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.CheckCircle
import androidx.compose.material.icons.outlined.Close
import androidx.compose.material.icons.outlined.LocationOn
import androidx.compose.material.icons.outlined.MyLocation
import androidx.compose.material.icons.outlined.Refresh
import androidx.compose.material.icons.outlined.Schedule
import androidx.compose.material.icons.outlined.Search
import androidx.compose.material3.AssistChip
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ElevatedCard
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.compose.LocalLifecycleOwner
import com.plantaria.app.data.model.PlantObservation
import com.plantaria.app.data.model.PlantRecord
import com.plantaria.app.ui.components.RemotePlantariaImage
import com.plantaria.app.ui.theme.PlantariaColors
import java.util.Locale
import org.maplibre.android.MapLibre
import org.maplibre.android.annotations.MarkerOptions
import org.maplibre.android.camera.CameraPosition
import org.maplibre.android.geometry.LatLng
import org.maplibre.android.maps.MapLibreMap
import org.maplibre.android.maps.MapView

private const val MAP_STYLE_URL = "https://demotiles.maplibre.org/style.json"
private const val DEFAULT_LATITUDE = 41.3874
private const val DEFAULT_LONGITUDE = 2.1686

private data class MapRecordPreview(
    val id: String,
    val name: String,
    val scientificName: String?,
    val status: String?,
    val authorHandle: String?,
    val photoUrl: String?,
    val latitude: Double,
    val longitude: Double,
) {
    val coordinatesText: String = String.format(Locale.US, "%.5f, %.5f", latitude, longitude)
}

@Composable
fun MapScreen(
    contentPadding: PaddingValues,
    records: List<PlantRecord>,
    selectedRecordDetail: PlantRecord?,
    searchQuery: String,
    isLoading: Boolean,
    isRecordDetailLoading: Boolean,
    error: String?,
    recordDetailError: String?,
    onSearchQueryChange: (String) -> Unit,
    onSearchSubmit: () -> Unit,
    onRecordPreviewClick: (String) -> Unit,
    onCloseRecordDetail: () -> Unit,
) {
    val previews = records.map { record -> record.toMapPreview() }
    var selectedId by rememberSaveable { mutableStateOf<String?>(null) }
    val selectedRecord = previews.firstOrNull { it.id == selectedId } ?: previews.firstOrNull()

    LaunchedEffect(previews) {
        if (selectedId != null && previews.none { it.id == selectedId }) {
            selectedId = previews.firstOrNull()?.id
        }
    }

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(PlantariaColors.MapBase)
            .padding(contentPadding),
    ) {
        PlantariaMapView(
            records = previews,
            selectedRecord = selectedRecord,
            onRecordSelected = { selectedId = it },
            modifier = Modifier.fillMaxSize(),
        )

        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(16.dp),
        ) {
            MapSearchBar(
                query = searchQuery,
                onQueryChange = onSearchQueryChange,
                onSearchSubmit = onSearchSubmit,
            )
            StatusText(message = null, error = error)
            Spacer(modifier = Modifier.weight(1f))
            when {
                selectedRecordDetail != null || isRecordDetailLoading || recordDetailError != null -> RecordDetailCard(
                    record = selectedRecordDetail,
                    isLoading = isRecordDetailLoading,
                    error = recordDetailError,
                    onClose = onCloseRecordDetail,
                )
                isLoading -> LoadingCard("Cargando registros")
                selectedRecord != null -> RecordPreviewCard(
                    record = selectedRecord,
                    onClick = { onRecordPreviewClick(selectedRecord.id) },
                )
                else -> EmptyMapCard()
            }
        }

        IconButton(
            onClick = onSearchSubmit,
            modifier = Modifier
                .align(Alignment.TopEnd)
                .padding(top = 88.dp, end = 16.dp)
                .clip(CircleShape)
                .background(MaterialTheme.colorScheme.surface),
        ) {
            Icon(
                imageVector = Icons.Outlined.Refresh,
                contentDescription = "Recargar registros",
                tint = PlantariaColors.Leaf,
            )
        }
    }
}

@Composable
private fun PlantariaMapView(
    records: List<MapRecordPreview>,
    selectedRecord: MapRecordPreview?,
    onRecordSelected: (String) -> Unit,
    modifier: Modifier = Modifier,
) {
    val context = LocalContext.current
    val lifecycle = LocalLifecycleOwner.current.lifecycle
    var mapLibreMap by remember { mutableStateOf<MapLibreMap?>(null) }
    var styleLoaded by remember { mutableStateOf(false) }
    val mapView = remember(context) {
        MapLibre.getInstance(context.applicationContext)
        MapView(context).apply {
            onCreate(null)
        }
    }

    DisposableEffect(lifecycle, mapView) {
        var started = lifecycle.currentState.isAtLeast(Lifecycle.State.STARTED)
        var resumed = lifecycle.currentState.isAtLeast(Lifecycle.State.RESUMED)

        if (started) {
            mapView.onStart()
        }
        if (resumed) {
            mapView.onResume()
        }

        val observer = LifecycleEventObserver { _, event ->
            when (event) {
                Lifecycle.Event.ON_START -> if (!started) {
                    mapView.onStart()
                    started = true
                }
                Lifecycle.Event.ON_RESUME -> if (!resumed) {
                    mapView.onResume()
                    resumed = true
                }
                Lifecycle.Event.ON_PAUSE -> if (resumed) {
                    mapView.onPause()
                    resumed = false
                }
                Lifecycle.Event.ON_STOP -> if (started) {
                    mapView.onStop()
                    started = false
                }
                else -> Unit
            }
        }

        lifecycle.addObserver(observer)

        onDispose {
            lifecycle.removeObserver(observer)
            if (resumed) {
                mapView.onPause()
            }
            if (started) {
                mapView.onStop()
            }
            mapView.onDestroy()
        }
    }

    AndroidView(
        factory = {
            mapView.apply {
                getMapAsync { map ->
                    mapLibreMap = map
                    map.uiSettings.isCompassEnabled = false
                    map.setStyle(MAP_STYLE_URL) {
                        styleLoaded = true
                    }
                }
            }
        },
        modifier = modifier,
    )

    LaunchedEffect(mapLibreMap, styleLoaded, records, selectedRecord?.id) {
        val map = mapLibreMap ?: return@LaunchedEffect
        if (!styleLoaded) {
            return@LaunchedEffect
        }

        map.renderRecords(
            records = records,
            selectedRecord = selectedRecord,
            onRecordSelected = onRecordSelected,
        )
    }
}

private fun MapLibreMap.renderRecords(
    records: List<MapRecordPreview>,
    selectedRecord: MapRecordPreview?,
    onRecordSelected: (String) -> Unit,
) {
    clear()
    setOnMarkerClickListener { marker ->
        marker.snippet?.let(onRecordSelected)
        true
    }

    records.forEach { record ->
        addMarker(
            MarkerOptions()
                .position(record.toLatLng())
                .title(record.name)
                .snippet(record.id),
        )
    }

    val target = selectedRecord?.toLatLng() ?: LatLng(DEFAULT_LATITUDE, DEFAULT_LONGITUDE)
    val zoom = if (records.isEmpty()) 11.0 else 14.0
    cameraPosition = CameraPosition.Builder()
        .target(target)
        .zoom(zoom)
        .build()
}

@Composable
private fun MapSearchBar(
    query: String,
    onQueryChange: (String) -> Unit,
    onSearchSubmit: () -> Unit,
) {
    OutlinedTextField(
        value = query,
        onValueChange = onQueryChange,
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(24.dp))
            .background(MaterialTheme.colorScheme.surface),
        leadingIcon = {
            Icon(
                imageVector = Icons.Outlined.Search,
                contentDescription = null,
            )
        },
        trailingIcon = {
            IconButton(onClick = onSearchSubmit) {
                Icon(
                    imageVector = Icons.Outlined.Search,
                    contentDescription = "Buscar",
                )
            }
        },
        placeholder = { Text("Buscar planta, zona o ID") },
        shape = RoundedCornerShape(24.dp),
        singleLine = true,
    )
}

@Composable
private fun RecordPreviewCard(
    record: MapRecordPreview,
    onClick: () -> Unit,
) {
    ElevatedCard(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.elevatedCardColors(
            containerColor = MaterialTheme.colorScheme.surface,
        ),
    ) {
        Row(
            modifier = Modifier.padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            RemotePlantariaImage(
                imageUrl = record.photoUrl,
                contentDescription = "Foto de ${record.name}",
                fallbackIcon = Icons.Outlined.LocationOn,
                modifier = Modifier
                    .size(72.dp)
                    .clip(RoundedCornerShape(8.dp)),
            )
            Spacer(modifier = Modifier.width(14.dp))
            Column(modifier = Modifier.weight(1f)) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Text(
                        text = record.name,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                    )
                    Text(
                        text = record.id,
                        style = MaterialTheme.typography.labelMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                record.scientificName?.let {
                    Text(
                        text = it,
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                Text(
                    text = record.coordinatesText,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                Text(
                    text = "Toca la tarjeta para abrir la ficha",
                    style = MaterialTheme.typography.labelSmall,
                    color = PlantariaColors.Leaf,
                )
                Spacer(modifier = Modifier.height(8.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    StatusChip(record.status)
                    record.authorHandle?.let {
                        AssistChip(
                            onClick = {},
                            label = { Text("@$it") },
                            leadingIcon = {
                                Icon(
                                    imageVector = Icons.Outlined.MyLocation,
                                    contentDescription = null,
                                )
                            },
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun RecordDetailCard(
    record: PlantRecord?,
    isLoading: Boolean,
    error: String?,
    onClose: () -> Unit,
) {
    ElevatedCard(
        modifier = Modifier
            .fillMaxWidth()
            .heightIn(max = 560.dp),
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.elevatedCardColors(
            containerColor = MaterialTheme.colorScheme.surface,
        ),
    ) {
        Column(
            modifier = Modifier
                .padding(16.dp)
                .verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Text(
                    text = record?.displayName ?: "Ficha de registro",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.SemiBold,
                )
                IconButton(onClick = onClose) {
                    Icon(
                        imageVector = Icons.Outlined.Close,
                        contentDescription = "Cerrar ficha",
                    )
                }
            }

            when {
                isLoading && record == null -> LoadingCard("Cargando ficha")
                error != null -> Text(
                    text = error,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.error,
                )
                record != null -> RecordDetailContent(record)
            }
        }
    }
}

@Composable
private fun RecordDetailContent(record: PlantRecord) {
    RemotePlantariaImage(
        imageUrl = record.primaryPhotoUrl,
        contentDescription = "Foto principal de ${record.displayName}",
        fallbackIcon = Icons.Outlined.LocationOn,
        modifier = Modifier
            .fillMaxWidth()
            .height(190.dp)
            .clip(RoundedCornerShape(8.dp)),
    )

    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        StatusChip(record.verificationStatus)
        record.plantCondition?.let { TextChip("Estado: $it") }
    }

    DetailLine(label = "ID", value = record.publicId)
    record.verifiedScientificName?.let { DetailLine(label = "Nombre cientifico", value = it) }
    record.verifiedCommonName?.let { DetailLine(label = "Nombre comun verificado", value = it) }
    DetailLine(label = "Nombre provisional", value = record.provisionalCommonName)
    record.description?.let { DetailLine(label = "Descripcion", value = it) }
    record.author?.handle?.let { DetailLine(label = "Autor", value = "@$it") }
    DetailLine(
        label = "Coordenadas",
        value = String.format(Locale.US, "%.5f, %.5f", record.latitude, record.longitude),
    )
    record.createdAt?.let { DetailLine(label = "Creado", value = it.toReadableDateTime()) }
    record.latestObservationAt?.let { DetailLine(label = "Ultima observacion", value = it.toReadableDateTime()) }

    Text(
        text = "Observaciones (${record.observations.size})",
        style = MaterialTheme.typography.titleMedium,
        fontWeight = FontWeight.SemiBold,
    )
    if (record.observations.isEmpty()) {
        Text(
            text = "Todavia no hay observaciones cargadas en esta ficha.",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
    } else {
        record.observations.forEach { observation ->
            ObservationRow(observation)
        }
    }
}

@Composable
private fun ObservationRow(observation: PlantObservation) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(12.dp),
        verticalAlignment = Alignment.Top,
    ) {
        RemotePlantariaImage(
            imageUrl = observation.photoUrl,
            contentDescription = "Foto de observacion",
            fallbackIcon = Icons.Outlined.LocationOn,
            modifier = Modifier
                .size(70.dp)
                .clip(RoundedCornerShape(8.dp)),
        )
        Column(
            modifier = Modifier.weight(1f),
            verticalArrangement = Arrangement.spacedBy(2.dp),
        ) {
            Text(
                text = observation.observedAt?.toReadableDateTime() ?: observation.publicId,
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.SemiBold,
            )
            observation.note?.let {
                Text(
                    text = it,
                    style = MaterialTheme.typography.bodyMedium,
                )
            }
            observation.author?.handle?.let {
                Text(
                    text = "@$it",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            Text(
                text = String.format(Locale.US, "%.5f, %.5f", observation.latitude, observation.longitude),
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}

@Composable
private fun DetailLine(label: String, value: String) {
    Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
        Text(
            text = label,
            style = MaterialTheme.typography.labelMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Text(
            text = value,
            style = MaterialTheme.typography.bodyMedium,
        )
    }
}

@Composable
private fun TextChip(text: String) {
    AssistChip(
        onClick = {},
        label = { Text(text) },
    )
}

@Composable
private fun LoadingCard(message: String) {
    ElevatedCard(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(8.dp),
    ) {
        Row(
            modifier = Modifier.padding(18.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            CircularProgressIndicator(modifier = Modifier.size(24.dp))
            Text(message)
        }
    }
}

@Composable
private fun EmptyMapCard() {
    ElevatedCard(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(8.dp),
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                text = "Sin registros",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
            )
            Text(
                text = "Cuando el backend tenga reportes, apareceran como chinchetas en este mapa.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}

@Composable
private fun StatusChip(status: String?) {
    val verified = status == "verified"
    AssistChip(
        onClick = {},
        label = { Text(if (verified) "Verificado" else "Pendiente") },
        leadingIcon = {
            Icon(
                imageVector = if (verified) Icons.Outlined.CheckCircle else Icons.Outlined.Schedule,
                contentDescription = null,
            )
        },
    )
}

private fun PlantRecord.toMapPreview(): MapRecordPreview {
    return MapRecordPreview(
        id = publicId,
        name = displayName,
        scientificName = verifiedScientificName,
        status = verificationStatus,
        authorHandle = author?.handle,
        photoUrl = primaryPhotoUrl,
        latitude = latitude,
        longitude = longitude,
    )
}

private fun MapRecordPreview.toLatLng(): LatLng {
    return LatLng(latitude, longitude)
}

private fun String.toReadableDateTime(): String {
    return take(16).replace('T', ' ')
}
