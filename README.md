# SpendSense

SpendSense is an Android expense tracker built with Kotlin and Jetpack Compose. It helps track day-to-day spending by reading transaction SMS messages, saving parsed transactions locally, and showing spending history, budgets, insights, and trip expenses in one place.

The app is designed for personal finance tracking on Android devices. It stores transaction data locally with Room and uses an optional Groq API key for AI-powered expense chat or insights.

## What It Does

- Reads bank and payment SMS messages after the user grants permission.
- Parses transaction amount, merchant, bank, account suffix, and transaction type.
- Saves transactions locally using Room.
- Shows a dashboard, history, manual entry screen, budget view, insights, trip expenses, and settings.
- Starts a foreground service so new transaction SMS messages can be processed.

## Tech Stack

- Kotlin
- Jetpack Compose
- Material 3
- Room database
- Navigation Compose
- Coroutines
- Gradle Kotlin DSL

## Requirements

- Android Studio
- Android SDK installed locally
- JDK 11 or newer
- Android device or emulator
- Optional: Groq API key

## Local Setup

1. Clone the repository.

   ```bash
   git clone https://github.com/YOUR_USERNAME/YOUR_REPOSITORY_NAME.git
   cd YOUR_REPOSITORY_NAME
   ```

2. Open the project in Android Studio.

3. Create a `local.properties` file in the project root if Android Studio has not created one.

   ```properties
   sdk.dir=C\:\\Users\\YOUR_NAME\\AppData\\Local\\Android\\Sdk
   GROQ_API_KEY=your_groq_api_key_here
   ```

4. Sync Gradle.

5. Run the app on an emulator or Android phone.

## Important Security Note

Do not commit `local.properties`. It contains machine-specific paths and may contain private API keys. This repository ignores it by default.

If an API key was ever shared publicly, revoke it from the provider dashboard and create a new one.

## Build From Terminal

Use the Gradle wrapper from the project root:

```bash
./gradlew assembleDebug
```

On Windows PowerShell:

```powershell
.\gradlew.bat assembleDebug
```

The debug APK will be generated under:

```text
app/build/outputs/apk/debug/
```

## Project Structure

```text
app/src/main/java/com/test/myapplication/
  data/        Room database and transaction model
  parser/      SMS transaction parser
  receiver/    SMS and boot receivers
  ui/          Compose theme and ViewModel
  utils/       Helper utilities
  MainActivity.kt
  SpendSenseService.kt

web/
  Static web files related to the project
```

## Permissions

The app requests SMS, notification, foreground service, boot completed, and internet permissions. SMS permissions are required for automatic transaction detection.

## License

Add your license here before publishing if you plan to make the project public.
