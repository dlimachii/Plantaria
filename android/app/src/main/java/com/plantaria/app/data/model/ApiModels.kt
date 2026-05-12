package com.plantaria.app.data.model

/** Representación Android del usuario devuelto por la API. */
data class ApiUser(
    val uid: String?,
    val handle: String,
    val displayName: String?,
    val email: String?,
    val photoPath: String?,
    val photoUrl: String?,
    val country: String?,
    val province: String?,
    val city: String?,
    val role: String?,
    val status: String?,
)

/** Resultado de autenticación con token y usuario ya resuelto. */
data class AuthResult(
    val token: String,
    val user: ApiUser,
)

/** Modelo principal de ficha de planta consumido por mapa, detalle y observaciones. */
data class PlantRecord(
    val uid: String?,
    val publicId: String,
    val provisionalCommonName: String,
    val verifiedCommonName: String?,
    val verifiedScientificName: String?,
    val displayName: String,
    val description: String?,
    val primaryPhotoPath: String?,
    val primaryPhotoUrl: String?,
    val plantCondition: String?,
    val verificationStatus: String?,
    val latitude: Double,
    val longitude: Double,
    val latestObservationAt: String?,
    val createdAt: String?,
    val author: RecordAuthor?,
    val observations: List<PlantObservation> = emptyList(),
)

/** Autor resumido asociado a registros u observaciones. */
data class RecordAuthor(
    val handle: String?,
    val displayName: String?,
    val photoPath: String?,
    val photoUrl: String?,
)

/** Resultado normalizado de búsqueda de lugares desde geocodificación. */
data class PlaceSearchResult(
    val displayName: String,
    val latitude: Double,
    val longitude: Double,
    val type: String?,
    val category: String?,
)

/** Observación temporal asociada a un [PlantRecord]. */
data class PlantObservation(
    val publicId: String,
    val photoPath: String?,
    val photoUrl: String?,
    val note: String?,
    val plantCondition: String?,
    val latitude: Double,
    val longitude: Double,
    val sourceType: String?,
    val observedAt: String?,
    val author: RecordAuthor?,
)

/** Evento de actividad propia mostrado en la pestaña de usuario. */
data class UserActivityItem(
    val id: String,
    val type: String,
    val label: String,
    val description: String?,
    val occurredAt: String?,
    val recordPublicId: String?,
    val recordName: String?,
    val photoUrl: String?,
    val status: String?,
)
