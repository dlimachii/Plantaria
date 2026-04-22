package com.plantaria.app.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val LightColorScheme = lightColorScheme(
    primary = PlantariaColors.Leaf,
    onPrimary = Color.White,
    secondary = PlantariaColors.LeafLight,
    tertiary = PlantariaColors.Earth,
    background = Color(0xFFF4F5EF),
    onBackground = PlantariaColors.Ink,
    surface = PlantariaColors.Surface,
    onSurface = PlantariaColors.Ink,
    surfaceVariant = Color(0xFFE3E8DD),
    onSurfaceVariant = Color(0xFF566154),
)

@Composable
fun PlantariaTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit,
) {
    MaterialTheme(
        colorScheme = LightColorScheme,
        typography = PlantariaTypography,
        content = content,
    )
}

