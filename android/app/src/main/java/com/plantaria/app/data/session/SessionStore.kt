package com.plantaria.app.data.session

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.booleanPreferencesKey
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.plantaria.app.data.model.ApiUser
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

private val Context.sessionDataStore by preferencesDataStore(name = "plantaria_session")

/**
 * Capa de persistencia ligera de la sesión Android basada en DataStore.
 *
 * También gestiona la URL base de API y pequeñas preferencias de onboarding para evitar
 * inconsistencias entre entornos local, túnel temporal y despliegue público.
 */
class SessionStore(
    context: Context,
    private val defaultApiBaseUrl: String,
) {
    private val dataStore = context.applicationContext.sessionDataStore

    /** Flujo observable con la sesión reconstruida desde preferencias persistidas. */
    val session: Flow<AppSession> = dataStore.data.map { preferences ->
        val token = preferences[TOKEN]
        val handle = preferences[HANDLE]
        val explicitApiBaseUrl = preferences[API_BASE_URL]
        val apiBaseUrl = explicitApiBaseUrl ?: defaultApiBaseUrl
        val isMapTourSeen = preferences[MAP_TOUR_SEEN] == true

        AppSession(
            token = token,
            apiBaseUrl = apiBaseUrl,
            isApiBaseUrlExplicit = explicitApiBaseUrl != null,
            isMapTourSeen = isMapTourSeen,
            user = handle?.let {
                ApiUser(
                    uid = preferences[UID],
                    handle = it,
                    displayName = preferences[DISPLAY_NAME],
                    email = preferences[EMAIL],
                    photoPath = preferences[PHOTO_PATH],
                    photoUrl = preferences[PHOTO_URL],
                    country = preferences[COUNTRY],
                    province = preferences[PROVINCE],
                    city = preferences[CITY],
                    role = preferences[ROLE],
                    status = preferences[STATUS],
                )
            },
        )
    }

    suspend fun save(authToken: String, user: ApiUser) {
        dataStore.edit { preferences ->
            preferences[TOKEN] = authToken
            preferences[HANDLE] = user.handle
            writeNullable(preferences, UID, user.uid)
            writeNullable(preferences, DISPLAY_NAME, user.displayName)
            writeNullable(preferences, EMAIL, user.email)
            writeNullable(preferences, PHOTO_PATH, user.photoPath)
            writeNullable(preferences, PHOTO_URL, user.photoUrl)
            writeNullable(preferences, COUNTRY, user.country)
            writeNullable(preferences, PROVINCE, user.province)
            writeNullable(preferences, CITY, user.city)
            writeNullable(preferences, ROLE, user.role)
            writeNullable(preferences, STATUS, user.status)
        }
    }

    suspend fun markMapTourSeen() {
        dataStore.edit { preferences ->
            preferences[MAP_TOUR_SEEN] = true
        }
    }

    /**
     * Guarda una URL base de API explícita y limpia la sesión previa para evitar mezclar tokens
     * emitidos por distintos servidores.
     */
    suspend fun saveApiBaseUrl(apiBaseUrl: String) {
        dataStore.edit { preferences ->
            // Changing servers invalidates any existing session token/user data.
            preferences[API_BASE_URL] = apiBaseUrl
            clearUserSession(preferences)
        }
    }

    /** Vuelve a la URL base por defecto de la build y descarta la sesión almacenada. */
    suspend fun resetApiBaseUrlToDefault() {
        dataStore.edit { preferences ->
            preferences.remove(API_BASE_URL)
            clearUserSession(preferences)
        }
    }

    private fun clearUserSession(
        preferences: androidx.datastore.preferences.core.MutablePreferences,
    ) {
        preferences.remove(TOKEN)
        preferences.remove(UID)
        preferences.remove(HANDLE)
        preferences.remove(DISPLAY_NAME)
        preferences.remove(EMAIL)
        preferences.remove(PHOTO_PATH)
        preferences.remove(PHOTO_URL)
        preferences.remove(COUNTRY)
        preferences.remove(PROVINCE)
        preferences.remove(CITY)
        preferences.remove(ROLE)
        preferences.remove(STATUS)
    }

    suspend fun clear() {
        dataStore.edit { preferences ->
            preferences.remove(API_BASE_URL)
            clearUserSession(preferences)
        }
    }

    private fun writeNullable(
        preferences: androidx.datastore.preferences.core.MutablePreferences,
        key: androidx.datastore.preferences.core.Preferences.Key<String>,
        value: String?,
    ) {
        if (value.isNullOrBlank()) {
            preferences.remove(key)
        } else {
            preferences[key] = value
        }
    }

    private companion object {
        val TOKEN = stringPreferencesKey("token")
        val UID = stringPreferencesKey("uid")
        val HANDLE = stringPreferencesKey("handle")
        val DISPLAY_NAME = stringPreferencesKey("display_name")
        val EMAIL = stringPreferencesKey("email")
        val PHOTO_PATH = stringPreferencesKey("photo_path")
        val PHOTO_URL = stringPreferencesKey("photo_url")
        val COUNTRY = stringPreferencesKey("country")
        val PROVINCE = stringPreferencesKey("province")
        val CITY = stringPreferencesKey("city")
        val ROLE = stringPreferencesKey("role")
        val STATUS = stringPreferencesKey("status")
        val API_BASE_URL = stringPreferencesKey("api_base_url")
        val MAP_TOUR_SEEN = booleanPreferencesKey("map_tour_seen")
    }
}
