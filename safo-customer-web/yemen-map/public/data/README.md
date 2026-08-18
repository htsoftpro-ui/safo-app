# حزمة بيانات خريطة اليمن

يحتوي `yemen-shortbread-1.0.pmtiles` على استخراج Vector Tiles لليمن من [Geofabrik Yemen](https://download.geofabrik.de/asia/yemen.html)، المبني على بيانات [OpenStreetMap contributors](https://www.openstreetmap.org/copyright). الأرشيف بصيغة PMTiles، ويُقرأ بواسطة MapLibre عبر بروتوكول محلي دون تحميل آلاف الملفات أو طلب خدمة خرائط خارجية.

## الترخيص والإسناد

البيانات مرخصة بموجب Open Database License 1.0، ويجب إبقاء إسناد OpenStreetMap ظاهرًا عند توزيع أو عرض الخريطة.

## الطبقات المتوفرة

تتضمن الحزمة طبقات Shortbread مثل `boundaries` و`place_labels` و`streets` و`street_labels` و`buildings` و`water_polygons` و`water_lines` و`land` و`pois`. تختلف التفاصيل حسب مستوى التكبير؛ المباني وبعض الشوارع التفصيلية تبدأ من مستويات التكبير العليا في المصدر.

## إعادة البناء

استخدم `pnpm data:download` لتنزيل نسخة MBTiles الرسمية الحديثة. بعد ذلك حوّلها إلى PMTiles باستخدام أداة Protomaps المناسبة، ثم ضع الناتج باسم `yemen-shortbread-1.0.pmtiles` داخل هذا المجلد. لا يعتمد التطبيق وقت التشغيل على Geofabrik أو OpenStreetMap؛ المصادر الخارجية مطلوبة فقط أثناء تجهيز تحديث جديد للبيانات.

## التضاريس

حزمة DEM ليست مضمنة تلقائيًا في هذا الإصدار لأن مصدر الارتفاعات يحتاج تجميعًا منفصلًا وتحققًا من الترخيص والحجم. التطبيق لا يطلب ملفات DEM غير الموجودة؛ ويُفعّل Terrain/Hillshade تلقائيًا فقط عند وجود `dem/manifest.json` وراستر الارتفاعات المحلي.
