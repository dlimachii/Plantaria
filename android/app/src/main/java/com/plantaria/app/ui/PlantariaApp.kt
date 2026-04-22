package com.plantaria.app.ui

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.Add
import androidx.compose.material.icons.outlined.Map
import androidx.compose.material.icons.outlined.Person
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavDestination.Companion.hierarchy
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.plantaria.app.ui.screens.ActionsScreen
import com.plantaria.app.ui.screens.AuthScreen
import com.plantaria.app.ui.screens.MapScreen
import com.plantaria.app.ui.screens.UserScreen
import com.plantaria.app.ui.state.PlantariaViewModel

@Composable
fun PlantariaApp(
    viewModel: PlantariaViewModel = viewModel(),
) {
    val uiState = viewModel.uiState

    if (!uiState.authChecked) {
        Box(
            modifier = Modifier.fillMaxSize(),
            contentAlignment = Alignment.Center,
        ) {
            CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
        }
        return
    }

    if (!uiState.session.isAuthenticated) {
        AuthScreen(
            apiBaseUrl = uiState.session.apiBaseUrl,
            isLoading = uiState.isAuthLoading,
            message = uiState.message,
            error = uiState.error,
            onApiBaseUrlChange = viewModel::updateApiBaseUrl,
            onLogin = viewModel::login,
            onRegister = viewModel::register,
        )
        return
    }

    val navController = rememberNavController()
    val destinations = listOf(
        PlantariaDestination.Map,
        PlantariaDestination.Actions,
        PlantariaDestination.User,
    )

    Scaffold(
        bottomBar = {
            val navBackStackEntry by navController.currentBackStackEntryAsState()
            val currentDestination = navBackStackEntry?.destination

            NavigationBar {
                destinations.forEach { destination ->
                    NavigationBarItem(
                        selected = currentDestination?.hierarchy?.any { it.route == destination.route } == true,
                        onClick = {
                            navController.navigate(destination.route) {
                                popUpTo(navController.graph.startDestinationId) {
                                    saveState = true
                                }
                                launchSingleTop = true
                                restoreState = true
                            }
                        },
                        icon = {
                            Icon(
                                imageVector = destination.icon,
                                contentDescription = destination.label,
                            )
                        },
                        label = { Text(destination.label) },
                    )
                }
            }
        },
    ) { innerPadding ->
        NavHost(
            navController = navController,
            startDestination = PlantariaDestination.Map.route,
            modifier = Modifier,
        ) {
            composable(PlantariaDestination.Map.route) {
                MapScreen(
                    contentPadding = innerPadding,
                    records = uiState.records,
                    selectedRecordDetail = uiState.selectedRecordDetail,
                    searchQuery = uiState.searchQuery,
                    isLoading = uiState.isRecordsLoading,
                    isRecordDetailLoading = uiState.isRecordDetailLoading,
                    error = uiState.error,
                    recordDetailError = uiState.recordDetailError,
                    onSearchQueryChange = viewModel::updateSearchQuery,
                    onSearchSubmit = viewModel::refreshRecords,
                    onRecordPreviewClick = viewModel::openRecordDetail,
                    onCloseRecordDetail = viewModel::closeRecordDetail,
                    onAddObservationForRecord = { publicId ->
                        viewModel.prepareObservationForRecord(publicId)
                        navController.navigate(PlantariaDestination.Actions.route) {
                            popUpTo(navController.graph.startDestinationId) {
                                saveState = true
                            }
                            launchSingleTop = true
                            restoreState = true
                        }
                    },
                )
            }
            composable(PlantariaDestination.Actions.route) {
                ActionsScreen(
                    contentPadding = innerPadding,
                    prefilledObservationRecordId = uiState.observationRecordPrefillId,
                    observationPrefillVersion = uiState.observationRecordPrefillVersion,
                    isCreateRecordLoading = uiState.isCreateRecordLoading,
                    isCreateObservationLoading = uiState.isCreateObservationLoading,
                    message = uiState.message,
                    error = uiState.error,
                    onCreateRecord = viewModel::createRecord,
                    onCreateObservation = viewModel::createObservation,
                )
            }
            composable(PlantariaDestination.User.route) {
                UserScreen(
                    contentPadding = innerPadding,
                    user = uiState.session.user,
                    apiBaseUrl = uiState.session.apiBaseUrl,
                    records = uiState.records,
                    onLogout = viewModel::logout,
                )
            }
        }
    }
}

private sealed class PlantariaDestination(
    val route: String,
    val label: String,
    val icon: androidx.compose.ui.graphics.vector.ImageVector,
) {
    data object Map : PlantariaDestination("map", "Mapa", Icons.Outlined.Map)
    data object Actions : PlantariaDestination("actions", "Acciones", Icons.Outlined.Add)
    data object User : PlantariaDestination("user", "Usuario", Icons.Outlined.Person)
}
