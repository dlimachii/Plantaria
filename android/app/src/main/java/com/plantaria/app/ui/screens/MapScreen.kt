package com.plantaria.app.ui.screens

import android.Manifest
import android.annotation.SuppressLint
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.graphics.Canvas
import android.location.Location
import android.location.LocationManager
import android.net.Uri
import android.os.Build
import android.os.CancellationSignal
import android.os.Handler
import android.os.Looper
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.aspectRatio
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.outlined.ArrowBack
import androidx.compose.material.icons.outlined.Cancel
import androidx.compose.material.icons.outlined.CheckCircle
import androidx.compose.material.icons.outlined.Close
import androidx.compose.material.icons.outlined.LocationOn
import androidx.compose.material.icons.outlined.MyLocation
import androidx.compose.material.icons.outlined.Refresh
import androidx.compose.material.icons.outlined.RestartAlt
import androidx.compose.material.icons.outlined.Schedule
import androidx.compose.material.icons.outlined.Search
import androidx.compose.material.icons.outlined.Update
import androidx.compose.material3.AssistChip
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ElevatedCard
import androidx.compose.material3.FilterChip
import androidx.compose.material3.FilterChipDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberUpdatedState
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.platform.LocalClipboardManager
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.ui.layout.ContentScale
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.compose.LocalLifecycleOwner
import com.plantaria.app.BuildConfig
import com.plantaria.app.R
import com.plantaria.app.data.model.PlaceSearchResult
import com.plantaria.app.data.model.PlantObservation
import com.plantaria.app.data.model.PlantRecord
import com.plantaria.app.ui.components.RemotePlantariaImage
import com.plantaria.app.ui.theme.PlantariaColors
import java.util.Locale
import kotlin.math.roundToInt
import org.maplibre.android.annotations.Icon
import org.maplibre.android.MapLibre
import org.maplibre.android.annotations.IconFactory
import org.maplibre.android.annotations.MarkerOptions
import org.maplibre.android.camera.CameraPosition
import org.maplibre.android.geometry.LatLng
import org.maplibre.android.maps.MapLibreMap
import org.maplibre.android.maps.MapView
import org.maplibre.android.maps.Style

private const val FALLBACK_MAP_STYLE_URL = "https://demotiles.maplibre.org/style.json"
private const val DEFAULT_LATITUDE = 41.3874
private const val DEFAULT_LONGITUDE = 2.1686
private const val MAX_LOCATION_AGE_MS = 10 * 60 * 1000L
private const val USER_LOCATION_MARKER = "__plantaria_user_location__"
private const val SEARCH_LOCATION_MARKER = "__plantaria_search_location__"
private const val CLUSTER_MARKER_PREFIX = "__plantaria_cluster__:"
private val OSM_STANDARD_MAP_STYLE_JSON = """
    {
      "version": 8,
      "name": "OpenStreetMap Standard",
      "sources": {
        "osm-standard": {
          "type": "raster",
          "tiles": [
            "https://tile.openstreetmap.org/{z}/{x}/{y}.png"
          ],
          "tileSize": 256,
          "maxzoom": 19,
          "attribution": "\u00A9 OpenStreetMap contributors"
        }
      },
      "layers": [
        {
          "id": "osm-standard",
          "type": "raster",
          "source": "osm-standard",
          "minzoom": 0,
          "maxzoom": 19
        }
      ]
    }
""".trimIndent()

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

private data class MapUserLocation(
    val latitude: Double,
    val longitude: Double,
) {
    val coordinatesText: String = String.format(Locale.US, "%.5f, %.5f", latitude, longitude)
}

private data class MapFocusOverride(
    val latitude: Double,
    val longitude: Double,
)

private enum class MapRecordFilter(val label: String) {
    ALL("Todos"),
    VERIFIED("Verificados"),
    PENDING("Pendientes"),
    REJECTED("Rechazados");

    fun matches(status: String?): Boolean {
        return when (this) {
            ALL -> true
            VERIFIED -> status == "verified"
            PENDING -> status == null || status == "pending"
            REJECTED -> status == "rejected"
        }
    }
}

private enum class MapBaseStyle(val label: String) {
    OSM_STANDARD("OSM estándar"),
    CURRENT("Mapa actual");

    fun toBuilder(): Style.Builder {
        return when (this) {
            OSM_STANDARD -> Style.Builder().fromJson(OSM_STANDARD_MAP_STYLE_JSON)
            CURRENT -> Style.Builder().fromUri(
                BuildConfig.PLANTARIA_MAP_STYLE_URL.ifBlank { FALLBACK_MAP_STYLE_URL },
            )
        }
    }
}

@Composable
fun MapScreen(
    contentPadding: PaddingValues,
    records: List<PlantRecord>,
    selectedRecordDetail: PlantRecord?,
    recordSearchQuery: String,
    locationQuery: String,
    placeResults: List<PlaceSearchResult>,
    selectedPlaceResult: PlaceSearchResult?,
    isLoading: Boolean,
    isPlaceSearchLoading: Boolean,
    isRecordDetailLoading: Boolean,
    searchMessage: String?,
    error: String?,
    recordDetailError: String?,
    isTourSeen: Boolean,
    onTourSeen: () -> Unit,
    onRecordSearchQueryChange: (String) -> Unit,
    onRecordSearchSubmit: () -> Unit,
    onClearRecordSearch: () -> Unit,
    onLocationQueryChange: (String) -> Unit,
    onLocationSearchSubmit: () -> Unit,
    onClearLocationSearch: () -> Unit,
    onRefreshRecords: () -> Unit,
    onPlaceSuggestionSelected: (PlaceSearchResult) -> Unit,
    onRecordPreviewClick: (String) -> Unit,
    onCloseRecordDetail: () -> Unit,
    onAddObservationForRecord: (String) -> Unit,
) {
    val context = LocalContext.current
    val onTourSeenLatest by rememberUpdatedState(onTourSeen)
    val onRefreshRecordsLatest by rememberUpdatedState(onRefreshRecords)
    val allPreviews = records.map { record -> record.toMapPreview() }
    var recordFilter by rememberSaveable { mutableStateOf(MapRecordFilter.ALL) }
    var baseStyle by rememberSaveable {
        mutableStateOf(
            if (BuildConfig.PLANTARIA_MAP_STYLE_PICKER_ENABLED) {
                MapBaseStyle.OSM_STANDARD
            } else {
                MapBaseStyle.CURRENT
            },
        )
    }
    val previews = allPreviews.filter { preview -> recordFilter.matches(preview.status) }
    var selectedId by rememberSaveable { mutableStateOf<String?>(null) }
    var userLocation by remember { mutableStateOf<MapUserLocation?>(null) }
    var manualFocusOverride by remember { mutableStateOf<MapFocusOverride?>(null) }
    var locationStatus by rememberSaveable { mutableStateOf<String?>(null) }
    val selectedRecord = selectedId?.let { id -> previews.firstOrNull { it.id == id } }
    val previewRecord = selectedRecord
    val recordSearchResults = if (recordSearchQuery.isNotBlank()) {
        previews
            .sortedWith(
                compareBy<MapRecordPreview> { preview ->
                    userLocation?.distanceKmTo(preview) ?: Double.MAX_VALUE
                }.thenBy { it.name }
            )
    } else {
        emptyList()
    }

    var tourStep by rememberSaveable { mutableStateOf(0) }
    var showTour by rememberSaveable(isTourSeen) { mutableStateOf(!isTourSeen) }
    if (showTour) {
        TourDialog(
            step = tourStep,
            onNext = {
                if (tourStep >= 3) {
                    showTour = false
                    onTourSeenLatest()
                } else {
                    tourStep += 1
                }
            },
            onSkip = {
                showTour = false
                onTourSeenLatest()
            },
        )
    }

    fun applyUserLocation(
        location: Location,
        centerOnUser: Boolean,
    ) {
        userLocation = MapUserLocation(
            latitude = location.latitude,
            longitude = location.longitude,
        )
        if (centerOnUser) {
            manualFocusOverride = MapFocusOverride(
                latitude = location.latitude,
                longitude = location.longitude,
            )
            locationStatus = "Mapa centrado en tu ubicación."
        } else {
            locationStatus = "Ubicación disponible para calcular distancias."
        }
    }

    fun loadUserLocation(centerOnUser: Boolean) {
        locationStatus = "Buscando ubicación..."
        context.fetchPlantariaMapLocation(
            onLocation = { location -> applyUserLocation(location, centerOnUser) },
            onUnavailable = {
                locationStatus = "No hay ubicación disponible. Revisa GPS o permisos."
            },
        )
    }

    fun loadCachedUserLocation() {
        if (!context.hasPlantariaMapLocationPermission()) {
            return
        }

        context.latestKnownPlantariaMapLocation()?.let { location ->
            applyUserLocation(location, centerOnUser = false)
        }
    }

    val locationPermissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestMultiplePermissions(),
    ) { permissions ->
        val granted = permissions[Manifest.permission.ACCESS_FINE_LOCATION] == true ||
            permissions[Manifest.permission.ACCESS_COARSE_LOCATION] == true

        if (granted) {
            loadUserLocation(centerOnUser = true)
        } else {
            locationStatus = "Permiso de ubicación denegado."
        }
    }

    fun requestUserLocation() {
        if (context.hasPlantariaMapLocationPermission()) {
            loadUserLocation(centerOnUser = true)
        } else {
            locationPermissionLauncher.launch(
                arrayOf(
                    Manifest.permission.ACCESS_FINE_LOCATION,
                    Manifest.permission.ACCESS_COARSE_LOCATION,
                )
            )
        }
    }

    LaunchedEffect(previews) {
        if (selectedId != null && previews.none { it.id == selectedId }) {
            selectedId = null
        }
    }

    LaunchedEffect(Unit) {
        loadCachedUserLocation()
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
            userLocation = userLocation,
            manualFocusOverride = manualFocusOverride,
            baseStyle = baseStyle,
            searchResult = null,
            onRecordSelected = { token ->
                if (token.startsWith(CLUSTER_MARKER_PREFIX)) {
                    parseClusterMarker(token)?.let { focus ->
                        selectedId = null
                        manualFocusOverride = focus
                    }
                } else {
                    selectedId = token
                    manualFocusOverride = null
                }
            },
            modifier = Modifier.fillMaxSize(),
        )

        if (BuildConfig.PLANTARIA_MAP_STYLE_PICKER_ENABLED) {
            CompactMapStyleSwitch(
                checked = baseStyle == MapBaseStyle.CURRENT,
                onCheckedChange = { checked ->
                    baseStyle = if (checked) MapBaseStyle.CURRENT else MapBaseStyle.OSM_STANDARD
                    selectedId = null
                },
                modifier = Modifier
                    .align(Alignment.BottomEnd)
                    .padding(16.dp),
            )
        }

        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(16.dp),
        ) {
            MapControlPanel(
                records = previews,
                userLocation = userLocation,
                searchResults = recordSearchResults,
                recordSearchQuery = recordSearchQuery,
                onRecordSearchQueryChange = onRecordSearchQueryChange,
                onRecordSearchSubmit = {
                    selectedId = null
                    onRecordSearchSubmit()
                },
                onClearRecordSearch = {
                    selectedId = null
                    onClearRecordSearch()
                },
                onRefreshRecords = {
                    selectedId = null
                    manualFocusOverride = null
                    onRefreshRecordsLatest()
                },
                onRequestUserLocation = ::requestUserLocation,
                recordFilter = recordFilter,
                onRecordFilterChange = { next ->
                    recordFilter = next
                    selectedId = null
                },
                compactControlsEnabled = BuildConfig.PLANTARIA_MAP_STYLE_PICKER_ENABLED,
                onRecordSelected = { record ->
                    selectedId = record.id
                    manualFocusOverride = null
                },
                onOpenRecord = { record ->
                    selectedId = record.id
                    onRecordPreviewClick(record.id)
                },
            )
            StatusText(
                message = listOfNotNull(searchMessage, locationStatus).joinToString(" ").ifBlank { null },
                error = error,
            )
            Spacer(modifier = Modifier.weight(1f))
            when {
                isLoading -> LoadingCard("Cargando registros")
                error != null && previewRecord == null -> MapErrorCard(
                    error = error,
                    onRetry = {
                        selectedId = null
                        onRefreshRecords()
                    },
                    onClearSearch = {
                        selectedId = null
                        onClearRecordSearch()
                    },
                )
                previewRecord != null -> RecordPreviewCard(
                    record = previewRecord,
                    userLocation = userLocation,
                    onOpen = { onRecordPreviewClick(previewRecord.id) },
                    onClose = { selectedId = null },
                )
                previews.isEmpty() -> EmptyMapCard(
                    searchQuery = recordSearchQuery,
                    onRetry = onRefreshRecords,
                    onClearSearch = onClearRecordSearch,
                )
            }
        }

        if (selectedRecordDetail != null || isRecordDetailLoading || recordDetailError != null) {
            FullScreenRecordDetail(
                record = selectedRecordDetail,
                isLoading = isRecordDetailLoading,
                error = recordDetailError,
                onBack = onCloseRecordDetail,
                onAddObservation = onAddObservationForRecord,
            )
        }
    }
}

@Composable
private fun PlantariaMapView(
    records: List<MapRecordPreview>,
    selectedRecord: MapRecordPreview?,
    userLocation: MapUserLocation?,
    manualFocusOverride: MapFocusOverride?,
    baseStyle: MapBaseStyle,
    searchResult: PlaceSearchResult?,
    onRecordSelected: (String) -> Unit,
    modifier: Modifier = Modifier,
) {
    val context = LocalContext.current
    val lifecycle = LocalLifecycleOwner.current.lifecycle
    var mapLibreMap by remember { mutableStateOf<MapLibreMap?>(null) }
    var styleLoaded by remember { mutableStateOf(false) }
    var requestedStyle by remember { mutableStateOf<MapBaseStyle?>(null) }
    val iconFactory = remember(context) { IconFactory.getInstance(context) }
    val userLocationIcon = remember(iconFactory, context) {
        iconFactory.fromDrawableBitmap(context, R.drawable.marker_user_location)
    }
    val searchLocationIcon = remember(iconFactory, context) {
        iconFactory.fromDrawableBitmap(context, R.drawable.marker_search_focus)
    }
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
                }
            }
        },
        modifier = modifier,
    )

    LaunchedEffect(mapLibreMap, baseStyle) {
        val map = mapLibreMap ?: return@LaunchedEffect
        requestedStyle = baseStyle
        styleLoaded = false
        map.setStyle(baseStyle.toBuilder()) {
            if (requestedStyle == baseStyle) {
                styleLoaded = true
            }
        }
    }

    LaunchedEffect(mapLibreMap, styleLoaded, records, selectedRecord?.id, userLocation, manualFocusOverride, searchResult) {
        val map = mapLibreMap ?: return@LaunchedEffect
        if (!styleLoaded) {
            return@LaunchedEffect
        }

        map.renderRecords(
            records = records,
            selectedRecord = selectedRecord,
            userLocation = userLocation,
            manualFocusOverride = manualFocusOverride,
            searchResult = searchResult,
            userLocationIcon = userLocationIcon,
            searchLocationIcon = searchLocationIcon,
            onRecordSelected = onRecordSelected,
        )
    }
}

private fun IconFactory.fromDrawableBitmap(
    context: Context,
    drawableResId: Int,
): Icon {
    val drawable = requireNotNull(context.getDrawable(drawableResId)) {
        "Drawable resource $drawableResId not found"
    }
    val fallbackSize = (24 * context.resources.displayMetrics.density).roundToInt()
    val width = drawable.intrinsicWidth.takeIf { it > 0 } ?: fallbackSize
    val height = drawable.intrinsicHeight.takeIf { it > 0 } ?: fallbackSize
    val bitmap = Bitmap.createBitmap(width, height, Bitmap.Config.ARGB_8888)
    val canvas = Canvas(bitmap)

    drawable.setBounds(0, 0, canvas.width, canvas.height)
    drawable.draw(canvas)

    return fromBitmap(bitmap)
}

private fun MapLibreMap.renderRecords(
    records: List<MapRecordPreview>,
    selectedRecord: MapRecordPreview?,
    userLocation: MapUserLocation?,
    manualFocusOverride: MapFocusOverride?,
    searchResult: PlaceSearchResult?,
    userLocationIcon: org.maplibre.android.annotations.Icon,
    searchLocationIcon: org.maplibre.android.annotations.Icon,
    onRecordSelected: (String) -> Unit,
) {
    clear()
    setOnMarkerClickListener { marker ->
        marker.snippet
            ?.takeIf { it != USER_LOCATION_MARKER && it != SEARCH_LOCATION_MARKER }
            ?.let(onRecordSelected)
        true
    }

        userLocation?.let { location ->
            addMarker(
                MarkerOptions()
                    .position(location.toLatLng())
                    .title("Tu ubicación actual")
                    .icon(userLocationIcon)
                    .snippet(USER_LOCATION_MARKER),
            )
        }

    searchResult?.let { place ->
        addMarker(
            MarkerOptions()
                .position(place.toLatLng())
                .title(place.shortLabel())
                .icon(searchLocationIcon)
                .snippet(SEARCH_LOCATION_MARKER),
        )
    }

    val buckets = records.groupBy { record ->
        val bucketLat = (record.latitude * 1000).toInt()
        val bucketLng = (record.longitude * 1000).toInt()
        "$bucketLat:$bucketLng"
    }

    buckets.values.forEach { bucket ->
        if (bucket.size >= 3) {
            val latitude = bucket.map { it.latitude }.average()
            val longitude = bucket.map { it.longitude }.average()
            addMarker(
                MarkerOptions()
                    .position(LatLng(latitude, longitude))
                    .title("${bucket.size} registros")
                    .snippet(CLUSTER_MARKER_PREFIX + latitude + "," + longitude),
            )
        } else {
            bucket.forEach { record ->
                addMarker(
                    MarkerOptions()
                        .position(record.toLatLng())
                        .title(record.name)
                        .snippet(record.id),
                )
            }
        }
    }

    val target = selectedRecord?.toLatLng()
        ?: manualFocusOverride?.toLatLng()
        ?: searchResult?.toLatLng()
        ?: userLocation?.toLatLng()
        ?: records.firstOrNull()?.toLatLng()
        ?: LatLng(DEFAULT_LATITUDE, DEFAULT_LONGITUDE)
    val zoom = when {
        selectedRecord != null || userLocation != null || manualFocusOverride != null -> 14.0
        searchResult != null -> 13.0
        else -> 11.0
    }
    cameraPosition = CameraPosition.Builder()
        .target(target)
        .zoom(zoom)
        .build()
}

@Composable
private fun MapControlPanel(
    records: List<MapRecordPreview>,
    userLocation: MapUserLocation?,
    searchResults: List<MapRecordPreview>,
    recordSearchQuery: String,
    onRecordSearchQueryChange: (String) -> Unit,
    onRecordSearchSubmit: () -> Unit,
    onClearRecordSearch: () -> Unit,
    onRefreshRecords: () -> Unit,
    onRequestUserLocation: () -> Unit,
    recordFilter: MapRecordFilter,
    onRecordFilterChange: (MapRecordFilter) -> Unit,
    compactControlsEnabled: Boolean,
    onRecordSelected: (MapRecordPreview) -> Unit,
    onOpenRecord: (MapRecordPreview) -> Unit,
) {
    ElevatedCard(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.elevatedCardColors(
            containerColor = MaterialTheme.colorScheme.surface,
        ),
    ) {
        Column(
            modifier = Modifier.padding(10.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Text(
                    text = "${records.size} registros",
                    modifier = Modifier.weight(1f),
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold,
                )
                IconButton(onClick = onRefreshRecords) {
                    Icon(
                        imageVector = Icons.Outlined.Refresh,
                        contentDescription = "Recargar",
                    )
                }
                IconButton(onClick = onRequestUserLocation) {
                    Icon(
                        imageVector = Icons.Outlined.MyLocation,
                        contentDescription = "Mi ubicación",
                    )
                }
            }
            SearchField(
                label = "Buscar plantas",
                value = recordSearchQuery,
                placeholder = "Nombre común o científico",
                onValueChange = onRecordSearchQueryChange,
                onSubmit = onRecordSearchSubmit,
                onClear = onClearRecordSearch,
            )
            if (compactControlsEnabled) {
                MapFilterDropdown(
                    selected = recordFilter,
                    hasUserLocation = userLocation != null,
                    onSelected = onRecordFilterChange,
                )
            } else {
                Row(
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    userLocation?.let {
                        TextChip("Tu posición")
                    }
                    RecordFilterChips(
                        selected = recordFilter,
                        onSelected = onRecordFilterChange,
                    )
                }
            }
            RecordSearchResultsCard(
                query = recordSearchQuery,
                records = searchResults,
                userLocation = userLocation,
                onRecordSelected = onRecordSelected,
                onOpenRecord = onOpenRecord,
            )
        }
    }
}

@Composable
private fun CompactMapStyleSwitch(
    checked: Boolean,
    onCheckedChange: (Boolean) -> Unit,
    modifier: Modifier = Modifier,
) {
    Surface(
        modifier = modifier,
        shape = RoundedCornerShape(999.dp),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.94f),
        tonalElevation = 4.dp,
        shadowElevation = 3.dp,
    ) {
        Switch(
            checked = checked,
            onCheckedChange = onCheckedChange,
            modifier = Modifier
                .padding(horizontal = 6.dp, vertical = 2.dp)
                .semantics { contentDescription = "Cambiar tipo de mapa" },
        )
    }
}

@Composable
private fun MapFilterDropdown(
    selected: MapRecordFilter,
    hasUserLocation: Boolean,
    onSelected: (MapRecordFilter) -> Unit,
) {
    var expanded by rememberSaveable { mutableStateOf(false) }

    Box {
        OutlinedButton(
            onClick = { expanded = true },
            contentPadding = PaddingValues(horizontal = 12.dp, vertical = 8.dp),
        ) {
            Text(
                text = "Filtro: ${selected.label}",
                style = MaterialTheme.typography.labelLarge,
            )
        }
        DropdownMenu(
            expanded = expanded,
            onDismissRequest = { expanded = false },
        ) {
            DropdownMenuItem(
                text = {
                    Text(if (hasUserLocation) "Tu posición activa" else "Sin posición")
                },
                onClick = {},
                enabled = false,
                leadingIcon = {
                    Icon(
                        imageVector = Icons.Outlined.MyLocation,
                        contentDescription = null,
                    )
                },
            )
            MapRecordFilter.values().forEach { filter ->
                DropdownMenuItem(
                    text = { Text(filter.label) },
                    onClick = {
                        expanded = false
                        onSelected(filter)
                    },
                    leadingIcon = {
                        Icon(
                            imageVector = when (filter) {
                                MapRecordFilter.ALL -> Icons.Outlined.LocationOn
                                MapRecordFilter.VERIFIED -> Icons.Outlined.CheckCircle
                                MapRecordFilter.PENDING -> Icons.Outlined.Schedule
                                MapRecordFilter.REJECTED -> Icons.Outlined.Cancel
                            },
                            contentDescription = null,
                        )
                    },
                    trailingIcon = {
                        if (filter == selected) {
                            Icon(
                                imageVector = Icons.Outlined.CheckCircle,
                                contentDescription = null,
                            )
                        }
                    },
                )
            }
        }
    }
}

@Composable
private fun RecordFilterChips(
    selected: MapRecordFilter,
    onSelected: (MapRecordFilter) -> Unit,
) {
    Row(
        modifier = Modifier.horizontalScroll(rememberScrollState()),
        horizontalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        MapRecordFilter.values().forEach { filter ->
            FilterChip(
                selected = filter == selected,
                onClick = { onSelected(filter) },
                label = { Text(filter.label) },
                colors = FilterChipDefaults.filterChipColors(),
            )
        }
    }
}

@Composable
private fun RecordSearchResultsCard(
    query: String,
    records: List<MapRecordPreview>,
    userLocation: MapUserLocation?,
    onRecordSelected: (MapRecordPreview) -> Unit,
    onOpenRecord: (MapRecordPreview) -> Unit,
) {
    if (query.isBlank()) {
        return
    }

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .heightIn(max = 250.dp)
            .verticalScroll(rememberScrollState()),
        verticalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        Text(
            text = if (records.isEmpty()) "Sin resultados para \"$query\"" else "Resultados para \"$query\"",
            style = MaterialTheme.typography.labelLarge,
            fontWeight = FontWeight.SemiBold,
        )
        records.forEach { record ->
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .clip(RoundedCornerShape(8.dp))
                    .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.45f))
                    .clickable { onRecordSelected(record) }
                    .padding(6.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(10.dp),
            ) {
                RemotePlantariaImage(
                    imageUrl = record.photoUrl,
                    contentDescription = "Foto de ${record.name}",
                    fallbackIcon = Icons.Outlined.LocationOn,
                    modifier = Modifier
                        .size(46.dp)
                        .clip(RoundedCornerShape(8.dp)),
                )
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = record.name,
                        style = MaterialTheme.typography.bodyMedium,
                        fontWeight = FontWeight.SemiBold,
                    )
                    Text(
                        text = listOfNotNull(
                            record.authorHandle?.let { "@$it" },
                            userLocation?.distanceTextTo(record) ?: record.coordinatesText,
                        ).joinToString(" · "),
                        style = MaterialTheme.typography.labelSmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                OutlinedButton(onClick = { onOpenRecord(record) }) {
                    Text(
                        text = "Abrir",
                        style = MaterialTheme.typography.labelMedium,
                    )
                }
            }
        }
    }
}

@Composable
private fun SearchField(
    label: String,
    value: String,
    placeholder: String,
    onValueChange: (String) -> Unit,
    onSubmit: () -> Unit,
    onClear: () -> Unit,
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        modifier = Modifier.fillMaxWidth(),
        label = { Text(label) },
        leadingIcon = {
            Icon(
                imageVector = Icons.Outlined.Search,
                contentDescription = null,
            )
        },
        trailingIcon = {
            Row {
                if (value.isNotBlank()) {
                    IconButton(onClick = onClear) {
                        Icon(
                            imageVector = Icons.Outlined.Close,
                            contentDescription = "Limpiar",
                        )
                    }
                }
                IconButton(onClick = onSubmit) {
                    Icon(
                        imageVector = Icons.Outlined.Search,
                        contentDescription = "Buscar",
                    )
                }
            }
        },
        placeholder = { Text(placeholder) },
        shape = RoundedCornerShape(16.dp),
        singleLine = true,
    )
}

@Composable
private fun RecordPreviewCard(
    record: MapRecordPreview,
    userLocation: MapUserLocation?,
    onOpen: () -> Unit,
    onClose: () -> Unit,
) {
    ElevatedCard(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.elevatedCardColors(
            containerColor = MaterialTheme.colorScheme.surface,
        ),
    ) {
        Column(
            modifier = Modifier.padding(14.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            Row(
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
                    Text(
                        text = record.name,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                    )
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
                }
                IconButton(onClick = onClose) {
                    Icon(
                        imageVector = Icons.Outlined.Close,
                        contentDescription = "Cerrar preview",
                    )
                }
            }
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                StatusChip(record.status)
                record.authorHandle?.let {
                    TextChip("@$it")
                }
            }
            userLocation?.let {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                Text(
                    text = "Tu posición: ${it.coordinatesText}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            }
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Button(
                    onClick = onOpen,
                    modifier = Modifier.weight(1f),
                ) {
                    Text("Abrir ficha")
                }
                OutlinedButton(
                    onClick = onClose,
                    modifier = Modifier.weight(1f),
                ) {
                    Text("Quitar")
                }
            }
            Text(
                text = record.id,
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}

@Composable
private fun PlaceSuggestionsCard(
    places: List<PlaceSearchResult>,
    isLoading: Boolean,
    onPlaceSuggestionSelected: (PlaceSearchResult) -> Unit,
) {
    if (!isLoading && places.isEmpty()) {
        return
    }

    ElevatedCard(
        modifier = Modifier
            .fillMaxWidth()
            .padding(top = 8.dp),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.elevatedCardColors(
            containerColor = MaterialTheme.colorScheme.surface,
        ),
    ) {
        Column(
            modifier = Modifier.padding(12.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            Text(
                text = if (isLoading) "Buscando zona..." else "Coincidencias de zona",
                style = MaterialTheme.typography.labelLarge,
                fontWeight = FontWeight.SemiBold,
            )

            if (isLoading) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(10.dp),
                ) {
                    CircularProgressIndicator(modifier = Modifier.size(18.dp))
                    Text(
                        text = "Consultando ubicaciones",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
            } else {
                places.take(5).forEach { place ->
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(12.dp))
                            .clickable { onPlaceSuggestionSelected(place) }
                            .padding(vertical = 4.dp),
                    ) {
                        Text(
                            text = place.shortLabel(),
                            style = MaterialTheme.typography.bodyMedium,
                            fontWeight = FontWeight.SemiBold,
                        )
                        Text(
                            text = place.displayName,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun PlaceFocusCard(
    place: PlaceSearchResult,
    userLocation: MapUserLocation?,
    onClearSearch: () -> Unit,
) {
    ElevatedCard(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.elevatedCardColors(
            containerColor = MaterialTheme.colorScheme.surface,
        ),
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            Text(
                text = place.shortLabel(),
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
            )
            Text(
                text = place.displayName,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Text(
                text = String.format(Locale.US, "%.5f, %.5f", place.latitude, place.longitude),
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            userLocation?.let {
                Text(
                    text = "Tu ubicación: ${it.coordinatesText}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            place.type?.let { type ->
                TextChip("Tipo: $type")
            }
            OutlinedButton(
                onClick = onClearSearch,
                modifier = Modifier.fillMaxWidth(),
            ) {
                Icon(
                    imageVector = Icons.Outlined.RestartAlt,
                    contentDescription = null,
                )
                Text(
                    text = "Quitar foco del mapa",
                    modifier = Modifier.padding(start = 8.dp),
                )
            }
        }
    }
}

@Composable
private fun FullScreenRecordDetail(
    record: PlantRecord?,
    isLoading: Boolean,
    error: String?,
    onBack: () -> Unit,
    onAddObservation: (String) -> Unit,
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .background(MaterialTheme.colorScheme.surface)
                .padding(horizontal = 8.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            IconButton(onClick = onBack) {
                Icon(
                    imageVector = Icons.AutoMirrored.Outlined.ArrowBack,
                    contentDescription = "Volver al mapa",
                )
            }
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = record?.displayName ?: "Ficha de registro",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.SemiBold,
                )
                record?.publicId?.let {
                    Text(
                        text = it,
                        style = MaterialTheme.typography.labelMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
            }
        }

        when {
            isLoading && record == null -> Box(
                modifier = Modifier.fillMaxSize(),
                contentAlignment = Alignment.Center,
            ) {
                LoadingCard("Cargando ficha")
            }
            error != null -> Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                Text(
                    text = error,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.error,
                )
                OutlinedButton(onClick = onBack) {
                    Text("Volver")
                }
            }
            record != null -> RecordProfileContent(
                record = record,
                onAddObservation = { onAddObservation(record.publicId) },
            )
        }
    }
}

@Composable
private fun RecordProfileContent(
    record: PlantRecord,
    onAddObservation: () -> Unit,
) {
    val context = LocalContext.current
    val clipboard = LocalClipboardManager.current
    val historyObservations = record.historyObservations()

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        RemotePlantariaImage(
            imageUrl = record.primaryPhotoUrl,
            contentDescription = "Foto principal de ${record.displayName}",
            fallbackIcon = Icons.Outlined.LocationOn,
            modifier = Modifier
                .fillMaxWidth()
                .aspectRatio(4f / 3f)
                .clip(RoundedCornerShape(8.dp)),
            contentScale = ContentScale.Fit,
        )

        Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Text(
                text = record.displayName,
                style = MaterialTheme.typography.headlineSmall,
                fontWeight = FontWeight.SemiBold,
            )
            record.verifiedScientificName?.let {
                Text(
                    text = it,
                    style = MaterialTheme.typography.titleMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                StatusChip(record.verificationStatus)
                record.plantCondition?.let { TextChip("Estado: $it") }
            }
        }

        ElevatedCard(
            shape = RoundedCornerShape(8.dp),
            colors = CardDefaults.elevatedCardColors(
                containerColor = MaterialTheme.colorScheme.surface,
            ),
        ) {
            Column(
                modifier = Modifier.padding(14.dp),
                verticalArrangement = Arrangement.spacedBy(10.dp),
            ) {
                record.verifiedCommonName?.let { MetadataLine(label = "Nombre común", value = it) }
                record.verifiedScientificName?.let { MetadataLine(label = "Nombre científico", value = it) }
                MetadataLine(label = "Nombre provisional", value = record.provisionalCommonName)
                record.author?.handle?.let { MetadataLine(label = "Autor", value = "@$it") }
                record.createdAt?.let { MetadataLine(label = "Creado", value = it.toReadableDateTime()) }
                MetadataLine(
                    label = "Coordenadas",
                    value = String.format(Locale.US, "%.5f, %.5f", record.latitude, record.longitude),
                )
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedButton(
                        onClick = {
                            val text = String.format(Locale.US, "%.5f, %.5f", record.latitude, record.longitude)
                            clipboard.setText(AnnotatedString(text))
                        },
                    ) {
                        Text("Copiar")
                    }
                    OutlinedButton(
                        onClick = {
                            val uri = Uri.parse("https://www.google.com/maps/search/?api=1&query=${record.latitude},${record.longitude}")
                            context.startActivity(Intent(Intent.ACTION_VIEW, uri))
                        },
                    ) {
                        Text("Google Maps")
                    }
                }
                record.description?.let { DescriptionLine(label = "Descripción", value = it) }
            }
        }

        Button(
            onClick = onAddObservation,
            modifier = Modifier.fillMaxWidth(),
        ) {
            Icon(
                imageVector = Icons.Outlined.Update,
                contentDescription = null,
            )
            Text(
                text = "Añadir observación",
                modifier = Modifier.padding(start = 8.dp),
            )
        }

        Text(
            text = "Historial de observaciones",
            style = MaterialTheme.typography.titleLarge,
            fontWeight = FontWeight.SemiBold,
        )
        if (historyObservations.isEmpty()) {
            EmptyHistoryCard()
        } else {
            historyObservations.forEachIndexed { index, observation ->
                ObservationTimelineCard(
                    observation = observation,
                    index = index,
                )
            }
        }
    }
}

@Composable
private fun EmptyHistoryCard() {
    ElevatedCard(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(8.dp),
    ) {
        Text(
            text = "Todavía no hay observaciones cargadas en esta ficha.",
            modifier = Modifier.padding(16.dp),
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
    }
}

@Composable
private fun ObservationTimelineCard(
    observation: PlantObservation,
    index: Int,
) {
    ElevatedCard(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.elevatedCardColors(
            containerColor = MaterialTheme.colorScheme.surface,
        ),
    ) {
        Column(
            modifier = Modifier.padding(14.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            Text(
                text = observation.observedAt?.toReadableDateTime() ?: observation.publicId,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
            )
            RemotePlantariaImage(
                imageUrl = observation.photoUrl,
                contentDescription = "Foto de observación",
                fallbackIcon = Icons.Outlined.LocationOn,
                modifier = Modifier
                    .fillMaxWidth()
                    .aspectRatio(4f / 3f)
                    .clip(RoundedCornerShape(8.dp)),
                contentScale = ContentScale.Fit,
            )
            if (observation.note.isNullOrBlank()) {
                Text(
                    text = "Sin descripción.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            } else {
                Text(
                    text = observation.note,
                    style = MaterialTheme.typography.bodyMedium,
                )
            }
            Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                MetadataLine(label = "Commit", value = "#${index + 1}")
                observation.author?.handle?.let { handle ->
                    MetadataLine(label = "Usuario", value = "@$handle")
                }
                observation.plantCondition?.let { condition ->
                    MetadataLine(label = "Estado", value = condition)
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
    onAddObservation: (String) -> Unit,
) {
    ElevatedCard(
        modifier = Modifier
            .fillMaxWidth()
            .heightIn(max = 440.dp),
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
                record != null -> RecordDetailContent(
                    record = record,
                    onAddObservation = { onAddObservation(record.publicId) },
                )
            }
        }
    }
}

@Composable
private fun RecordDetailContent(
    record: PlantRecord,
    onAddObservation: () -> Unit,
) {
    val context = LocalContext.current
    val clipboard = LocalClipboardManager.current
    val historyObservations = record.historyObservations()

    RemotePlantariaImage(
        imageUrl = record.primaryPhotoUrl,
        contentDescription = "Foto principal de ${record.displayName}",
        fallbackIcon = Icons.Outlined.LocationOn,
        modifier = Modifier
            .fillMaxWidth()
            .aspectRatio(4f / 3f)
            .clip(RoundedCornerShape(8.dp)),
        contentScale = ContentScale.Fit,
    )

    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        StatusChip(record.verificationStatus)
        record.plantCondition?.let { TextChip("Estado: $it") }
    }

    Button(
        onClick = onAddObservation,
        modifier = Modifier.fillMaxWidth(),
    ) {
        Icon(
            imageVector = Icons.Outlined.Update,
            contentDescription = null,
        )
        Text(
            text = "Añadir observación",
            modifier = Modifier.padding(start = 8.dp),
        )
    }

    DetailLine(label = "ID", value = record.publicId)
    record.verifiedScientificName?.let { DetailLine(label = "Nombre científico", value = it) }
    record.verifiedCommonName?.let { DetailLine(label = "Nombre común verificado", value = it) }
    DetailLine(label = "Nombre provisional", value = record.provisionalCommonName)
    record.description?.let { DetailLine(label = "Descripción", value = it) }
    record.author?.handle?.let { DetailLine(label = "Autor", value = "@$it") }
    DetailLine(
        label = "Coordenadas",
        value = String.format(Locale.US, "%.5f, %.5f", record.latitude, record.longitude),
    )
    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        OutlinedButton(
            onClick = {
                val text = String.format(Locale.US, "%.5f, %.5f", record.latitude, record.longitude)
                clipboard.setText(AnnotatedString(text))
            },
        ) {
            Text("Copiar")
        }
        OutlinedButton(
            onClick = {
                val uri = Uri.parse("https://www.google.com/maps/search/?api=1&query=${record.latitude},${record.longitude}")
                context.startActivity(Intent(Intent.ACTION_VIEW, uri))
            },
        ) {
            Text("Google Maps")
        }
    }
    record.createdAt?.let { DetailLine(label = "Creado", value = it.toReadableDateTime()) }
    record.latestObservationAt?.let { DetailLine(label = "Última observación", value = it.toReadableDateTime()) }

    Text(
        text = "Observaciones (${historyObservations.size})",
        style = MaterialTheme.typography.titleMedium,
        fontWeight = FontWeight.SemiBold,
    )
    if (historyObservations.isEmpty()) {
        Text(
            text = "Todavía no hay observaciones cargadas en esta ficha.",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
    } else {
        historyObservations.forEach { observation ->
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
            contentDescription = "Foto de observación",
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
private fun MetadataLine(label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Text(
            text = label,
            style = MaterialTheme.typography.labelMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Text(
            text = value,
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.SemiBold,
        )
    }
}

@Composable
private fun DescriptionLine(label: String, value: String) {
    Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
        Text(
            text = label,
            style = MaterialTheme.typography.labelMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Text(
            text = value,
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.SemiBold,
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
private fun MapErrorCard(
    error: String,
    onRetry: () -> Unit,
    onClearSearch: () -> Unit,
) {
    ElevatedCard(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(8.dp),
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            Text(
                text = "No se pudo cargar el mapa",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
            )
            Text(
                text = error,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.error,
            )
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Button(
                    onClick = onRetry,
                    modifier = Modifier.weight(1f),
                ) {
                    Icon(
                        imageVector = Icons.Outlined.Refresh,
                        contentDescription = null,
                    )
                    Text(
                        text = "Reintentar",
                        modifier = Modifier.padding(start = 8.dp),
                    )
                }
                OutlinedButton(
                    onClick = onClearSearch,
                    modifier = Modifier.weight(1f),
                ) {
                    Icon(
                        imageVector = Icons.Outlined.RestartAlt,
                        contentDescription = null,
                    )
                    Text(
                        text = "Limpiar",
                        modifier = Modifier.padding(start = 8.dp),
                    )
                }
            }
        }
    }
}

@Composable
private fun EmptyMapCard(
    searchQuery: String,
    onRetry: () -> Unit,
    onClearSearch: () -> Unit,
) {
    ElevatedCard(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(8.dp),
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            Text(
                text = if (searchQuery.isBlank()) "Sin registros" else "Sin coincidencias",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
            )
            Text(
                text = if (searchQuery.isBlank()) {
                    "Todavía no hay reportes visibles. Cuando existan, aparecerán como pines en este mapa."
                } else {
                    "No se encontraron registros para \"$searchQuery\". Prueba otra planta, otro ID o limpia el filtro."
                },
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                Button(
                    onClick = onRetry,
                    modifier = Modifier.weight(1f),
                ) {
                    Icon(
                        imageVector = Icons.Outlined.Refresh,
                        contentDescription = null,
                    )
                    Text(
                        text = "Recargar",
                        modifier = Modifier.padding(start = 8.dp),
                    )
                }
                if (searchQuery.isNotBlank()) {
                    OutlinedButton(
                        onClick = onClearSearch,
                        modifier = Modifier.weight(1f),
                    ) {
                        Icon(
                            imageVector = Icons.Outlined.RestartAlt,
                            contentDescription = null,
                        )
                        Text(
                            text = "Quitar filtro",
                            modifier = Modifier.padding(start = 8.dp),
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun StatusChip(status: String?) {
    val verified = status == "verified"
    val rejected = status == "rejected"
    AssistChip(
        onClick = {},
        label = {
            Text(
                when {
                    verified -> "Verificado"
                    rejected -> "Rechazado"
                    else -> "Pendiente"
                }
            )
        },
        leadingIcon = {
            Icon(
                imageVector = when {
                    verified -> Icons.Outlined.CheckCircle
                    rejected -> Icons.Outlined.Cancel
                    else -> Icons.Outlined.Schedule
                },
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

private fun MapUserLocation.toLatLng(): LatLng {
    return LatLng(latitude, longitude)
}

private fun MapFocusOverride.toLatLng(): LatLng {
    return LatLng(latitude, longitude)
}

private fun PlaceSearchResult.toLatLng(): LatLng {
    return LatLng(latitude, longitude)
}

private fun PlaceSearchResult.shortLabel(): String {
    return displayName.substringBefore(',').trim().ifBlank { displayName }
}

private fun PlantRecord.historyObservations(): List<PlantObservation> {
    return observations.filter { observation -> observation.sourceType != "initial" }
}

private fun MapUserLocation.distanceKmTo(record: MapRecordPreview): Double {
    val result = FloatArray(1)
    Location.distanceBetween(
        latitude,
        longitude,
        record.latitude,
        record.longitude,
        result,
    )

    return result[0] / 1000.0
}

private fun MapUserLocation.distanceTextTo(record: MapRecordPreview): String {
    val distanceKm = distanceKmTo(record)

    return if (distanceKm < 1.0) {
        "${(distanceKm * 1000).roundToInt()} m"
    } else {
        String.format(Locale.US, "%.1f km", distanceKm)
    }
}

private fun String.toReadableDateTime(): String {
    return take(16).replace('T', ' ')
}

private fun Context.hasPlantariaMapLocationPermission(): Boolean {
    return checkSelfPermission(Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED ||
        checkSelfPermission(Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED
}

private fun Context.latestKnownPlantariaMapLocation(): Location? {
    val locationManager = getSystemService(Context.LOCATION_SERVICE) as? LocationManager ?: return null
    val allowFineLocation = checkSelfPermission(Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED

    return locationManager.latestKnownPlantariaMapLocation(allowFineLocation)
        ?.takeIf { location -> location.isRecent(maxAgeMs = MAX_LOCATION_AGE_MS) }
}

@SuppressLint("MissingPermission")
private fun Context.fetchPlantariaMapLocation(
    onLocation: (Location) -> Unit,
    onUnavailable: () -> Unit,
) {
    val locationManager = getSystemService(Context.LOCATION_SERVICE) as? LocationManager
    if (locationManager == null) {
        onUnavailable()
        return
    }

    val allowFineLocation = checkSelfPermission(Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED
    val provider = locationManager.bestPlantariaMapProvider(allowFineLocation)
    if (provider == null) {
        locationManager.latestKnownPlantariaMapLocation(allowFineLocation)
            ?.takeIf { location -> location.isRecent(maxAgeMs = MAX_LOCATION_AGE_MS) }
            ?.let(onLocation)
            ?: onUnavailable()
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
                    ?.takeIf { candidate -> candidate.isRecent(maxAgeMs = MAX_LOCATION_AGE_MS) }
                    ?.let(onLocation)
                    ?: locationManager.latestKnownPlantariaMapLocation(allowFineLocation)
                        ?.takeIf { candidate -> candidate.isRecent(maxAgeMs = MAX_LOCATION_AGE_MS) }
                        ?.let(onLocation)
                    ?: onUnavailable()
            }
        }.onFailure {
            locationManager.latestKnownPlantariaMapLocation(allowFineLocation)
                ?.takeIf { location -> location.isRecent(maxAgeMs = MAX_LOCATION_AGE_MS) }
                ?.let(onLocation)
                ?: onUnavailable()
        }
    } else {
        locationManager.latestKnownPlantariaMapLocation(allowFineLocation)
            ?.takeIf { location -> location.isRecent(maxAgeMs = MAX_LOCATION_AGE_MS) }
            ?.let(onLocation)
            ?: onUnavailable()
    }
}

private fun Location.isRecent(maxAgeMs: Long): Boolean {
    if (time <= 0L) {
        return false
    }

    val age = System.currentTimeMillis() - time
    return age in 0..maxAgeMs
}

private fun LocationManager.bestPlantariaMapProvider(allowFineLocation: Boolean): String? {
    val enabledProviders = runCatching { getProviders(true) }.getOrDefault(emptyList())
    return plantariaMapProviders(allowFineLocation)
        .firstOrNull { provider -> provider in enabledProviders }
}

@SuppressLint("MissingPermission")
private fun LocationManager.latestKnownPlantariaMapLocation(allowFineLocation: Boolean): Location? {
    return plantariaMapProviders(allowFineLocation).mapNotNull { provider ->
        runCatching { getLastKnownLocation(provider) }.getOrNull()
    }.maxByOrNull { location -> location.time }
}

private fun plantariaMapProviders(allowFineLocation: Boolean): List<String> {
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

private fun parseClusterMarker(value: String): MapFocusOverride? {
    if (!value.startsWith(CLUSTER_MARKER_PREFIX)) {
        return null
    }

    val payload = value.removePrefix(CLUSTER_MARKER_PREFIX)
    val parts = payload.split(',', limit = 2)
    if (parts.size != 2) {
        return null
    }

    val latitude = parts[0].toDoubleOrNull() ?: return null
    val longitude = parts[1].toDoubleOrNull() ?: return null
    if (latitude !in -90.0..90.0 || longitude !in -180.0..180.0) {
        return null
    }

    return MapFocusOverride(latitude = latitude, longitude = longitude)
}

@Composable
private fun TourDialog(
    step: Int,
    onNext: () -> Unit,
    onSkip: () -> Unit,
) {
    val steps = listOf(
        "Toca el botón de ubicación para centrar el mapa en tu posición.",
        "Usa el buscador para filtrar por nombre de planta.",
        "Toca un pin para ver el resumen y pulsa \"Abrir\" para ver la ficha completa.",
        "Para crear un reporte u observación, entra en la pestaña \"Acciones\".",
    )

    AlertDialog(
        onDismissRequest = onSkip,
        title = { Text("Tour rápido (${step + 1}/${steps.size})") },
        text = { Text(steps.getOrNull(step).orEmpty()) },
        confirmButton = {
            TextButton(onClick = onNext) {
                Text(if (step >= steps.lastIndex) "Entendido" else "Siguiente")
            }
        },
        dismissButton = {
            TextButton(onClick = onSkip) {
                Text("Saltar")
            }
        },
    )
}
