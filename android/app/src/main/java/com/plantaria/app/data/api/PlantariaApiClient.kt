package com.plantaria.app.data.api

import com.plantaria.app.data.model.ApiUser
import com.plantaria.app.data.model.AuthResult
import com.plantaria.app.data.model.ObservationResult
import com.plantaria.app.data.model.PlantRecord
import com.plantaria.app.data.model.RecordAuthor
import java.io.IOException
import java.net.HttpURLConnection
import java.net.URL
import java.net.URLEncoder
import java.util.UUID
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONArray
import org.json.JSONObject

class PlantariaApiClient(
    private val baseUrl: String,
) {
    suspend fun login(
        handle: String,
        password: String,
    ): AuthResult {
        val body = JSONObject()
            .put("handle", handle)
            .put("password", password)
            .put("device_name", "plantaria-android")

        val response = request(
            path = "auth/login",
            method = "POST",
            body = body,
        )

        return response.toAuthResult()
    }

    suspend fun register(
        handle: String,
        displayName: String,
        email: String,
        password: String,
        passwordConfirmation: String,
        country: String,
        province: String?,
        city: String?,
    ): AuthResult {
        val body = JSONObject()
            .put("handle", handle)
            .put("display_name", displayName)
            .put("email", email)
            .put("password", password)
            .put("password_confirmation", passwordConfirmation)
            .put("country", country)
            .put("device_name", "plantaria-android")

        body.putNullable("province", province)
        body.putNullable("city", city)

        val response = request(
            path = "auth/register",
            method = "POST",
            body = body,
        )

        return response.toAuthResult()
    }

    suspend fun me(token: String): ApiUser {
        val response = request(
            path = "auth/me",
            method = "GET",
            token = token,
        )

        return response.getJSONObject("user").toApiUser()
    }

    suspend fun logout(token: String) {
        request(
            path = "auth/logout",
            method = "POST",
            token = token,
        )
    }

    suspend fun records(query: String? = null): List<PlantRecord> {
        val encodedQuery = query
            ?.trim()
            ?.takeIf { it.isNotBlank() }
            ?.let { URLEncoder.encode(it, Charsets.UTF_8.name()) }
        val path = if (encodedQuery == null) {
            "records?limit=50"
        } else {
            "records?limit=50&q=$encodedQuery"
        }

        val response = request(
            path = path,
            method = "GET",
        )

        return response.getJSONArray("data").toPlantRecords()
    }

    suspend fun createRecord(
        token: String,
        provisionalCommonName: String,
        description: String?,
        primaryPhotoPath: String,
        latitude: Double,
        longitude: Double,
    ): PlantRecord {
        val body = JSONObject()
            .put("provisional_common_name", provisionalCommonName)
            .put("primary_photo_path", primaryPhotoPath)
            .put("latitude", latitude)
            .put("longitude", longitude)

        body.putNullable("description", description)

        val response = request(
            path = "records",
            method = "POST",
            token = token,
            body = body,
        )

        return response.getJSONObject("data").toPlantRecord()
    }

    suspend fun createObservation(
        token: String,
        recordPublicId: String,
        photoPath: String,
        note: String?,
        latitude: Double,
        longitude: Double,
    ): ObservationResult {
        val body = JSONObject()
            .put("photo_path", photoPath)
            .put("latitude", latitude)
            .put("longitude", longitude)

        body.putNullable("note", note)

        val response = request(
            path = "records/${URLEncoder.encode(recordPublicId, Charsets.UTF_8.name())}/observations",
            method = "POST",
            token = token,
            body = body,
        )

        val data = response.getJSONObject("data")
        return ObservationResult(
            publicId = data.getString("public_id"),
            recordPublicId = data.getString("record_public_id"),
            photoPath = data.getString("photo_path"),
            note = data.optNullableString("note"),
            latitude = data.optDouble("latitude"),
            longitude = data.optDouble("longitude"),
            observedAt = data.optNullableString("observed_at"),
        )
    }

    suspend fun uploadPhoto(
        token: String,
        bytes: ByteArray,
        fileName: String,
        mimeType: String,
    ): String = withContext(Dispatchers.IO) {
        val boundary = "PlantariaBoundary${UUID.randomUUID()}"
        val lineBreak = "\r\n"
        val connection = (URL(baseUrl.normalized() + "uploads/photos").openConnection() as HttpURLConnection)
        connection.requestMethod = "POST"
        connection.connectTimeout = 20_000
        connection.readTimeout = 30_000
        connection.doOutput = true
        connection.setRequestProperty("Accept", "application/json")
        connection.setRequestProperty("Authorization", "Bearer $token")
        connection.setRequestProperty("Content-Type", "multipart/form-data; boundary=$boundary")

        connection.outputStream.use { output ->
            output.write("--$boundary$lineBreak".toByteArray())
            output.write(
                "Content-Disposition: form-data; name=\"photo\"; filename=\"$fileName\"$lineBreak"
                    .toByteArray()
            )
            output.write("Content-Type: $mimeType$lineBreak$lineBreak".toByteArray())
            output.write(bytes)
            output.write(lineBreak.toByteArray())
            output.write("--$boundary--$lineBreak".toByteArray())
        }

        val statusCode = connection.responseCode
        val responseText = try {
            val stream = if (statusCode in 200..299) connection.inputStream else connection.errorStream
            stream?.bufferedReader()?.use { it.readText() }.orEmpty()
        } finally {
            connection.disconnect()
        }

        if (statusCode !in 200..299) {
            throw ApiException(statusCode, responseText.apiMessage())
        }

        JSONObject(responseText)
            .getJSONObject("data")
            .getString("path")
    }

    private suspend fun request(
        path: String,
        method: String,
        token: String? = null,
        body: JSONObject? = null,
    ): JSONObject = withContext(Dispatchers.IO) {
        val connection = (URL(baseUrl.normalized() + path.trimStart('/')).openConnection() as HttpURLConnection)
        connection.requestMethod = method
        connection.connectTimeout = 10_000
        connection.readTimeout = 10_000
        connection.setRequestProperty("Accept", "application/json")
        connection.setRequestProperty("Content-Type", "application/json")
        token?.let { connection.setRequestProperty("Authorization", "Bearer $it") }

        if (body != null) {
            connection.doOutput = true
            connection.outputStream.use { output ->
                output.write(body.toString().toByteArray(Charsets.UTF_8))
            }
        }

        val statusCode = connection.responseCode
        val responseText = try {
            val stream = if (statusCode in 200..299) connection.inputStream else connection.errorStream
            stream?.bufferedReader()?.use { it.readText() }.orEmpty()
        } finally {
            connection.disconnect()
        }

        if (statusCode !in 200..299) {
            val message = responseText.apiMessage()
            throw ApiException(statusCode, message)
        }

        if (responseText.isBlank()) {
            JSONObject()
        } else {
            JSONObject(responseText)
        }
    }

    private fun JSONObject.toAuthResult(): AuthResult {
        return AuthResult(
            token = getString("token"),
            user = getJSONObject("user").toApiUser(),
        )
    }

    private fun JSONObject.toApiUser(): ApiUser {
        return ApiUser(
            uid = optNullableString("uid"),
            handle = optString("handle"),
            displayName = optNullableString("display_name"),
            email = optNullableString("email"),
            photoPath = optNullableString("photo_path"),
            country = optNullableString("country"),
            province = optNullableString("province"),
            city = optNullableString("city"),
            role = optNullableString("role"),
            status = optNullableString("status"),
        )
    }

    private fun JSONArray.toPlantRecords(): List<PlantRecord> {
        return buildList {
            for (index in 0 until length()) {
                add(getJSONObject(index).toPlantRecord())
            }
        }
    }

    private fun JSONObject.toPlantRecord(): PlantRecord {
        val authorJson = optJSONObject("author")
        return PlantRecord(
            uid = optNullableString("uid"),
            publicId = optString("public_id"),
            provisionalCommonName = optString("provisional_common_name"),
            verifiedCommonName = optNullableString("verified_common_name"),
            verifiedScientificName = optNullableString("verified_scientific_name"),
            displayName = optString("display_name", optString("provisional_common_name")),
            description = optNullableString("description"),
            primaryPhotoPath = optNullableString("primary_photo_path"),
            plantCondition = optNullableString("plant_condition"),
            verificationStatus = optNullableString("verification_status"),
            latitude = optDouble("latitude"),
            longitude = optDouble("longitude"),
            latestObservationAt = optNullableString("latest_observation_at"),
            createdAt = optNullableString("created_at"),
            author = authorJson?.let {
                RecordAuthor(
                    handle = it.optNullableString("handle"),
                    displayName = it.optNullableString("display_name"),
                    photoPath = it.optNullableString("photo_path"),
                )
            },
        )
    }

    private fun JSONObject.putNullable(key: String, value: String?) {
        if (value.isNullOrBlank()) {
            put(key, JSONObject.NULL)
        } else {
            put(key, value)
        }
    }

    private fun JSONObject.optNullableString(key: String): String? {
        if (!has(key) || isNull(key)) {
            return null
        }

        return optString(key).takeIf { it.isNotBlank() }
    }

    private fun String.normalized(): String {
        return if (endsWith("/")) this else "$this/"
    }

    private fun String.apiMessage(): String {
        return try {
            val json = JSONObject(this)
            json.optString("message").takeIf { it.isNotBlank() }
                ?: "Error de API sin mensaje."
        } catch (_: Exception) {
            if (isBlank()) "Error de red sin respuesta." else this
        }
    }
}

class ApiException(
    val statusCode: Int,
    override val message: String,
) : IOException(message)
