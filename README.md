# أناقتك — Anaqatuk

سوق سعودي متعدد البائعات لأزياء المصممات: **بيع**، **بيع بتعديل مقاسات**، و**تأجير**، مع منتجات العناية والإكسسوارات والمستعمل الموثّق. عربي أولًا، RTL بالكامل.

> **مصدر الحقيقة:** `anaqatuk.html` (النموذج التفاعلي). المواصفة الكاملة في [`SPEC.md`](SPEC.md)، وقرارات الغموض في [`DECISIONS.md`](DECISIONS.md).

## بنية المستودع

```
anaqatuk.html      # النموذج المرجعي (source of truth)
SPEC.md            # المواصفة الكاملة: ١٦ شاشة، قواعد العمل، البيانات، آلات الحالة
DECISIONS.md       # قرارات الغموض (محلولة نحو سلوك HTML)
backend/           # Laravel 11 API + Filament 3 admin
app/               # Flutter (Android + iOS)
```

## نظرة تقنية

| الطبقة | التقنية |
|---|---|
| الباك إند | Laravel 11 · PHP 8.3+ · MySQL 8 (SQLite للتطوير) · Sanctum · Redis |
| الأدمن | Filament 3 (نفس مشروع Laravel) |
| الحيّ | Broadcasting بروتوكول Pusher (Soketi) للشات والعروض والإشعارات |
| التطبيق | Flutter · Riverpod · go_router · dio · flutter_secure_storage · google_fonts |
| الدفع/الرسائل/الذكاء | تجريدات قابلة للاستبدال: `PaymentGateway`/`FakeGateway` · `SmsChannel`/`FakeSmsChannel` · `AiListingService`/`Mock` |

## التشغيل السريع

### الباك إند
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# التطوير على SQLite (الافتراضي في .env):
touch database/database.sqlite
php artisan migrate:fresh --seed        # يبذر كل بيانات النموذج حرفيًا
php artisan serve                        # http://localhost:8000
```

- **لوحة الأدمن:** `http://localhost:8000/admin` — الدخول `admin@anaqatuk.sa` / `password`.
- **توثيق الـ API:** `http://localhost:8000/docs` (Scribe) · OpenAPI في `storage/app/private/scribe/openapi.yaml`.
- **الاختبارات:** `php vendor/bin/pest` (كل قواعد §٤) · التنسيق `php vendor/bin/pint`.
- **كود التطوير:** OTP وكود التسليم = `1111` (خارج الإنتاج فقط).

### التطبيق
```bash
cd app
flutter pub get
# وجّه التطبيق إلى الـ API (محاكي أندرويد → 10.0.2.2):
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api
flutter analyze        # صفر تحذيرات
flutter test           # اختبارات وحدة/ويجت + Golden RTL
```

## قواعد العمل الأساسية (SPEC §4)

- **Escrow:** المبلغ محجوز حتى تُدخل البائعة **كود التسليم**؛ العمولة **١٢٪** تُخصم عند التحرير وتُحسب بعد كوبون البائعة.
- **الاسترجاع:** ٧ أيام مجانية على المشترية (شحن الإرجاع على البائعة)؛ التفصيل الخاص لعيب مصنعي فقط.
- **التأجير:** سعر/٣ أيام + تأمين محجوز؛ القطعة تُحجب (إيجار+إرجاع+فحص)؛ تأخير ١٠٪/يوم.
- **المميز:** باقات مسبقة الدفع (منطقتي ٢٩/٤٩/٧٩ · كل المناطق ٥٩/٩٩/١٤٩) بانتهاء تلقائي.
- **الكميات لا تظهر للمشتريات إطلاقًا** — نفادها يعطّل الخيار فقط.
- **الطلب الذكي:** عرض > الميزانية مرفوض؛ حد ٣ طلبات نشطة.

## حالة التنفيذ

| المرحلة | الحالة |
|---|---|
| ٠ — المواصفة (SPEC/DECISIONS) | ✅ |
| ١ — الباك إند (قاعدة، خدمات، REST API موثّق، Pest) | ✅ ٢١ اختبارًا |
| ٢ — لوحة الأدمن (Filament) | ✅ ٨ موارد + إحصائيات |
| ٣ — Flutter (Design System + شاشات + realtime + اختبارات) | ✅ الأساس + الرحلة الأساسية |
| ٤ — تكامل E2E | 🚧 |
| ٥ — تجهيز النشر | 🚧 (`DEPLOYMENT.md` + `.env.example` جاهزان) |

راجع مراجعات المراحل: [`backend/PHASE-1-REVIEW.md`](backend/PHASE-1-REVIEW.md).
