# بحث معمارية خريطة اليمن Offline

## نتائج التحقق

توثيق MapLibre الرسمي يوضح أن MapLibre GL JS مكتبة TypeScript تستخدم WebGL لرسم الخرائط التفاعلية، وأنها تستطيع استخدام إضافة PMTiles وبروتوكول `pmtiles://` كمصدر Vector Tiles. المثال الرسمي يبيّن تسجيل `pmtiles.Protocol`، إضافة البروتوكول إلى MapLibre، ثم تعريف مصدر متجه داخل Style JSON، مع طبقات مستقلة للطرق والمباني وطبقات أخرى.

يوفر PMTiles أرشيفًا واحدًا للبيانات المبلطة يمكن قراءته مباشرة من المتصفح عبر MapLibre، ما يلائم حزم البيانات المحلية وتقليل عدد الملفات. يجب تصميم التطبيق بحيث يمكن تبديل مصدر PMTiles المحلي مع مصدر حزمة قابلة للتنزيل، مع عدم الاعتماد على عناوين خارجية أثناء التشغيل Offline.

## قرار مبدئي

سيكون محرك العرض MapLibre GL JS، وستكون بيانات الخريطة المتجهة في PMTiles مقسمة منطقيًا إلى حزمة أساس وحزم تفاصيل اختيارية. ستُستخدم مصادر Raster DEM أو Terrain-RGB محلية عندما تتوفر، مع hillshade وterrain في Style JSON. البحث سيكون محليًا من ملف فهرس مضغوط أو SQLite/JSON مضمّن، ولن يُستخدم Google Geocoding API.

## مصادر

1. MapLibre GL JS: PMTiles source and protocol — https://maplibre.org/maplibre-gl-js/docs/examples/pmtiles-source-and-protocol/
2. MapLibre GL JS documentation — https://www.maplibre.org/maplibre-gl-js/docs/
3. Protomaps PMTiles documentation — https://docs.protomaps.com/pmtiles/maplibre
4. OpenStreetMap copyright and attribution — https://www.openstreetmap.org/copyright
