package com.plantaria.app.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.outlined.Logout
import androidx.compose.material.icons.outlined.AccountCircle
import androidx.compose.material.icons.outlined.AddLocationAlt
import androidx.compose.material.icons.outlined.CheckCircle
import androidx.compose.material.icons.outlined.EditNote
import androidx.compose.material.icons.outlined.Flag
import androidx.compose.material.icons.outlined.LocationOn
import androidx.compose.material.icons.outlined.MoreVert
import androidx.compose.material.icons.outlined.Refresh
import androidx.compose.material.icons.outlined.Schedule
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.plantaria.app.data.model.ApiUser
import com.plantaria.app.data.model.UserActivityItem
import com.plantaria.app.ui.components.RemotePlantariaImage
import com.plantaria.app.ui.theme.PlantariaColors

@Composable
fun UserScreen(
    contentPadding: PaddingValues,
    user: ApiUser?,
    activity: List<UserActivityItem>,
    isLoading: Boolean,
    onRefresh: () -> Unit,
    onLogout: () -> Unit,
) {
    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
            .padding(contentPadding),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        item {
            ProfileHeader(user = user, onLogout = onLogout)
        }
        item {
            StatsRow(activity = activity)
        }
        item {
            ActivityHeader(isLoading = isLoading, onRefresh = onRefresh)
        }
        items(
            items = activity,
            key = { item -> item.id },
        ) { item ->
            UserActivityRow(item)
        }
        if (activity.isEmpty() && !isLoading) {
            item {
                EmptyActivityCard()
            }
        }
    }
}

@Composable
private fun ProfileHeader(
    user: ApiUser?,
    onLogout: () -> Unit,
) {
    var menuExpanded by remember { mutableStateOf(false) }

    Card(
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Box(
                modifier = Modifier
                    .size(72.dp)
                    .clip(CircleShape)
                    .background(PlantariaColors.Leaf.copy(alpha = 0.14f)),
                contentAlignment = Alignment.Center,
            ) {
                Icon(
                    imageVector = Icons.Outlined.AccountCircle,
                    contentDescription = null,
                    modifier = Modifier.size(48.dp),
                    tint = PlantariaColors.Leaf,
                )
            }
            Column(
                modifier = Modifier
                    .weight(1f)
                    .padding(start = 14.dp),
            ) {
                Text(
                    text = user?.handle?.let { "@$it" } ?: "@usuario",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.SemiBold,
                )
                Text(
                    text = user?.displayName ?: user?.email ?: user?.uid ?: "Sesión activa",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                user?.role?.let {
                    Text(
                        text = roleLabel(it),
                        style = MaterialTheme.typography.labelMedium,
                        color = PlantariaColors.Leaf,
                    )
                }
            }
            Box {
                IconButton(onClick = { menuExpanded = true }) {
                    Icon(
                        imageVector = Icons.Outlined.MoreVert,
                        contentDescription = "Opciones de perfil",
                    )
                }
                DropdownMenu(
                    expanded = menuExpanded,
                    onDismissRequest = { menuExpanded = false },
                ) {
                    DropdownMenuItem(
                        text = { Text("Cerrar sesion") },
                        leadingIcon = {
                            Icon(
                                imageVector = Icons.AutoMirrored.Outlined.Logout,
                                contentDescription = null,
                            )
                        },
                        onClick = {
                            menuExpanded = false
                            onLogout()
                        },
                    )
                }
            }
        }
    }
}

private fun roleLabel(role: String): String {
    return when (role) {
        "admin" -> "Rol: administrador"
        "mod" -> "Rol: moderador"
        else -> "Rol: usuario"
    }
}

@Composable
private fun StatsRow(activity: List<UserActivityItem>) {
    Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
        StatCard(
            label = "Acciones",
            value = activity.size.toString(),
            modifier = Modifier.weight(1f),
        )
        StatCard(
            label = "Reportes",
            value = activity.count { it.type == "record_created" }.toString(),
            modifier = Modifier.weight(1f),
        )
        StatCard(
            label = "Commits",
            value = activity.count { it.type == "observation_created" }.toString(),
            modifier = Modifier.weight(1f),
        )
    }
}

@Composable
private fun StatCard(
    label: String,
    value: String,
    modifier: Modifier = Modifier,
) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(
                text = value,
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.SemiBold,
            )
            Text(
                text = label,
                style = MaterialTheme.typography.labelMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}

@Composable
private fun ActivityHeader(
    isLoading: Boolean,
    onRefresh: () -> Unit,
) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Text(
            text = "Actividad reciente",
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.SemiBold,
        )
        TextButton(
            onClick = onRefresh,
            enabled = !isLoading,
        ) {
            if (isLoading) {
                CircularProgressIndicator(
                    modifier = Modifier.size(18.dp),
                    strokeWidth = 2.dp,
                )
            } else {
                Icon(
                    imageVector = Icons.Outlined.Refresh,
                    contentDescription = null,
                )
            }
            Text(
                text = if (isLoading) "Cargando" else "Actualizar",
                modifier = Modifier.padding(start = 6.dp),
            )
        }
    }
}

@Composable
private fun UserActivityRow(item: UserActivityItem) {
    Card(
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Box(
                modifier = Modifier
                    .size(46.dp)
                    .clip(RoundedCornerShape(8.dp))
                    .background(PlantariaColors.MapBase),
                contentAlignment = Alignment.Center,
            ) {
                if (item.photoUrl != null) {
                    RemotePlantariaImage(
                        imageUrl = item.photoUrl,
                        contentDescription = item.description ?: item.label,
                        modifier = Modifier.fillMaxSize(),
                    )
                } else {
                    Icon(
                        imageVector = activityIcon(item.type),
                        contentDescription = null,
                        tint = PlantariaColors.Leaf,
                    )
                }
            }
            Column(
                modifier = Modifier
                    .weight(1f)
                    .padding(start = 12.dp),
            ) {
                Text(
                    text = item.label,
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.SemiBold,
                )
                Text(
                    text = item.description ?: item.recordName ?: item.recordPublicId ?: "Sin detalle",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                item.occurredAt?.let {
                    Text(
                        text = it.toReadableDateTime(),
                        style = MaterialTheme.typography.labelSmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
            }
            Icon(
                imageVector = if (item.status == "verified" || item.type == "record_verified") {
                    Icons.Outlined.CheckCircle
                } else {
                    Icons.Outlined.Schedule
                },
                contentDescription = item.status,
                tint = if (item.status == "verified" || item.type == "record_verified") {
                    PlantariaColors.Leaf
                } else {
                    PlantariaColors.Earth
                },
            )
        }
    }
    Spacer(modifier = Modifier.height(2.dp))
}

@Composable
private fun EmptyActivityCard() {
    Card(
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            Text(
                text = "Sin actividad reciente",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
            )
            Text(
                text = "Cuando esta cuenta cree reportes, observaciones o acciones de moderacion, apareceran aqui.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}

private fun activityIcon(type: String): androidx.compose.ui.graphics.vector.ImageVector {
    return when (type) {
        "record_created" -> Icons.Outlined.AddLocationAlt
        "observation_created" -> Icons.Outlined.EditNote
        "flag_created", "flag_updated" -> Icons.Outlined.Flag
        "record_verified" -> Icons.Outlined.CheckCircle
        else -> Icons.Outlined.LocationOn
    }
}

private fun String.toReadableDateTime(): String {
    return replace('T', ' ')
        .substringBefore('.')
        .substringBefore('+')
        .substringBefore('Z')
        .take(16)
}
