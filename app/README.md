# أناقتك — تطبيق Flutter

الواجهة الأمامية (Android + iOS) لمنصة أناقتك. انظر [`../README.md`](../README.md) للنظرة الكاملة و[`../DEPLOYMENT.md`](../DEPLOYMENT.md) للنشر.

## التشغيل

```bash
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api   # محاكي أندرويد → localhost
flutter analyze        # صفر تحذيرات
flutter test           # وحدة + ويجت + Golden RTL
```

## البنية

```
lib/
  core/       الثيم (ألوان الهوية + خطوط عربية RTL)، شبكة Dio + Sanctum، realtime، أدوات الأرقام العربية
  data/       ApiRepository (دالة لكل Endpoint)
  models/     DTOs (لا تحمل كمية المخزون — تُخفى عن المشتريات)
  providers/  حالة المصادقة (Riverpod)
  router/     go_router ببوابة OTP للشاشات المحمية
  features/   الشاشات الـ١٦ (auth, home, product, designer, listing, cart,
              orders, account, seller, smart_request, chat, favorites,
              notifications, policies)
tool/gen_icon.dart   مولّد أيقونة/Splash بالهوية
```

## بناء النشر
```bash
flutter build appbundle --release --dart-define=API_BASE_URL=https://api.anaqatuk.sa/api
flutter build apk       --release --dart-define=API_BASE_URL=https://api.anaqatuk.sa/api
flutter build ios       --release --dart-define=API_BASE_URL=https://api.anaqatuk.sa/api
```
التوقيع عبر `android/key.properties` (غير مُتتبَّع). الأيقونة/Splash: `dart run flutter_launcher_icons && dart run flutter_native_splash:create`.
