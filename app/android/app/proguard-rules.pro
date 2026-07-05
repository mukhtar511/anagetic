# Anaqatuk ProGuard/R8 rules.

# Flutter engine
-keep class io.flutter.** { *; }
-keep class io.flutter.plugins.** { *; }
-dontwarn io.flutter.embedding.**

# Keep annotations / native method names used via JNI.
-keepattributes *Annotation*
-keepclasseswithmembernames class * {
    native <methods>;
}

# Pusher (realtime) — reflective access.
-keep class com.pusher.** { *; }
-dontwarn com.pusher.**

# Gson/OkHttp transitive (if pulled by plugins) — silence warnings.
-dontwarn okhttp3.**
-dontwarn okio.**
