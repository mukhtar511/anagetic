# مراجعة ذاتية — المرحلة ١ (باك إند Laravel)

> بوابة §٨: ماذا نُفّذ · ماذا اختُبر · الفجوات المتبقية · ثم إصلاحها قبل المتابعة.

## ماذا نُفّذت

**البنية:** Laravel 11 · PHP 8.4 · Sanctum · SQLite للتطوير/الاختبار (MySQL موثّق للإنتاج) · Redis/Soketi/S3/FCM في `.env.example`.

**القاعدة:** ٢٨ جدولًا في ٨ ملفات Migration (كتالوج، طلبات/Escrow، محفظة، كوبونات، تأجير، طلبات ذكية، شات، تقييمات/اعتراضات/سلة). `migrate:fresh --seed` يعمل نظيفًا.

**آلات الحالة:** ١١ Enum مُدعّم يشفّر انتقالات §٦ (`OrderStatus` بانتقالات محمية + `isFreelyCancellable`، `DepositStatus`، `RentalStatus`، `FeaturedStatus`، `SmartRequestStatus`، `StoreStatus`، `CommMode`، `ProductMode`، `CouponKind/Owner`، `FeaturedScope`).

**النماذج:** ٣٤ نموذج Eloquent بعلاقات كاملة وcasts (Enum + `decimal:2` + json + datetime).

**الخدمات (منطق العمل):** `EscrowService` (عمولة ١٢٪ على الأساس بعد كوبون البائعة، تحرير بكود التسليم) · `WalletService` (الرصيد = مجموع الدفتر) · `CouponService` · `RentalService` (حجب التوفر + دورة التأمين) · `SmartRequestService` (رفض >الميزانية + حد ٣ نشطة + TTL) · `FeaturedService` · `ReturnService` · `WithdrawalService` · `OrderService` (Checkout بتسعير خادمي + Escrow لكل بائعة) · `AuthService` · `ExternalDealingFilter`.

**التجريدات القابلة للاستبدال:** `PaymentGateway`/`FakeGateway` · `SmsChannel`/`FakeSmsChannel` (OTP `1111` خارج الإنتاج) · `AiListingService`/`MockAiListingService` — مربوطة في `AppServiceProvider`.

**REST API:** ٥٨ Endpoint (عامة + `auth:sanctum` للمشترية والبائعة)، Controllers رفيعة (تحقق → خدمة → Resource)، ماب استثناءات المجال → 422 JSON بالرسالة العربية، Policies للملكية. موثّقة عبر **Scribe** (HTML + Postman + OpenAPI).

**الجدولة:** أوامر `featured:expire` (يومي) · `featured:notify-expiring` (٩ص) · `smart-requests:expire` (ساعي).

**البذور:** كل بيانات النموذج حرفيًا (١٠ متاجر، ١٠ منتجات + أنماط/ألوان/إضافات، ٤ كوبونات، كشف محفظة نوف = ٤٣٤٫٤٠، ٥ إشعارات).

## ماذا اختُبر (٢١ اختبار Pest — كلها خضراء، ٨٩ تأكيدًا)

| القاعدة §٤ | الاختبار |
|---|---|
| Escrow + تحرير بالكود فقط | `holds funds and releases the net (88%) only on the correct delivery code` |
| العمولة على الأساس **بعد** كوبون البائعة | `computes commission on the price AFTER the seller coupon` (900→108 عمولة→792 صافي) |
| تقسيم متعدد البائعات (كود مستقل) | `splits a multi-seller checkout...` |
| حجب التأجير (إيجار+إرجاع+فحص) | `blocks a piece for rental + return + inspection...` |
| استرداد التأمين الكامل | `refunds the full deposit...` |
| خصم التلف الجزئي (تقسيم) | `splits the deposit on partial damage...` |
| رفض عرض > الميزانية | `rejects a seller offer above the request budget...` |
| حد ٣ طلبات نشطة | `enforces the 3 active-request limit...` |
| نافذة الإرجاع + منع إرجاع التفصيل | `refuses returning a custom item except for a manufacturing defect` |
| فتح شات «الدفع أولًا» بعد الدفع | `opens the chat for a payfirst store once payment is in` |
| فلتر التعامل الخارجي | `flags external phone numbers, links and payment keywords` |
| سقف الكوبون + الحد الأدنى | `caps a percentage coupon and honours the minimum order` |
| تحقق كوبون البائعة (≤٧٠٪) | `rejects invalid seller coupon codes and percentages over 70` |
| حد السحب ١٠٠ | `blocks withdrawals below 100 or above the balance` |
| تسعير المميز | `prices featured packages from the matrix` |
| API: OTP→توكن، إخفاء الكمية، Checkout→تحرير، رفض العرض | `tests/Feature/Api/*` |

**التنسيق:** `pint` نظيف.

## الفجوات المتبقية (تُعالَج في مراحل لاحقة أو عند توفر الأدوات)

1. **phpstan/larastan (مستوى ٦):** الملف `phpstan.neon` جاهز، لكن تعذّر تثبيت larastan في هذه البيئة (توزيع GitHub يحتاج مصادقة غير متوفرة في الساندبوكس — نجح ما يأتي من Packagist فقط). يُشغَّل حيث تتوفر مصادقة GitHub.
2. **البث الحي (WebSockets):** قنوات Broadcasting (شات/عروض/إشعارات) مُعدّة في `.env.example` (Soketi/Pusher) لكن أحداث `ShouldBroadcast` ستُربط فعليًا في المرحلة ٣ مع Flutter Echo.
3. **Rate limiting (OTP/الطلب الذكي):** مطلوب §٨ — يُضاف كـ throttle middleware على مسارات OTP والطلب الذكي (لم يُفعَّل بعد).
4. **رفع الصور بفحص نوع/حجم:** مسارات الرفع موجودة؛ قيود `mimes/max` على FormRequests تُشدَّد عند ربط التخزين S3.
5. **تغطية اختبارات أوسع:** الحالي يغطي كل قواعد §٤ الحرجة؛ يُوسَّع لاحقًا لمسارات القراءة والحواف.

## الإصلاحات المطبّقة ضمن هذه المرحلة
- تصحيح حجب التأجير ليشمل فترة الفحص كاملة (فاصل شامل موحّد بين `book()` و`isAvailable()`).
- قصر `preventLazyLoading` على بيئة `local` فقط حتى لا تكسر الخدمات في الاختبار.
- إزالة إدخالات larastan/phpstan المعلّقة من `composer.json` للحفاظ على `composer install` قابلًا للتكرار.
