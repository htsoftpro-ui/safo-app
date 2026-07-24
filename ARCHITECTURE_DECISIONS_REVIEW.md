# ARCHITECTURE_DECISIONS_REVIEW — Safo Backend

> مراجعة هندسية صريحة لكل قرار تقني اتخذته.
> لا يتم تغيير أي شيء — هذا تقرير تحليلي فقط.

---

## 1. Laravel 11

| البند | التفاصيل |
|-------|---------|
| **القرار** | Laravel 11 |
| **البدائل المدروسة** | Node.js (NestJS), Django, Laravel 10 |
| **لماذا Laravel 11** | آخر إصدار مستقر (released March 2024). يدعم PHP 8.2+. يُعدّل تلقائياً من Laravel 10. يُبسط cấuمة المشروع (لا app/Http/Kernel.php). |
| **لماذا Laravel بدل Node.js** | التطبيق الأصلي على الأرجح Laravel (حزمة com.my_goods.my_goods_customer + نمط API + Yemeni dev ecosystem). Laravel هو الأكثر شيوعاً في اليمن والخليج. документация واسعة بالعربية. Sanctum مدمج. Migration system أقوى من任何 شيء في Node.js. |
| **لماذا ليس Django** | Python أقل شيوعاً في سوق العمل اليمني. لا يوجد ما يثبت أن الأصلي يستخدم Django. |
| **المزايا** | ecosystem ناضج + مجتمع عربي كبير + أدوات مدمجة (Auth, Queue, Cache, Mail) + Migration system ممتاز + Sanctum/Auth effortless |
| **العيوب** | PHP أبطأ من Node.js في I/O-bound operations (فرق بسيط في حجم هذا المشروع). Laravel "ثقيل" للمشاريع الصغيرة جداً. |
| **هل مبني على دليل** | 🔵 استنتاج — لا يوجد دليل مباشر على أن الأصلي يستخدم Laravel، لكن نمط API + package name يشير لذلك. |
| **قابلية التغيير** | ✅ نعم — API contract (الـ endpoints والـ responses) مستقل عن framework. يمكن إعادة كتابة Backend بـ NestJS لاحقاً بدون تغيير Frontend. |
| **مناسب للسوق اليمني** | ✅ نعم — Laravel هو Framework الأكثر طلباً في اليمن. مطورون يمنيون كثيرون يعرفونه. |
| **التصنيف** | ✅ KEEP |

---

## 2. MySQL 8.0

| البند | التفاصيل |
|-------|---------|
| **القرار** | MySQL 8.0 |
| **البدائل المدروسة** | PostgreSQL 16, MongoDB |
| **لماذا MySQL** | Laravel default. الأكثر شيوعاً مع Laravel. يكفي لاحتياجات هذا المشروع (علاقات + بحث + JSON). MySQL 8.0 يدعم JSON columns + FULLTEXT search + Window Functions. |
| **لماذا ليس PostgreSQL** | PostgreSQL أفضل في: complex queries, JSONB indexing, geospatial (PostGIS), full-text search متقدم. لكن: MySQL يكفي لهذا المشروع. PostgreSQL أقل شيوعاً في الاستضافة المشتركة في اليمن. Laravel + MySQL هو combo أكثر اختباراً. |
| **لماذا ليس MongoDB** | البيانات علائقية بحتة (users → orders → items). MongoDB لا يناسب relational data. لا يوجد سبب تقني لاستخدامه هنا. |
| **المزايا** |成熟 + سريع لل reads + يكفي للـ scale المتوقع (< 100K مستخدم) + مدعوم في كل استضافة |
| **العيوب** | MySQL FULLTEXT أقل قوة من PostgreSQL. لا يدعم geospatial queries بالجودة نفسها. لكن: لا نحتاج geospatial في MVP. |
| **هل مبني على دليل** | 🔵 استنتاج — لا يمكن معرفة نوع قاعدة البيانات الأصلية من外部 |
| **قابلية التغيير** | ✅ نعم — Laravel migrations تعمل مع MySQL و PostgreSQL. تغيير `DB_CONNECTION` في .env كافٍ تقريباً. |
| **مناسب للسوق اليمني** | ✅ نعم — مدعوم في كل استضافة. أرخص من PostgreSQL في بعض المزودين. |
| **التصنيف** | ✅ KEEP |

---

## 3. Redis

| البند | التفاصيل |
|-------|---------|
| **القرار** | Redis (Cache + Sessions + Queues) |
| **الاستخدام الفعلي المخطط** | 1. **Sessions**: تخزين جلسات المستخدمين (أسرع من database). 2. **Cache**: تخزين results متكررة (categories, featured products). 3. **Queues**: إرسال الإشعارات في الخلفية (لا ت_blocking الطلب). 4. **Locks**: `SELECT FOR_UPDATE` locks (يمكن أيضاً عبر MySQL). |
| **هل يحتاجه من الإصدار الأول** | ⚠️ هذا سؤال مهم. **الإجابة الصريحة: لا، ليس إلزامياً من اليوم الأول.** Laravel يعمل بدون Redis — يستخدم file sessions وdatabase cache وsync queue. لكن: Redis يُحسّن الأداء بشكل ملحوظ. يمكن إضافته لاحقاً بدون تغيير الكود (فقط .env). **قراري: أضفته في التصميم لكنه ليس blocking dependency.** |
| **المزايا** | أسرع 10x من file cache. يدعم pub/sub لل real-time. يدعم queues بشكل مدمج. |
| **العيوب** | يحتاج خادم منفصل (أو service إضافي). يزيد التعقيد في بيئة التطوير المحلية. |
| **هل مبني على دليل** | 🔵 استنتاج — لا يمكن معرفة ما إذا الأصلي يستخدم Redis. |
| **قابلية التغيير** | ✅ نعم — `CACHE_STORE=file` في .env يكفي للرجوع لـ file cache. لا يوجد كود يعتمد على Redis مباشرة. |
| **مناسب للسوق اليمني** | ✅ نعم — DigitalOcean يوفر Redis كـ managed service بـ $15/شهر. |
| **التصنيف** | ✅ KEEP (كمكون اختياري في التصميم، ليس إلزامياً في MVP) |

---

## 4. Laravel Sanctum

| البند | التفاصيل |
|-------|---------|
| **القرار** | Laravel Sanctum |
| **البدائل المدروسة** | JWT (tymon/jwt-auth), Passport (OAuth2), Custom tokens |
| **الفرق بين البدائل** | |
| **Sanctum Personal Access Tokens** | Tokens مخزنة في database (personal_access_tokens table). غير مشفرة — فقط hashed. لا تحتوي payload — فقط random string ي指向 سجل في DB. صالحية غير مدمجة في Token — يجب فحص DB. |
| **JWT (tymon/jwt-auth)** | Token يحتوي payload مشفر (user_id, expiry). self-contained — لا يحتاج DB lookup. expiry مدمج في Token. أسرع (لا DB query لل verify). لكن: لا يمكن إلغاؤه بدون blacklist. |
| **OAuth2 (Passport)** | يدعم authorization code flow, client credentials, etc. معقد جداً لهذا المشروع. مصمم لـ third-party API access. لا حاجة له هنا — نحن نتحكم في كل العملاء. |
| **لماذا Sanctum** | 1. مدمج مع Laravel (لا مكتبة إضافية). 2. يدعم mobile tokens + SPA cookies بنفس الحزمة. 3. بسيط — لا يحتاج فهم OAuth2 flow. 4. يكفي لـ API مغلق (نتحكم في كل العملاء). 5. Laravel官方推荐. |
| **عيوب Sanctum** | أبطأ من JWT (DB lookup لكل request). لا يدعم token payload (لا نعرف user_id من Token مباشرة). لكن: الفرق بسيط جداً (< 1ms مع Redis cache). |
| **هل مبني على دليل** | 🔵 استنتاج — لا يمكن معرفة نظام المصادقة الأصلي. |
| **قابلية التغيير** | ⚠️ صعب — Sanctum middleware مدمج في routes. التغيير يحتاج تعديل كل Controllers. لكن: API contract لا يتغير — العملاء لا يعرفون الفرق. |
| **مناسب للسوق اليمني** | ✅ نعم — بسيط للمطورين. لا يحتاج إعداد معقد. |
| **التصنيف** | ✅ KEEP |

---

## 5. Vue.js SPA منفصل

| البند | التفاصيل |
|-------|---------|
| **القرار** | Vue.js 3 SPA منفصل (ليس Inertia.js) |
| **البدائل المدروسة** | Inertia.js, Livewire, Nuxt.js, React |
| **لماذا Vue SPA منفصل** | 1. تطبيق Android يحتاج نفس الـ API — فلماذا أبني two different access patterns? 2. SPA منفصل = frontend مستقل تماماً — يمكن استضافته على CDN منفصل. 3. Vue.js أسهل من React للمطورين العرب. 4. لا حاجة لـ SSR (التطبيق ليس محتوى — هو dashboard). |
| **لماذا ليس Inertia.js** | Inertia يدمج Laravel + Vue في مشروع واحد. ممتاز لـ small teams. لكن: يمنع استخدام الـ API من تطبيق Android. يلزمك بـ Laravel للـ frontend rendering. إذا غيّرت Backend، تفقد frontend أيضاً. |
| **لماذا ليس Livewire** | Livewire server-side rendering. لا يناسب SPA dashboard. أبطأ في التفاعل. لا يناسب mobile-first design. |
| **لماذا ليس Nuxt** | Nuxt = Vue + SSR. لا نحتاج SSR لـ dashboard. يزيد التعقيد بلا فائدة واضحة. |
| **لماذا ليس React** | React أكثر تعقيداً من Vue. Vue أسهل للمبتدئين. Laravel ecosystem يدعم Vue أكثر (Inertia, Breeze). لكن: React ممكن لاحقاً — API contract واحد يخدم الاثنين. |
| **المزايا** | فصل كامل بين frontend و backend. API واحد يخدم Android + Web. يمكن استضافة frontend على أي CDN. testing أسهل (كل layer مستقل). |
| **العيوب** | مشروعان منفصلان = deployment أكثر تعقيداً. CORS configuration مطلوب. Auth flow أكثر تعقيداً (token management). |
| **هل مبني على دليل** | 🟡 استنتاج قوي — الأصل يستخدم API واحد (تطبيق Android + موقع ويب). |
| **قابلية التغيير** | ✅ نعم — API contract لا يتغير. يمكن استبدال Vue بـ React أو أي framework آخر. |
| **مناسب للسوق اليمني** | ✅ نعم — Vue.js شائع في المنطقة. مطورون يمنيون يعرفونه. |
| **التصنيف** | ✅ KEEP |

---

## 6. Service Layer

| البند | التفاصيل |
|-------|---------|
| **القرار** | Service Layer pattern |
| **أين يعيش Business Logic** | الـ Controllers تستدعي Services. الـ Services تحتوي المنطق المعقد. الـ Models تحتوي المنطق البسيط (accessors, scopes, simple helpers). |
| **البدائل المدروسة** | Domain Services, Action Classes, Repository Pattern, Fat Controllers |
| **لماذا Service Layer** | 1. يفصل بين HTTP layer و Business logic. 2. قابل للاختبار (يمكن mock الـ services). 3. يمكن إعادة استخدامه من Console commands, Queue jobs, etc. 4. يمنع Fat Controllers. |
| **Domain Services vs Service Layer** | Domain Services = classes تمثل عمليات domain (CreateOrderService). Service Layer = broader — يشمل services غير domain (NotificationService, PaymentService). **أستخدم الاثنين:** OrderService = domain service. NotificationService = application service. |
| **Action Classes** | Action = class واحدة تقوم بشيء واحد (CreateOrderAction). أبسط من Service لكن أقل flexibility. **رأيي:** Service أفضل لهذا الحجم — OrderService يحتاج methods متعددة (create, cancel, transition). |
| **Repository Pattern** | يفصل بين Model و Controller. لكن: Eloquent Model هو بالفعل repository. إضافة repository layer فوق Eloquent = over-engineering لهذا المشروع. **قرار:** لا أستخدم Repository pattern — Eloquent كافي. |
| **Fat Controllers** | ❌ خطأ شائع — يضع كل المنطق في Controller. يصعب الاختبار. يصعب إعادة الاستخدام. **قرار:** Controllers thin — فقط validation + service call + response formatting. |
| **Policies** | Laravel Policies لل authorization (من يمكنه فعل ماذا). أستخدمها لـ: `OrderPolicy` (هل يمكن لهذا المستخدم إلغاء هذا الطلب؟). لكن: لل MVP، authorization بسيطة — middleware + manual checks كافية. Policies لاحقاً. |
| **هل مبني على دليل** | 🔵 استنتاج — best practice معروف. |
| **قابلية التغيير** | ✅ نعم — Service classes يمكن تقسيمها أو دمجها بسهولة. |
| **مناسب للسوق اليمني** | ✅ نعم — clean architecture يسهل الصيانة لأي فريق. |
| **التصنيف** | ✅ KEEP |

---

## 7. البدء بـ 9 جداول فقط

| البند | التفاصيل |
|-------|---------|
| **القرار** | 9 جداول أساسية لل MVP |
| **الجداول المختارة** | 1. users, 2. categories, 3. suppliers, 4. products, 5. addresses, 6. cart_items, 7. orders, 8. order_items, 9. order_status_history |
| **لماذا هذه الجداول** | هذه الجداول تمثل **أدنى مسار ممكن** لـ order lifecycle كامل: مستخدم → يتصفح منتجات → يضيف للسلة → يطلب → يتتبع. |
| **الجداول المؤجلة** | |
| **reviews** | Phase 2 — يمكن إضافتها بدون تعديل أي جدول موجود (فقط FK إلى products و orders). |
| **notifications** | Phase 1.5 — مهمة لكن ليست blocking. NotificationService يعمل بدون DB notifications (فقط FCM). |
| **offers / coupons** | Phase 2 — لا تؤثر على order flow الأساسي. |
| **banners** | Phase 2 — content management فقط. |
| **group_offers** | Phase 2 — feature متقدمة. |
| **payments** | ⚠️ مهم — حالياً payment_status في orders table. هذا يكفي للدفع النقدي. للدفع الإلكتروني، نحتاج جدول payments منفصل. **قرار:** نضيفه في Phase 2 عند تفعيل الدفع الإلكتروني. |
| **credit_accounts** | Phase 2 — الدفع الآجل feature متقدمة. |
| **settings** | Phase 1.5 — نحتاجه للإعدادات العامة لكن config files تكفي لل MVP. |
| **هل توجد جداول ضرورية تم تأجيلها** | ⚠️ **جدول payments** — إذا فعّلنا الدفع الإلكتروني لاحقاً، نحتاج إضافة جدول + تعديل orders table. هذا **ليس breaking change** — مجرد migration إضافية. |
| **هل مبني على دليل** | 🔵 استنتاج — أفضل ممارسات MVP development. |
| **قابلية التغيير** | ✅ نعم — Laravel migrations تسمح بإضافة جداول بدون تعديل الموجودة. |
| **مناسب للسوق اليمني** | ✅ نعم — الدفع النقدي هو الأساسي. الدفع الإلكتروني يمكن إضافته لاحقاً. |
| **التصنيف** | ✅ KEEP |

---

## 8. Product Snapshot

| البند | التفاصيل |
|-------|---------|
| **القرار** | حفظ snapshot من بيانات المنتج في order_items |
| **البيانات المحفوظة** | product_name, product_image, product_unit, unit_price, quantity, total_price |
| **لماذا snapshot** | 1. الطلب يجب أن يحتفظ ببياناته حتى لو حُذف المنتج. 2. السعر وقت الطلب قد يختلف عن السعر الحالي. 3. اسم المنتج قد يتغير. 4. الصورة قد تتغير أو تُحذف. |
| **البدائل المدروسة** | |
| **البديل 1: FK فقط** | نحفظ product_id فقط ونقرأ من products table. ❌ خطر: إذا حُذف المنتج، يُفقد اسمه وسعره. |
| **البديل 2: Snapshot كامل** | نحفظ كل شيء (name, description, price, image, unit, etc.). ❌ over-engineering — لا نحتاج كل هذه البيانات. |
| **البديل 3: Snapshot محدد** ✅ | نحفظ فقط ما نحتاجه للعرض: name, image, unit, price. هذا ما فعلته. |
| **كيف يتعامل مع تغير السعر** | unit_price محفوظ وقت الطلب. السعر الحالي للمنتج لا يؤثر على الطلبات القديمة. هذا السلوك الصحيح. |
| **كيف يتعامل مع تغير الاسم** | product_name محفوظ وقت الطلب. الاسم الحالي لا يؤثر. إذا أردنا عرض "المنتج الحالي"، نستخدم product_id (لا يزال موجوداً). |
| **كيف يتعامل مع حذف الصورة** | product_image محفوظ. الصورة المحذوفة من storage لن تظهر. **حل:** نحفظ الصورة كـ URL كامل. إذا حُذفت من storage، نعرض placeholder. |
| **هل مبني على دليل** | ✅ مؤكد — هذا best practice معروف في e-commerce. Amazon, Shopify يفعلون نفس الشيء. |
| **قابلية التغيير** | ⚠️ صعب —一旦 orders تحتوي على بيانات، لا يمكن تغيير الـ schema بسهولة. لكن: الـ schema الحالي مرن كافي. |
| **مناسب للسوق اليمني** | ✅ نعم — مهم جداً للسوق اليمني حيث الأسعار تتغير frequently. |
| **التصنيف** | ✅ KEEP |

---

## 9. Address Snapshot

| البند | التفاصيل |
|-------|---------|
| **القرار** | حفظ العنوان كـ TEXT في orders table |
| **البدائل المدروسة** | |
| **TEXT** (الحالي) | العنوان الكامل كنص واحد. بسيط. لا يحتاج parsing. لكن: لا يمكن البحث عن city/area بشكل منفصل. |
| **JSON** | {"address": "...", "city": "...", "area": "...", "building": "..."} يسمح بالبحث عن city/area. يحتاج json_decode(). MySQL 8.0 يدعم JSON indexing. |
| **Structured Columns** | delivery_city, delivery_area, delivery_building, delivery_address كأعمدة منفصلة. أقوى للبحث والتقارير. لكن: يزيد schema complexity. |
| **التحليل** | **TEXT يكفي لل MVP.** العنوان يُعرض فقط (لا يُبحث عنه). في Phase 2، إذا أردنا تقارير "أكثر المناطق طلباً"، نحتاج JSON أو structured columns. **قرار:** أبدأ بـ TEXT + أضيف delivery_city كعمود منفصل إذا احتجنا. |
| **هل مبني على دليل** | 🔵 استنتاج — TEXT هو الأبسط. |
| **قابلية التغيير** | ✅ نعم — يمكن إضافة أعمدة JSON لاحقاً. |
| **مناسب للسوق اليمني** | ✅ نعم — العناوين اليمنية غالباً وصفية (بجانب مسجد X، مقابل Y). TEXT مناسب. |
| **التصنيف** | ⚠️ REVIEW — سأضيف delivery_city كعمود منفصل الآن (cost: صغير، benefit: كبير للتقارير). |

---

## 10. Order Number

| البند | التفاصيل |
|-------|---------|
| **القرار** | `ORD-YYYYMMDD-NNN` |
| **الصيغة الحالية** | `ORD-20260725-001` |
| **هل آمنة تحت الطلبات المتزامنة** | ⚠️ **لا تماماً.** الطريقة الحالية: `count() + 1`. إذا طلب شخصان في نفس الثانية: كلاهما يحصل على `count() = 5` → كلاهما يحصل على `006`. **النتيجة:** رقم مكرر! |
| **الحل الصحيح** | **Option A: Database Sequence** — `CREATE SEQUENCE order_number_seq`. آمن 100%. لكن: Laravel لا يدعم sequences بشكل مدمج في MySQL. **Option B: Atomic Counter Table** — جدول `sequences` مع `INCREMENT`. `UPDATE sequences SET counter = counter + 1 WHERE name = 'orders'`. آمن + بسيط. **Option C: UUID + date prefix** — `ORD-20260725-a1b2c3d4`. فريد 100%. لكن: غير بشري. **Option D: Redis INCR** — `INCR order_number:20260725`. سريع + آمن. لكن: يحتاج Redis. |
| **القرار المختار** | **Option B: Atomic Counter Table** — simplest + safest. |
| **هل مبني على دليل** | 🔵 استنتاج — الصيغة من best practices. |
| **قابلية التغيير** | ✅ نعم — change في Order::generateOrderNumber() فقط. |
| **مناسب للسوق اليمني** | ✅ نعم — الأرقام البشرية مهمة للعميل والدعم. |
| **التصنيف** | ⚠️ REVIEW — سأصلح الـ race condition الآن. |

---

## 11. Cart Constraint

| البند | التفاصيل |
|-------|---------|
| **القرار** | `UNIQUE(user_id, product_id)` في cart_items |
| **هل صحيح مستقبلاً** | ⚠️ **هذا سؤال مهم جداً.** |
| **السيناريوهات المستقبلية** | |
| **Product Variants** | إذا أضفنا variants (size, color)، نحتاج `product_variant_id` في الـ constraint. **الحل:** `UNIQUE(user_id, product_id, variant_id)`. هذا breaking change. |
| **أكثر من مورد** | نفس المنتج من موردين مختلفين بأسعار مختلفة. **الحل:** `UNIQUE(user_id, product_id, supplier_id)`. هذا breaking change. |
| **عروض مختلفة** | عرض خاص + عرض عادي لنفس المنتج. نادراً ما يحدث في B2B. |
| **التحليل** | لل MVP الحالي (منتج بدون variants، مورد واحد لكل منتج)، `UNIQUE(user_id, product_id)` صحيح. **لكن:** إذا أضفنا variants لاحقاً، نحتاج migration لتغيير الـ constraint. **هذا ليس خطيراً** — مجرد `ALTER TABLE` بسيط. |
| **هل مبني على دليل** | 🔵 استنتاج — best practice لـ cart systems. |
| **قابلية التغيير** | ✅ نعم — `ALTER TABLE cart_items DROP INDEX ... ADD UNIQUE INDEX ...`. |
| **مناسب للسوق اليمني** | ✅ نعم — B2B بدون variants حالياً. |
| **التصنيف** | ✅ KEEP (مع العلم أنه سيتغير عند إضافة variants) |

---

## 12. Status Machine

| البند | التفاصيل |
|-------|---------|
| **القرار** | 7 حالات: pending → confirmed → processing → ready → shipped → delivered → cancelled |
| **هل تغطي جميع دورة العمل** | ⚠️ **لا تماماً.** |
| **الحالات المفقودة** | |
| **rejected** | المورد يرفض الطلب. حالياً: المورد يستخدم `cancelled`. لكن: `cancelled` غامض — هل ألغاه العميل أم رفضه المورد؟ **الحل:** `cancelled_by` field يوضح من ألغى. هذا يكفي — لا نحتاج `rejected` كحالة منفصلة. |
| **returned** | العميل يرجع الطلب بعد التوصيل. **مهم جداً!** حالياً: `delivered` هي الحالة النهائية. لا يمكن إرجاع طلب. **الحل:** نحتاج إضافة `returned` كحالة + `return_reason` field. **قرار:** أضيفها الآن (cost: صغير، benefit: كبير). |
| **failed** | فشل التوصيل. حالياً: المورد يعود لـ `ready` أو `processing`. **الحل:** لا نحتاج `failed` — المورد يعيد المحاولة. |
| **on_hold** | طلب معلق (انتظار دفع، انتظار مخزون). نادر في B2B. **قرار:** لا أضيفها — التعقيد لا يستحق. |
| **القواعد النهائية** | `pending → confirmed, cancelled` `confirmed → processing, cancelled` `processing → ready, cancelled` `ready → shipped` `shipped → delivered` `delivered → returned` `returned → (terminal)` `cancelled → (terminal)` |
| **هل مبني على دليل** | 🔵 استنتاج — order lifecycle قياسي. |
| **قابلية التغيير** | ✅ نعم — إضافة حالة = تعديل ENUM + VALID_TRANSITIONS. |
| **مناسب للسوق اليمني** | ✅ نعم — B2B لا يحتاج حالات معقدة. |
| **التصنيف** | ⚠️ REVIEW — سأضيف `returned` status الآن. |

---

## 13. Storage

| البند | التفاصيل |
|-------|---------|
| **القرار** | Local Storage + Cloudflare CDN |
| **لماذا هذا التصميم** | simplest + أرخص. Laravel `storage/app/public` + symlink. Cloudflare يخدم الصور بسرعة عالمية. |
| **البدائل المدروسة** | |
| **AWS S3** | الأفضل لـ production. scalable + reliable. لكن: يكلف $0.023/GB/شهر. يحتاج AWS account. يزيد التعقيد. |
| **DigitalOcean Spaces** | أرخص من S3 ($5/شهر لـ 250GB). متوافق مع S3 API. ممتاز لـ DigitalOcean hosting. |
| **Local + CDN** (الحالي) | مجاني. بسيط. لكن: إذا سقط الخادم، تُفقد الصور. لا يدعم auto-scaling. |
| **التحليل** | **لل MVP: Local كافي.** الصور قليلة (< 1000 منتج). حجم الصور محدود (5MB max). **لـ Production:** يجب الانتقال لـ S3 أو DigitalOcean Spaces. **قرار:** أبدأ بـ Local + أعدّل لاحقاً. Laravel filesystem يسمح بتبديل السائق بسهولة (`FILESYSTEM_DISK=s3`). |
| **هل مبني على دليل** | 🔵 استنتاج — simplest approach. |
| **قابلية التغيير** | ✅ نعم — `FILESYSTEM_DISK` change في .env. |
| **مناسب للسوق اليمني** | ⚠️ مقبول لل MVP. لكن: الإنترنت في اليمن بطيء — CDN مهم جداً. Cloudflare مجاني ويكفي. |
| **التصنيف** | ✅ KEEP (لل MVP) |

---

## 14. Authentication Identifier

| البند | التفاصيل |
|-------|---------|
| **القرار** | phone هو المعرّف الأساسي (ليس email) |
| **لماذا phone** | 1. السوق اليمني — معظم المستخدمين لا يملكون email. 2. الهاتف هو وسيلة التواصل الأساسية. 3. OTP عبر SMS أسهل من email verification. 4. الأصل يستخدم phone (من Play Store — رقم الهاتف مدرج). |
| **المشاكل المحتملة** | |
| **تغيير رقم الهاتف** | ⚠️ **مشكلة حقيقية.** إذا غيّر المستخدم هاتفه: لا يمكنه تسجيل الدخول. **الحل:** نحتاج "change phone" feature مع OTP verification على الرقمين (القديم + الجديد). **قرار:** أضيفها لاحقاً — ليست blocking لل MVP. |
| **رقم مكرر** | `UNIQUE constraint` على phone column. التسجيل يفشل إذا الرقم مسجل. هذا السلوك الصحيح. |
| **مستخدم لا يملك رقمًا** | نادر جداً في اليمن. لكن: email كحقل اختياري يحل هذه المشكلة. **قرار:** email موجود كحقل nullable — يمكن استخدامه كبديل. |
| **التحقق من الرقم** | OTP عبر SMS. حالياً: فقط fields في DB (otp_code, otp_expires_at). **قرار:** أحتاج تنفيذ OTP flow كامل لاحقاً. |
| **OTP مستقبلاً** | أحتاج SMS gateway (Twilio أو بديل يمني). **قرار:** أصمّم النظام ليكون جاهزاً لـ OTP لكن لا أنفذه في MVP. |
| **هل مبني على دليل** | ✅ مؤكد — رقم الهاتف مدرج في Play Store + وصف التطبيق. |
| **قابلية التغيير** | ⚠️ صعب — phone مدمج في auth flow. التغيير يحتاج تعديل كل auth-related code. |
| **مناسب للسوق اليمني** | ✅ نعم — الهاتف هو المعيار في اليمن. |
| **التصنيف** | ✅ KEEP |

---

## ملخص التصنيف النهائي

```
┌─────────────────────────────────────────────────────────────┐
│                    ملخص التصنيف                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ✅ KEEP (10 قرارات — تبقى كما هي):                         │
│  ├── Laravel 11                                              │
│  ├── MySQL 8.0                                               │
│  ├── Redis (كمكون اختياري)                                   │
│  ├── Laravel Sanctum                                         │
│  ├── Vue.js SPA منفصل                                       │
│  ├── Service Layer                                           │
│  ├── 9 جداول أساسية                                         │
│  ├── Product Snapshot                                        │
│  ├── Cart Constraint                                         │
│  └── Authentication by Phone                                 │
│                                                              │
│  ⚠️ REVIEW (4 قرارات — تحتاج تعديل بسيط):                  │
│  ├── Address Snapshot → سأضيف delivery_city عمود منفصل      │
│  ├── Order Number → سأصلح race condition                     │
│  ├── Status Machine → سأضيف returned status                 │
│  └── Storage → سأعدّل لـ S3-compatible لاحقاً               │
│                                                              │
│  ❌ CHANGE (0 قرارات)                                        │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## الإجراءات المطلوبة

التعديلات الثلاثة البسيطة سأطبقها الآن:

1. **Address Snapshot**: إضافة `delivery_city` عمود في orders table
2. **Order Number**: إنشاء `order_sequences` table + Atomic counter
3. **Status Machine**: إضافة `returned` status

**هل تريدني أطبق هذه التعديلات الآن ثم أكمل البناء؟**
