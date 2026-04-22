package com.plantaria.app.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.CheckCircle
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
import androidx.compose.material3.Surface
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
import com.plantaria.app.data.model.PlantRecord
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
    val latitude: Double,
    val longitude: Double,
) {
    val isVerified: Boolean = status == "verified"
    val coordinatesText: String = String.format(Locale.US, "%.5f, %.5f", latitude, longitude)
}

@Composable
fun MapScreen(
    contentPadding: PaddingValues,
    records: List<PlantRecord>,
    searchQuery: String,
    isLoading: Boolean,
    error: String?,
    onSearchQueryChange: (String) -> Unit,
    onSearchSubmit: () -> Unit,
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
                isLoading -> LoadingCard()
                selectedRecord != null -> RecordPreviewCard(selectedRecord)
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
private fun RecordPreviewCard(record: MapRecordPreview) {
    ElevatedCard(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.elevatedCardColors(
            containerColor = MaterialTheme.colorScheme.surface,
        ),
    ) {
        Row(
            modifier = Modifier.padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Surface(
                modifier = Modifier.size(72.dp),
                shape = RoundedCornerShape(8.dp),
                color = PlantariaColors.Leaf.copy(alpha = 0.14f),
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Icon(
                        imageVector = Icons.Outlined.LocationOn,
                        contentDescription = null,
                        tint = PlantariaColors.Leaf,
                    )
                }
            }
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
private fun LoadingCard() {
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
            Text("Cargando registros")
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
                text = "Cuando el backend tenga reportes, aparecerán como chinchetas en este mapa.",
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
        latitude = latitude,
        longitude = longitude,
    )
}

private fun MapRecordPreview.toLatLng(): LatLng {
    return LatLng(latitude, longitude)
}
