# DEPLOYMENT.md — نشر أناقتك

## ١) الباك إند (Laravel 11)

### المتطلبات
PHP 8.3+ · Composer · MySQL 8 · Redis · خادم ويب (Nginx/Apache) · Node (اختياري للأصول).

### الخطوات
```bash
cd backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# اضبط .env للإنتاج:
#   APP_ENV=production  APP_DEBUG=false
#   DB_CONNECTION=mysql  DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD
#   QUEUE_CONNECTION=redis  CACHE_STORE=redis
#   FILESYSTEM_DISK=s3  AWS_* (تخزين S3-متوافق)
#   BROADCAST_CONNECTION=pusher  PUSHER_* (Soketi)
#   ANAQATUK_PAYMENT_DRIVER / ANAQATUK_SMS_DRIVER / ANAQATUK_AI_DRIVER
php artisan migrate --force
php artisan db:seed --force            # اختياري: بيانات أولية
php artisan config:cache route:cache view:cache
php artisan filament:optimize
```
> **مهم:** كود `1111` للـOTP/التسليم يُرفض تلقائيًا حين `APP_ENV=production`.

### الطوابير (Queues)
```bash
php artisan queue:work redis --tries=3 --timeout=90
# أو عبر Supervisor للإبقاء عليه حيًّا.
```

### المجدول (Scheduler) — SPEC §6
أضِف إلى crontab:
```
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```
المهام: `featured:expire` (يومي) · `featured:notify-expiring` (٩ص، إشعار «ينتهي غدًا») · `smart-requests:expire` (ساعي).

### البث الحي (Soketi)
```bash
npm install -g @soketi/soketi
# soketi.json: يستخدم PUSHER_APP_ID/KEY/SECRET من .env
soketi start --config=soketi.json
```
يستمع التطبيق على القنوات الخاصة: `private-conversation.{id}`، `private-smart-request.{id}`، `private-user.{id}`.

### الأمن
- Rate limiting مفعّل على OTP (٦/دقيقة) والطلب الذكي (١٠/دقيقة).
- كل الأسعار تُحسب خادميًا (لا ثقة بمبالغ العميل).
- Policies لكل Endpoint (ملكية الطلب/المتجر).
- `php artisan storage:link` لروابط الملفات العامة.

### الدفع الفعلي (لاحقًا)
اربط مزودًا (Moyasar/NeoLeap) بتنفيذ `App\Services\Payment\PaymentGateway` وتبديل الـbinding في `AppServiceProvider` — دون لمس منطق العمل.

## ٢) التطبيق (Flutter)

### أندرويد
```bash
cd app
flutter build apk --release \
  --dart-define=API_BASE_URL=https://api.anaqatuk.sa/api
# أو App Bundle للنشر على Google Play:
flutter build appbundle --release --dart-define=API_BASE_URL=https://api.anaqatuk.sa/api
```
- ProGuard/R8 مفعّل افتراضيًا في وضع release.
- الصلاحيات بالحد الأدنى (الإنترنت فقط؛ الكاميرا/الصور عند تفعيل الرفع).

### iOS
```bash
flutter build ios --release --dart-define=API_BASE_URL=https://api.anaqatuk.sa/api
# ثم الأرشفة والرفع عبر Xcode / Transporter.
```

### الأيقونة و Splash
تُولّد من هوية العلامة (بنفسجي `#6D28A9` + الحرف «أ» الذهبي) عبر `flutter_launcher_icons` و`flutter_native_splash` (يُضافان في تجهيز النشر النهائي).

## ٣) قائمة تحقق ما قبل الإطلاق
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, مفاتيح فعلية للدفع/SMS.
- [ ] `migrate --force` على قاعدة الإنتاج، نسخ احتياطي مجدول.
- [ ] Queue worker + Scheduler + Soketi قيد التشغيل (Supervisor/systemd).
- [ ] HTTPS + CORS لنطاق التطبيق.
- [ ] `flutter analyze` صفر تحذيرات · `pest` و`pint` خضراء في CI.
- [ ] `API_BASE_URL` يشير إلى نطاق الإنتاج في بناء التطبيق.
