package com.plantaria.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import com.plantaria.app.ui.PlantariaApp
import com.plantaria.app.ui.theme.PlantariaTheme

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

