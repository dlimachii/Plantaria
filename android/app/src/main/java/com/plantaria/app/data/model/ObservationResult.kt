package com.plantaria.app.data.model

data class ObservationResult(
    val publicId: String,
    val recordPublicId: String,
    val photoPath: String,
    val note: String?,
    val latitude: Double,
    val longitude: Double,
    val observedAt: String?,
)

