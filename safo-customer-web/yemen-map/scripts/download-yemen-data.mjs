import { mkdir, writeFile } from 'node:fs/promises';
import { createWriteStream } from 'node:fs';
import { pipeline } from 'node:stream/promises';
import { Readable } from 'node:stream';
import { dirname, resolve } from 'node:path';

const root = resolve(new URL('..', import.meta.url).pathname);
const dataDir = resolve(root, 'public/data');
const source = 'https://download.geofabrik.de/asia/yemen-shortbread-1.0.mbtiles';
const target = resolve(dataDir, 'yemen-shortbread-1.0.mbtiles');
const readme = resolve(dataDir, 'README.md');

await mkdir(dirname(target), { recursive: true });
console.log(`Downloading ${source}`);
const response = await fetch(source, { redirect: 'follow' });
if (!response.ok || !response.body) throw new Error(`Download failed: ${response.status} ${response.statusText}`);
await pipeline(Readable.fromWeb(response.body), createWriteStream(target));
await writeFile(readme, `# حزمة بيانات اليمن\n\nهذه الحزمة مصدرها [Geofabrik Yemen](https://download.geofabrik.de/asia/yemen.html)، وهي استخراج من بيانات [OpenStreetMap](https://www.openstreetmap.org/copyright) بصيغة Shortbread/MBTiles.\n\nالترخيص: OpenStreetMap contributors، ODbL 1.0. يجب إبقاء الإسناد ظاهرًا في التطبيق.\n\n## التحويل إلى PMTiles\n\nتُحفظ نسخة MBTiles المصدرية هنا لإعادة البناء. لتحويلها إلى PMTiles استخدم أداة PMTiles/Protomaps المناسبة في بيئة تجهيز البيانات، ثم ضع الناتج باسم `yemen-shortbread-1.0.pmtiles` داخل هذا المجلد. لا يطلب التطبيق أي مصدر شبكة عند وجود الحزمة المحلية.\n`);
console.log(`Saved ${target}`);
