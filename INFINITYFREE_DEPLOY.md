# InfinityFree Deployment Guide

## الخطوات

### 1. سجل في InfinityFree
- اذهب إلى https://www.infinityfree.com
- سجل حساب مجاني (بريد + كلمة مرور فقط)
- لا تحتاج بطاقة بنكية

### 2. أنشئ موقع
- من لوحة التحكم → Create Account
- اختر اسم نطاق (مثال: safo.infinityfreeapp.com)
- أو استخدم نطاقك الخاص

### 3. أنشئ قاعدة بيانات MySQL
- من لوحة الت⡱ف → MySQL Databases
- أنشئ قاعدة بيانات جديدة
- **احفظ**: اسم القاعدة، اسم المستخدم، كلمة المرور، العنوان (sql.infinityfree.com)

### 4. استورد قاعدة البيانات
- اذهب إلى phpMyAdmin (من لوحة التحكم)
- اختر قاعدة البيانات
- اضغط Import
- اختر الملف: `database/safo_mysql.sql`
- اضغط Go

### 5. ارفع الملفات
- من File Manager أو FTP
- ارفع محتويات `safo-backend/` إلى `htdocs/`
- **مهم**: محتويات `public/` تذهب إلى `htdocs/` مباشرة
- بقية الملفات (app, config, database, etc.) تبقى في `htdocs/`

### 6. هيئ public_html
- المجلد الرئيسي يجب أن يحتوي:
  - index.php
  - .htaccess
  - robots.txt
  - وباقي ملفات public/

### 7. عدّل index.php
عدّل المسارات في `index.php`:
```php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```
إلى:
```php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
```

### 8. عدّل .env
- انسخ `.env.infinityfree` إلى `.env`
- عدّل APP_KEY (استخدم أي أداة لتوليد مفتاح)
- عدّل بيانات قاعدة البيانات

### 9. عدّل bootstrap/app.php
تأكد أن المسارات صحيحة.

### 10. اختبر
```
https://YOUR_DOMAIN.infinityfreeapp.com/api/v1/health
```

---

## بيانات الاختبار

| الدور | الهاتف | كلمة المرور |
|-------|--------|-------------|
| Admin | 770000001 | password123 |
| Supplier | 771000001 | password123 |
| Customer | 772000001 | password123 |

---

## القيود

- لا SSH → لا artisan commands
- لا Redis → file cache
- لا Queue → sync
- لا Cron → بدون scheduler
- 10 ثانية حد أقصى للـ PHP
- 5 GB مساحة
- MySQL محدود

## ما يعمل

- ✅ Auth (register/login/logout)
- ✅ Products (browse/search/filter)
- ✅ Cart (add/update/delete)
- ✅ Orders (create/cancel/track)
- ✅ Addresses (CRUD)
- ✅ Profile
- ✅ Supplier operations

## ما لا يعمل

- ❌ Background queue jobs
- ❌ Scheduled tasks
- ❌ Push notifications
- ❌ Image upload (needs storage link)
