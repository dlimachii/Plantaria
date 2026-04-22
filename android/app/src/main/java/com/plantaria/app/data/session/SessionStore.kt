package com.plantaria.app.data.session

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.plantaria.app.data.model.ApiUser
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map

private val Context.sessionDataStore by preferencesDataStore(name = "plantaria_session")

class SessionStore(
    context: Context,
    private val defaultApiBaseUrl: String,
) {
    private val dataStore = context.applicationContext.sessionDataStore

    val session: Flow<AppSession> = dataStore.data.map { preferences ->
        val token = preferences[TOKEN]
        val handle = preferences[HANDLE]

        AppSession(
            token = token,
            apiBaseUrl = preferences[API_BASE_URL] ?: defaultApiBaseUrl,
            user = handle?.let {
                ApiUser(
                    uid = preferences[UID],
                    handle = it,
                    displayName = preferences[DISPLAY_NAME],
                    email = preferences[EMAIL],
                    photoPath = preferences[PHOTO_PATH],
                    country = preferences[COUNTRY],
                    province = preferences[PROVINCE],
                    city = preferences[CITY],
                    role = preferences[ROLE],
                    status = preferences[STATUS],
                )
            },
        )
    }

    suspend fun saveApiBaseUrl(apiBaseUrl: String) {
        dataStore.edit { preferences ->
            preferences[API_BASE_URL] = apiBaseUrl
        }
    }

    suspend fun save(authToken: String, user: ApiUser) {
        dataStore.edit { preferences ->
            preferences[TOKEN] = authToken
            preferences[HANDLE] = user.handle
            writeNullable(preferences, UID, user.uid)
            writeNullable(preferences, DISPLAY_NAME, user.displayName)
            writeNullable(preferences, EMAIL, user.email)
            writeNullable(preferences, PHOTO_PATH, user.photoPath)
            writeNullable(preferences, COUNTRY, user.country)
            writeNullable(preferences, PROVINCE, user.province)
            writeNullable(preferences, CITY, user.city)
            writeNullable(preferences, ROLE, user.role)
            writeNullable(preferences, STATUS, user.status)
        }
    }

    suspend fun clear() {
        dataStore.edit { preferences ->
            preferences.remove(TOKEN)
            preferences.remove(UID)
            preferences.remove(HANDLE)
            preferences.remove(DISPLAY_NAME)
            preferences.remove(EMAIL)
            preferences.remove(PHOTO_PATH)
            preferences.remove(COUNTRY)
            preferences.remove(PROVINCE)
            preferences.remove(CITY)
            preferences.remove(ROLE)
            preferences.remove(STATUS)
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
        val API_BASE_URL = stringPreferencesKey("api_base_url")
        val UID = stringPreferencesKey("uid")
        val HANDLE = stringPreferencesKey("handle")
        val DISPLAY_NAME = stringPreferencesKey("display_name")
        val EMAIL = stringPreferencesKey("email")
        val PHOTO_PATH = stringPreferencesKey("photo_path")
        val COUNTRY = stringPreferencesKey("country")
        val PROVINCE = stringPreferencesKey("province")
        val CITY = stringPreferencesKey("city")
        val ROLE = stringPreferencesKey("role")
        val STATUS = stringPreferencesKey("status")
    }
}
