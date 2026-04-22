package com.plantaria.app.data.model

data class ApiUser(
    val uid: String?,
    val handle: String,
    val displayName: String?,
    val email: String?,
    val photoPath: String?,
    val country: String?,
    val province: String?,
    val city: String?,
    val role: String?,
    val status: String?,
)

data class AuthResult(
    val token: String,
    val user: ApiUser,
)

data class PlantRecord(
    val uid: String?,
    val publicId: String,
    val provisionalCommonName: String,
    val verifiedCommonName: String?,
    val verifiedScientificName: String?,
    val displayName: String,
    val description: String?,
    val primaryPhotoPath: String?,
    val plantCondition: String?,
    val verificationStatus: String?,
    val latitude: Double,
    val longitude: Double,
    val latestObservationAt: String?,
    val createdAt: String?,
    val author: RecordAuthor?,
)

data class RecordAuthor(
    val handle: String?,
    val displayName: String?,
    val photoPath: String?,
)

