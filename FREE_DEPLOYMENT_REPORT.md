# FREE_DEPLOYMENT_REPORT.md

> Safo B2B — Free Deployment Guide (Updated)
> Date: 2026-07-25

---

## البدائل المجانية المتاحة

### الخيار 1: Railway.app (الأفضل للاختبار)

| البند | التفاصيل |
|-------|---------|
| **الرابط** | https://railway.com |
| **السعر** | $5 رصيد مجاني (30 يوم) |
| **بطاقة بنكية** | ❌ لا تحتاج |
| **MySQL** | ✅ مدعوم |
| **Redis** | ✅ مدعوم |
| **PHP 8.2** | ✅ مدعوم |
| **Queue** | ✅ مدعوم |
| **Cron** | ✅ مدعوم |
| **Docker** | ✅ مدعوم |
| **Custom Domain** | ✅ مدعوم |
| **SSL** | ✅ مجاني |
| **ينام** | ❌ لا ينام |

**الخطوات:**
1. سجل في https://railway.com (بـ GitHub أو Google)
2. أنشئ مشروع جديد
3. أضف MySQL service
4. أضف Redis service
5. أضف API service من GitHub repo
6. Railway يستخدم `railway.json` + `Dockerfile.railway` تلقائياً
7. أضف Environment Variables
8. انشر!

**بعد 30 يوم:** $1/شهر فقط (أقل من قهوة)

---

### الخيار 2: InfinityFree (مجاني للأبد)

| البند | التفاصيل |
|-------|---------|
| **الرابط** | https://www.infinityfree.com |
| **السعر** | مجاني للأبد |
| **بطاقة بنكية** | ❌ لا تحتاج |
| **MySQL** | ✅ مدعوم |
| **Redis** | ❌ غير مدعوم |
| **PHP 8.3** | ✅ مدعوم |
| **Queue** | ❌ غير مدعوم |
| **Cron** | ❌ غير مدعوم |
| **SSH** | ❌ غير مدعوم |
| **Custom Domain** | ✅ مدعوم |
| **SSL** | ✅ مجاني |
| **ينام** | ❌ لا ينام |

**القيود:**
- لا Redis → نستخدم file cache
- لا Queue → نستخدم sync queue
- لا Cron → بدون scheduler
- لا SSH → رفع عبر FTP
- 10 ثانية حد أقصى للـ PHP execution

**يعمل:**
- Auth (register/login/logout)
- Products (browse/search/filter)
- Cart (add/update/delete)
- Orders (create/cancel/track)
- Addresses (CRUD)
- Profile (get/update)
- Supplier operations

**لا يعمل:**
- Background queue jobs
- Scheduled tasks
- Push notifications
- Real-time features

---

### الخيار 3: GitHub Pages + Railway DB

لوحة المورد على GitHub Pages (مجاني للأبد) + API على Railway.

---

## التوصية

**لاختبار سريع (30 يوم):** Railway.app — يدعم كل شيء

**لمجاني دائم:** InfinityFree — يعمل مع قيود بسيطة

**الأفضل:** Railway ($1/شهر بعد الفترة المجانية) — أرخص من قهوة

---

## ملفات النشر جاهزة

| الملف | الغرض |
|-------|-------|
| `Dockerfile.railway` | Docker image لـ Railway |
| `railway.json` | Railway deployment config |
| `deploy-oracle-free.sh` | Oracle Cloud setup (إذا تحققت لاحقاً) |
| `.github/workflows/deploy-dashboard.yml` | GitHub Pages للـ Dashboard |

---

## طريقة النشر على Railway (الخطوات التفصيلية)

### 1. سجل في Railway
```
https://railway.com → Sign up with GitHub
```

### 2. أنشئ مشروع
```
New Project → Deploy from GitHub Repo → htsoftpro-ui/safo-app
```

### 3. أضف قاعدة البيانات
```
New → Database → MySQL → يُنشأ تلقائياً
```

### 4. أضف Redis
```
New → Database → Redis → يُنشأ تلقائياً
```

### 5. أضف Variables
```
APP_KEY=base64:xxx (generate with: php artisan key:generate --show)
APP_ENV=production
APP_DEBUG=false
```

### 6. Railway يكتشف تلقائياً
```
Dockerfile.railway → يبني المشروع
railway.json → يحدد أمر التشغيل
```

### 7. احصل على الرابط
```
Settings → Generate Domain
→ https://safo-api-production-xxxx.up.railway.app
```

### 8. اختبر
```
curl https://safo-api-production-xxxx.up.railway.app/api/v1/health
```

---

## تحديث الروابط في المشروع

### Vue Dashboard
```typescript
// src/api/index.ts
const api = axios.create({
  baseURL: 'https://safo-api-production-xxxx.up.railway.app/api/v1',
})
```

### Android App
```kotlin
// di/NetworkModule.kt
private const val BASE_URL = "https://safo-api-production-xxxx.up.railway.app/api/v1/"
```

---

## النتيجة

| المكون | الرابط | الحالة |
|--------|--------|--------|
| Laravel API | https://safo-api-xxx.up.railway.app/api/v1 | ⏳ بعد النشر |
| Vue Dashboard | https://htsoftpro-ui.github.io/safo-app/ | ⏳ بعد تفعيل GH Pages |
| Android | يتصل بالـ API | ⏳ بعد تحديث URL |

**التكلفة:** $0 لمدة 30 يوم، ثم $1/شهر
