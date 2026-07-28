# 🔧 خطة الإصلاح المركزة — SAFO Remaining Issues
## مبنية على تحليل My Goods (بضاعتي) الأصلي

---

## المشكلة 1: Live Site / Production

**حالة My Goods:** يعمل على `mersalmygoods.com.ye` — VPS حقيقي (Ubuntu + Nginx + MySQL + Redis + SSL)

**حالة SAFO:** `safo-markt.wuaze.com` معطل (InfinityFree = shared hosting مجاني غير موثوق)

### الحل:
```
الخيار الأفضل: Railway.app (Free tier)
├── Laravel Backend → Railway (MySQL + Redis مدمجان)
├── Supplier Dashboard → GitHub Pages (static)
├── Admin Dashboard → GitHub Pages (static)
└── API URL → https://safo-app.up.railway.app/api/v1

البديل: Render.com (Free tier)
├── Web Service → Laravel
├── PostgreSQL مجاناً
└── Static sites → GitHub Pages
```

### ما يلزمك يدوياً:
1. أنشئ حساب Railway.app
2. اربط GitHub repo `htsoftpro-ui/safo-app`
3. Railway سيكتشف `Dockerfile` تلقائياً
4. أضف MySQL service من Railway dashboard
5. Environment variables من `.env.production`

---

## المشكلة 2: APP_KEY

**حالة My Goods:** مُولّد على السيرفر أثناء التثبيت

**حالة SAFO:** placeholder في `.env.production`

### الحل:
يُولّد تلقائياً عند أول deploy:
```bash
php artisan key:generate --force
```
Railway يفعل هذا تلقائياً إذا أضفت `buildCommand` في `railway.toml`:
```toml
[build]
builder = "nixpacks"

[deploy]
startCommand = "php artisan key:generate --force && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT"
```

---

## المشكلة 3: CI/CD + MySQL Migrations

**حالة My Goods:** deployment يدوي على VPS مع `prisma migrate deploy`

**حالة SAFO:** CI يفشل في `Run Migrations` بسبب MySQL connection

### المشكلة:
CI workflow يستخدم MySQL service container لكن:
- `DB_USERNAME` كان `safo` بدلاً من `root` → تم إصلاحه
- `DB_PASSWORD` sed command لا يتطابق → تم إصلاحه
- لكن لا يزال يفشل — السبب الأرجح: MySQL container لم يكمل التهيئة

### الحل:
```yaml
# إضافة sleep للتأكد من جاهزية MySQL
- name: Wait for MySQL
  run: |
    for i in $(seq 1 30); do
      mysqladmin ping -h 127.0.0.1 --silent && break
      echo "Waiting for MySQL... $i"
      sleep 2
    done

- name: Run Migrations
  run: php artisan migrate --force
```

---

## المشكلة 4: CORS + Sanctum

**حالة My Goods:** CORS headers في Nginx config + Sanctum stateful domains

**حالة SAFO:** `config/cors.php` موجود لكن لم يُختبر

### الحل الموجود:
```php
// config/cors.php — موجود ✅
'allowed_origins' => [
    env('FRONTEND_URL'),
    'https://htsoftpro-ui.github.io',
],
'allowed_origins_patterns' => [
    '#^https://.*\.github\.io$#',
    '#^https://.*\.railway\.app$#',
],

// bootstrap/app.php — موجود ✅
$middleware->api(prepend: [
    \Illuminate\Http\Middleware\HandleCors::class,
]);
```

### المطلوب إضافته:
```php
// config/sanctum.php — يحتاج تحديث
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 
    'localhost,localhost:3000,localhost:5173,htsoftpro-ui.github.io'
)),
```

---

## المشكلة 5: Queue Worker

**حالة My Goods:** PM2 + Node.js workers على VPS

**حالة SAFO:** `QUEUE_CONNECTION=database` مُعدّ لكن لا يوجد worker

### الحل:
```toml
# railway.toml
[deploy]
startCommand = "php artisan migrate --force && php artisan queue:work & php artisan serve --host=0.0.0.0 --port=$PORT"
```

أو استخدام Laravel Horizon على Railway:
```bash
composer require laravel/horizon
php artisan horizon
```

---

## المشكلة 6: Admin Dashboard

**حالة My Goods:** لوحة إدارة كاملة (إحصائيات + مستخدمين + محتوى + طلبات + تقارير + إعدادات)

**حالة SAFO:** الكود موجود (6 controllers + 8 pages) لكن GitHub Pages غير مُفعّل

### الحل:
1. **فعّل GitHub Pages** من Settings → Source: GitHub Actions
2. Deploy workflow موجود: `.github/workflows/deploy-admin.yml`
3. Admin Dashboard سيعمل على: `https://htsoftpro-ui.github.io/safo-admin/`

---

## المشكلة 7: OTP / SMS

**حالة My Goods:**
```
التسجيل: phone → OTP (SMS) → verify → complete
استعادة كلمة المرور: phone → OTP (SMS) → new password
```

**حالة SAFO:** DB fields موجودة (`otp_code`, `otp_expires_at`) لكن لا يوجد implementation

### الحل — إضافة SMS OTP:

**الخيار 1: Twilio (عالمي)**
```php
// .env
TWILIO_SID=your_sid
TWILIO_AUTH_TOKEN=your_token
TWILIO_FROM=+1234567890
```

**الخيار 2: SMS gateway يمني (أرخص)**
- Yemen Mobile API
- Sabafon API
- أي SMS gateway محلي

**الكود المطلوب:**
```php
// app/Services/SmsService.php
class SmsService {
    public function sendOtp(string $phone, string $otp): bool {
        // Twilio أو gateway محلي
    }
}

// app/Http/Controllers/API/AuthController.php
public function register(RegisterRequest $request) {
    $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    // Save OTP to user
    // Send SMS
    // Return: "تم إرسال رمز التحقق"
}

public function verifyOtp(Request $request) {
    // Verify OTP
    // Mark user as verified
}
```

---

## المشكلة 8: Payment Gateway

**حالة My Goods:**
- نقدي عند الاستلام ✅
- دفع بالآجل (ائتمان) ✅
- محفظة إلكترونية (محتمل)

**حالة SAFO:** فقط `cash` — payment_method field موجود لكن لا يوجد gateway

### الحل:

**المرحلة 1:** تفعيل الدفع بالآجل (Credit)
```php
// في Order model — موجود بالفعل:
const PAYMENT_METHOD_CREDIT = 'credit';

// المطلوب: إضافة credit limit للمورد
// suppliers table: credit_limit, credit_used
```

**المرحلة 2:** بوابة دفع يمنية
- **Jeebly Pay** — بوابة دفع يمنية
- **Kashier** — بوابة دفع عربية
- **Stripe** — إذا السوق الدولي

```php
// app/Services/PaymentService.php
class PaymentService {
    public function createPayment(Order $order): string {
        // Create payment session
        // Return payment URL
    }
    
    public function handleCallback(Request $request): void {
        // Verify payment
        // Update order status
    }
}
```

---

## المشكلة 9: Admin APIs + RBAC

**حالة My Goods:** Admin APIs مع role-based access

**حالة SAFO:** Admin APIs موجودة مع `role:admin` middleware

### الموجود فعلياً:
```php
// routes/api.php
Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->group(function () {
        // 25+ endpoints ✅
    });

// CheckRole middleware ✅
if (!$user || !in_array($user->type, $roles)) → 403
```

### المطلوب للاختبار:
- Backend يعمل على Railway
- Admin user من seeder: `770000001` / `password123`
- اختبار كل endpoint مع Postman أو curl

---

## ملخص الإجراءات

### يدوي (يحتاج صلاحياتك):
| # | الإجراء | الأداة |
|---|---------|--------|
| 1 | أنشئ Railway account | railway.app |
| 2 | اربط GitHub repo | Railway dashboard |
| 3 | أضف MySQL service | Railway dashboard |
| 4 | فعّل GitHub Pages | Settings → Pages → GitHub Actions |
| 5 | فعّل CI/CD environment | Settings → Environments → github-pages |
| 6 | اختر SMS gateway | Twilio أو محلي |
| 7 | اختر Payment gateway | Jeebly أو Kashier |

### آلي (أكتبه لك):
| # | الإجراء | الحالة |
|---|---------|--------|
| 1 | إصلاح CI workflow (MySQL wait) | أقدر أكتبه |
| 2 | railway.toml configuration | أكتبه |
| 3 | SMS OTP service | أكتبه |
| 4 | Payment service | أكتبه |
| 5 | Credit payment system | أكتبه |

---

## الأولوية

```
1. Railway deployment    → يحل 5 مشاكل (Live + APP_KEY + Queue + CORS + Admin)
2. GitHub Pages activation → يحل مشكلة Admin Dashboard
3. CI/CD fix             → يحل مشكلة الـ testing
4. SMS OTP               → يحل مشكلة التحقق
5. Payment Gateway       → يحل مشكلة الدفع
```

**هل تريدني أن أكتب الكود لـ SMS OTP + Payment Gateway + Railway config الآن؟**
