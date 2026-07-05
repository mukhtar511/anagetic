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

# Pusher pulls java-pusher-client → slf4j, which references an optional binder
# class that isn't shipped. Silence it so R8 doesn't fail on the missing class.
-dontwarn org.slf4j.**
-keep class org.slf4j.** { *; }

# Gson/OkHttp/other transitive deps pulled by plugins — silence R8 warnings.
-dontwarn okhttp3.**
-dontwarn okio.**
-dontwarn javax.annotation.**
-dontwarn org.conscrypt.**
-dontwarn org.bouncycastle.**
-dontwarn org.openjsse.**
