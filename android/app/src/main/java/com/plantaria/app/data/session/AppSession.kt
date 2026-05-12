package com.plantaria.app.data.session

import com.plantaria.app.data.model.ApiUser

/**
 * Estado persistible de sesión del cliente Android.
 *
 * Guarda token, usuario y configuración local mínima para reanudar la app contra el backend
 * correcto después de reinicios o cambios de servidor.
 */
data class AppSession(
    val token: String? = null,
    val user: ApiUser? = null,
    val apiBaseUrl: String = "",
    val isApiBaseUrlExplicit: Boolean = false,
    val isMapTourSeen: Boolean = false,
) {
    /** Indica si la sesión actual dispone de un token utilizable para la API. */
    val isAuthenticated: Boolean = !token.isNullOrBlank()
}
