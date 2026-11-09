# T3al (تعال)

بطاقات ولاء تنحفظ في محفظة الجوال (Apple Wallet و Google Wallet)، بدون تطبيق.
T3al: loyalty cards in the phone wallet, no app required.

## المتطلبات / Requirements

- PHP 8.3 أو أحدث، مع الإضافات: `intl`، `zip`، `gd`، `pdo_pgsql`، `mbstring`، `openssl`، `curl`، `fileinfo`
- PostgreSQL 16 أو أحدث
- Composer 2، Node.js 20 أو أحدث

## التشغيل محلياً / Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

أنشئ قاعدة بيانات PostgreSQL، وعدّل بيانات `DB_*` في `.env`، ثم:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

- الموقع: http://127.0.0.1:8000
- لوحة المحل: http://127.0.0.1:8000/app
- لوحة الإدارة: http://127.0.0.1:8000/admin (الحساب التجريبي: `admin@t3al.app` / `password`)

الحسابات التجريبية بعد `php artisan migrate:fresh --seed`: `owner@example.com` و`cashier@example.com` (كلمة المرور `password`).

## الدومينات / Domains

| السطح | الدومين | متغير البيئة |
|---|---|---|
| الموقع التسويقي | `t3al.app` | `T3AL_MARKETING_DOMAIN` |
| لوحة المحل والكاشير | `panel.t3al.app` | `T3AL_PANEL_DOMAIN` |
| الإدارة | `admin.t3al.app` | `T3AL_ADMIN_DOMAIN` |

إذا تركت المتغيرات فاضية، تعمل اللوحات على مسارات (`/app`، `/admin`) وهذا مناسب للتطوير المحلي.

## الاختبارات / Tests

```bash
php artisan test
```

الاختبارات تستخدم قاعدة PostgreSQL منفصلة اسمها `t3al_test`، وتُعرّف في `phpunit.xml`.

## اللغات / Languages

العربية هي الافتراضية، والإنجليزية متاحة من الموقع. ملفات الترجمة في `lang/ar` و`lang/en`.

## الاشتراكات / Billing

- الباقات تحددها من لوحة الإدارة: السعر لكل فرع شهرياً، والمميزات.
- الفاتورة الشهرية = عدد الفروع × سعر الباقة.
- الدفع بتحويل بنكي، والإدارة تسجّل الدفعة من صفحة المحلات، فيتمدد الاشتراك تلقائياً.
- المحل اللي تنتهي تجربته أو اشتراكه يتوقف تلقائياً كل يوم (`merchants:suspend-expired`)، والبيانات تبقى محفوظة.

## الحالة الحالية

هذي نسخة الأساس: الدومينات، واللغتان، واللوحتان مع التحكم بالصلاحيات، وقاعدة PostgreSQL. ميزات البطاقات والكاشير وطلبات الانتظار تُضاف في المراحل الجاية حسب وثيقة المنتج.
