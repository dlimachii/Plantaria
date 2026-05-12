package com.plantaria.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import com.plantaria.app.ui.PlantariaApp
import com.plantaria.app.ui.theme.PlantariaTheme

/**
 * Activity principal de la app Android.
 *
 * Inicializa el tema Compose y delega toda la navegación y el estado de UI en [PlantariaApp].
 */
class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            PlantariaTheme {
                PlantariaApp()
            }
        }
    }
}
