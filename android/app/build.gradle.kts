import org.jetbrains.kotlin.gradle.dsl.JvmTarget

plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.plugin.compose")
    id("org.jetbrains.dokka")
}

android {
    namespace = "com.plantaria.app"
    compileSdk = 36

    flavorDimensions += "install"
    productFlavors {
        create("prod") {
            dimension = "install"
            resValue("string", "app_name", "Plantaria")
            buildConfigField("boolean", "PLANTARIA_MAP_STYLE_PICKER_ENABLED", "true")
            buildConfigField(
                "String",
                "PLANTARIA_API_BASE_URL",
                "\"https://api.dlimachii.com/api/\"",
            )
        }
        create("demoA") {
            dimension = "install"
            applicationIdSuffix = ".demoa"
            resValue("string", "app_name", "Plantaria Demo A")
        }
        create("demoB") {
            dimension = "install"
            applicationIdSuffix = ".demob"
            resValue("string", "app_name", "Plantaria Demo B")
        }
        create("demoC") {
            dimension = "install"
            applicationIdSuffix = ".democ"
            resValue("string", "app_name", "Plantaria Demo C")
        }
        create("demoPJ") {
            dimension = "install"
            applicationIdSuffix = ".demopj"
            resValue("string", "app_name", "Plantaria")
            buildConfigField("boolean", "PLANTARIA_MAP_STYLE_PICKER_ENABLED", "true")
            buildConfigField(
                "String",
                "PLANTARIA_API_BASE_URL",
                "\"https://api.dlimachii.com/api/\"",
            )
        }
    }

    defaultConfig {
        applicationId = "com.plantaria.app"
        minSdk = 26
        targetSdk = 36
        versionCode = 1
        versionName = "0.1.0"
        buildConfigField(
            "String",
            "PLANTARIA_API_BASE_URL",
            "\"https://api.dlimachii.com/api/\"",
        )
        buildConfigField(
            "String",
            "PLANTARIA_MAP_STYLE_URL",
            "\"https://demotiles.maplibre.org/style.json\"",
        )
        buildConfigField(
            "String",
            "PLANTARIA_BOOTSTRAP_CONFIG_URL",
            "\"\"",
        )
        buildConfigField(
            "boolean",
            "PLANTARIA_MAP_STYLE_PICKER_ENABLED",
            "false",
        )
    }

    buildFeatures {
        buildConfig = true
        compose = true
        resValues = true
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlin {
        compilerOptions {
            jvmTarget.set(JvmTarget.JVM_17)
        }
    }
}

dependencies {
    val composeBom = platform("androidx.compose:compose-bom:2026.03.00")

    implementation(composeBom)
    androidTestImplementation(composeBom)

    implementation("androidx.activity:activity-compose:1.13.0")
    implementation("androidx.compose.foundation:foundation")
    implementation("androidx.compose.material:material-icons-extended")
    implementation("androidx.compose.material3:material3")
    implementation("androidx.compose.ui:ui")
    implementation("androidx.compose.ui:ui-tooling-preview")
    implementation("androidx.datastore:datastore-preferences:1.2.1")
    implementation("androidx.lifecycle:lifecycle-runtime-compose:2.10.0")
    implementation("androidx.lifecycle:lifecycle-viewmodel-compose:2.10.0")
    implementation("androidx.lifecycle:lifecycle-viewmodel-ktx:2.10.0")
    implementation("androidx.navigation:navigation-compose:2.9.7")
    implementation("org.maplibre.gl:android-sdk:13.0.2")
    dokkaPlugin("org.jetbrains.dokka:android-documentation-plugin:2.2.0")

    debugImplementation("androidx.compose.ui:ui-test-manifest")
    debugImplementation("androidx.compose.ui:ui-tooling")
}

dokka {
    moduleName.set("Plantaria Android")

    dokkaPublications.html {
        outputDirectory.set(layout.buildDirectory.dir("documentation/html"))
        includes.from("dokka/module.md")
    }

    dokkaSourceSets.configureEach {
        displayName.set("androidApp")
        includes.from("dokka/packages.md")
        skipEmptyPackages.set(true)
        reportUndocumented.set(false)
        sourceRoots.from(file("src/main/java"))
        suppress.set(name != "prodRelease")
    }
}
