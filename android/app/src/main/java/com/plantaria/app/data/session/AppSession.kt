package com.plantaria.app.data.session

import com.plantaria.app.data.model.ApiUser

data class AppSession(
    val token: String? = null,
    val user: ApiUser? = null,
    val apiBaseUrl: String = "",
) {
    val isAuthenticated: Boolean = !token.isNullOrBlank()
}
