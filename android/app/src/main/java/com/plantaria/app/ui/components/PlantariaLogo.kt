package com.plantaria.app.ui.components

import androidx.compose.animation.core.Animatable
import androidx.compose.animation.core.FastOutSlowInEasing
import androidx.compose.animation.core.tween
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.mutableFloatStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.StrokeJoin
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.drawscope.withTransform
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.flow.collectLatest

@Composable
fun PlantariaAnimatedLogo(
    modifier: Modifier = Modifier,
    playAnimation: Boolean = true,
) {
    val progress = remember { Animatable(if (playAnimation) 0f else 1f) }
    val animatedProgress = remember { mutableFloatStateOf(if (playAnimation) 0f else 1f) }
    val density = LocalDensity.current

    LaunchedEffect(playAnimation) {
        if (playAnimation) {
            progress.snapTo(0f)
            progress.animateTo(
                targetValue = 1f,
                animationSpec = tween(durationMillis = 2200, easing = FastOutSlowInEasing),
            )
        } else {
            progress.snapTo(1f)
        }
    }

    LaunchedEffect(progress) {
        snapshotFlow { progress.value }.collectLatest { value ->
            animatedProgress.floatValue = value
        }
    }

    val p = animatedProgress.floatValue
    val circleIn = phase(p, 0.02f, 0.24f)
    val leafIn = phase(p, 0.42f, 0.64f)
    val strokeIn = phase(p, 0.34f, 0.70f)
    val veinIn = phase(p, 0.54f, 0.76f)
    val moveLeft = phase(p, 0.68f, 0.84f)
    val textIn = phase(p, 0.82f, 1f)

    Box(
        modifier = modifier
            .width(224.dp)
            .height(72.dp),
        contentAlignment = Alignment.CenterStart,
    ) {
        Canvas(
            modifier = Modifier
                .size(60.dp)
                .graphicsLayer {
                    translationX = with(density) { 82.dp.toPx() } * (1f - moveLeft)
                    scaleX = 0.92f + (0.08f * circleIn)
                    scaleY = 0.92f + (0.08f * circleIn)
                    alpha = circleIn
                },
        ) {
            val logoScale = size.minDimension / 60f
            val leafPath = Path().apply {
                moveTo(30f, 46f)
                cubicTo(46f, 36f, 52f, 20f, 40f, 12f)
                cubicTo(28f, 18f, 20f, 30f, 22f, 41f)
                cubicTo(23f, 44f, 27f, 47f, 30f, 46f)
                close()
            }

            withTransform({
                scale(logoScale, logoScale)
            }) {
                drawCircle(
                    color = Color(0xFF2E7D32),
                    radius = 28f,
                    center = Offset(30f, 30f),
                )
                drawPath(
                    path = leafPath,
                    color = Color(0xFF66BB6A),
                    alpha = leafIn,
                )
                drawPath(
                    path = leafPath,
                    color = Color(0xFF1B5E20),
                    style = Stroke(width = 2.2f, cap = StrokeCap.Round, join = StrokeJoin.Round),
                    alpha = strokeIn,
                )
                drawLine(
                    color = Color(0xFF1B5E20),
                    start = Offset(30f, 46f),
                    end = Offset(38f, 18f),
                    strokeWidth = 2.2f,
                    cap = StrokeCap.Round,
                    alpha = veinIn,
                )
            }
        }

        Text(
            text = "Plantaria",
            modifier = Modifier
                .align(Alignment.CenterStart)
                .padding(start = 72.dp)
                .graphicsLayer {
                    alpha = textIn
                    translationX = with(density) { (-10).dp.toPx() } * (1f - textIn)
                },
            style = MaterialTheme.typography.headlineLarge,
            fontWeight = FontWeight.Bold,
            color = Color(0xFF1B1B1B),
        )
    }
}

private fun phase(progress: Float, start: Float, end: Float): Float {
    return ((progress - start) / (end - start)).coerceIn(0f, 1f)
}
