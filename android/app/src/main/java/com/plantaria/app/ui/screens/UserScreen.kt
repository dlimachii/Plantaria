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
import androidx.compose.material.icons.outlined.CheckCircle
import androidx.compose.material.icons.outlined.LocationOn
import androidx.compose.material.icons.outlined.Schedule
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.plantaria.app.data.model.ApiUser
import com.plantaria.app.data.model.PlantRecord
import com.plantaria.app.ui.theme.PlantariaColors

@Composable
fun UserScreen(
    contentPadding: PaddingValues,
    user: ApiUser?,
    apiBaseUrl: String,
    records: List<PlantRecord>,
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
            ProfileHeader(user = user, apiBaseUrl = apiBaseUrl, onLogout = onLogout)
        }
        item {
            StatsRow(records = records)
        }
        item {
            Text(
                text = "Registros cargados",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
            )
        }
        items(records, key = { it.publicId }) { record ->
            UserRecordRow(record)
        }
        if (records.isEmpty()) {
            item {
                Card(
                    shape = RoundedCornerShape(8.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                ) {
                    Text(
                        text = "Todavía no hay registros cargados desde la API.",
                        modifier = Modifier.padding(16.dp),
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
            }
        }
    }
}

@Composable
private fun ProfileHeader(
    user: ApiUser?,
    apiBaseUrl: String,
    onLogout: () -> Unit,
) {
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
                        text = it.uppercase(),
                        style = MaterialTheme.typography.labelMedium,
                        color = PlantariaColors.Leaf,
                    )
                }
                Text(
                    text = apiBaseUrl,
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            OutlinedButton(onClick = onLogout) {
                Icon(
                    imageVector = Icons.AutoMirrored.Outlined.Logout,
                    contentDescription = null,
                )
                Text(
                    text = "Salir",
                    modifier = Modifier.padding(start = 8.dp),
                )
            }
        }
    }
}

@Composable
private fun StatsRow(records: List<PlantRecord>) {
    Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
        StatCard(
            label = "Registros",
            value = records.size.toString(),
            modifier = Modifier.weight(1f),
        )
        StatCard(
            label = "Verificados",
            value = records.count { it.verificationStatus == "verified" }.toString(),
            modifier = Modifier.weight(1f),
        )
        StatCard(
            label = "Pendientes",
            value = records.count { it.verificationStatus == "pending" }.toString(),
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
private fun UserRecordRow(record: PlantRecord) {
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
                Icon(
                    imageVector = Icons.Outlined.LocationOn,
                    contentDescription = null,
                    tint = PlantariaColors.Leaf,
                )
            }
            Column(
                modifier = Modifier
                    .weight(1f)
                    .padding(start = 12.dp),
            ) {
                Text(
                    text = record.displayName,
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.SemiBold,
                )
                Text(
                    text = "${record.publicId} · ${record.latitude}, ${record.longitude}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            Icon(
                imageVector = if (record.verificationStatus == "verified") {
                    Icons.Outlined.CheckCircle
                } else {
                    Icons.Outlined.Schedule
                },
                contentDescription = record.verificationStatus,
                tint = if (record.verificationStatus == "verified") PlantariaColors.Leaf else PlantariaColors.Earth,
            )
        }
    }
    Spacer(modifier = Modifier.height(2.dp))
}
