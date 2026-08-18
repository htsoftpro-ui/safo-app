import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import maplibregl, { Map as MapLibreMap, MapMouseEvent, StyleSpecification } from 'maplibre-gl';
import { Protocol, PMTiles } from 'pmtiles';
import { Search, LocateFixed, Maximize, Minimize, Layers3, Moon, Sun, RotateCcw, MapPinned, Mountain, Building2, Route, Map as MapIcon, WifiOff, Info, X } from 'lucide-react';
import 'maplibre-gl/dist/maplibre-gl.css';
import './styles.css';

type SearchItem = { id: string; name: string; type: string; lng: number; lat: number; aliases?: string[] };

type FeatureCollection = GeoJSON.FeatureCollection<GeoJSON.Geometry, Record<string, unknown>>;

const YEMEN_CENTER: [number, number] = [47.7, 15.55];
const YEMEN_BOUNDS: [[number, number], [number, number]] = [[42.45, 12.0], [54.55, 19.1]];
const DATA_ROOT = new URL('./data/', window.location.href).toString();
const PMTILES_URL = new URL('./yemen-shortbread-1.0.pmtiles', new URL('./data/', window.location.href)).toString();

const fallbackPlaces: SearchItem[] = [
  { id: 'sanaa', name: 'صنعاء', type: 'مدينة', lng: 44.206, lat: 15.369 },
  { id: 'aden', name: 'عدن', type: 'مدينة', lng: 45.0187, lat: 12.7855 },
  { id: 'taiz', name: 'تعز', type: 'مدينة', lng: 44.0209, lat: 13.5789 },
  { id: 'hodeidah', name: 'الحديدة', type: 'مدينة', lng: 42.954, lat: 14.7978 },
  { id: 'ibb', name: 'إب', type: 'مدينة', lng: 44.18, lat: 13.9667 },
  { id: 'mukalla', name: 'المكلا', type: 'مدينة', lng: 49.1277, lat: 14.5425 },
  { id: 'marib', name: 'مأرب', type: 'مدينة', lng: 45.325, lat: 15.462 },
  { id: 'saada', name: 'صعدة', type: 'مدينة', lng: 43.761, lat: 16.94 },
  { id: 'dhamar', name: 'ذمار', type: 'مدينة', lng: 44.405, lat: 14.55 },
  { id: 'sayun', name: 'سيئون', type: 'مدينة', lng: 48.831, lat: 15.943 },
];

const fallbackGeoJSON: FeatureCollection = {
  type: 'FeatureCollection',
  features: fallbackPlaces.map((place) => ({
    type: 'Feature',
    properties: { name: place.name, kind: place.type, id: place.id },
    geometry: { type: 'Point', coordinates: [place.lng, place.lat] },
  })),
};

const baseStyle: StyleSpecification = {
  version: 8,
  sources: {
    fallback: { type: 'geojson', data: fallbackGeoJSON },
  },
  layers: [
    { id: 'background', type: 'background', paint: { 'background-color': '#dfe8e3' } },
    { id: 'fallback-points', type: 'circle', source: 'fallback', paint: { 'circle-color': '#0f766e', 'circle-radius': 5, 'circle-stroke-color': '#ffffff', 'circle-stroke-width': 1.5 } },
    { id: 'fallback-labels', type: 'symbol', source: 'fallback', layout: { 'text-field': ['get', 'name'], 'text-size': 13, 'text-font': ['Open Sans Regular'], 'text-offset': [0, 1.1], 'text-anchor': 'top' }, paint: { 'text-color': '#14332f', 'text-halo-color': '#ffffff', 'text-halo-width': 1.5 } },
  ],
};

function buildMapStyle(): StyleSpecification {
  const style: StyleSpecification = JSON.parse(JSON.stringify(baseStyle));
  style.sources = {
    ...style.sources,
    yemen: { type: 'vector', url: `pmtiles://${PMTILES_URL}`, attribution: '© OpenStreetMap contributors · Geofabrik' },
  };
  style.layers = [
    { id: 'country-boundaries', type: 'line', source: 'yemen', 'source-layer': 'boundaries', minzoom: 0, paint: { 'line-color': '#50756d', 'line-width': ['interpolate', ['linear'], ['zoom'], 3, 0.8, 8, 2.2, 14, 3.5], 'line-opacity': 0.86 } },
    { id: 'place-dots', type: 'circle', source: 'yemen', 'source-layer': 'place_labels', minzoom: 3, paint: { 'circle-color': '#147d72', 'circle-radius': ['interpolate', ['linear'], ['zoom'], 3, 2.2, 10, 4.2, 14, 6], 'circle-stroke-color': '#ffffff', 'circle-stroke-width': 1.2 } },
    { id: 'land', type: 'fill', source: 'yemen', 'source-layer': 'land', minzoom: 4, paint: { 'fill-color': ['match', ['get', 'kind'], 'farmland', '#d5e5bb', 'orchard', '#c9dfb2', 'forest', '#a9c7a5', 'grass', '#dce8c8', '#dfe8e3'], 'fill-opacity': 0.8 } },
    { id: 'water', type: 'fill', source: 'yemen', 'source-layer': 'water_polygons', paint: { 'fill-color': '#a9d7e5', 'fill-opacity': 0.92 } },
    { id: 'waterway', type: 'line', source: 'yemen', 'source-layer': 'water_lines', minzoom: 8, paint: { 'line-color': '#63acc7', 'line-width': ['interpolate', ['linear'], ['zoom'], 8, 0.6, 14, 2] } },
    { id: 'roads-major-casing', type: 'line', source: 'yemen', 'source-layer': 'streets', minzoom: 5, filter: ['in', 'kind', 'motorway', 'trunk', 'primary'], paint: { 'line-color': '#ffffff', 'line-width': ['interpolate', ['linear'], ['zoom'], 5, 1.2, 12, 5, 16, 10] } },
    { id: 'roads-major', type: 'line', source: 'yemen', 'source-layer': 'streets', minzoom: 5, paint: { 'line-color': ['match', ['get', 'kind'], 'motorway', '#d97706', 'trunk', '#e59b38', 'primary', '#f4c46e', 'secondary', '#f1d7a0', '#eadfca'], 'line-width': ['interpolate', ['linear'], ['zoom'], 5, 0.7, 12, 2.5, 17, 8] } },
    { id: 'buildings', type: 'fill', source: 'yemen', 'source-layer': 'buildings', minzoom: 14, paint: { 'fill-color': '#c8b9a1', 'fill-opacity': 0.72, 'fill-outline-color': '#a9957b' } },
    { id: 'places', type: 'symbol', source: 'yemen', 'source-layer': 'place_labels', minzoom: 4, layout: { 'text-field': ['coalesce', ['get', 'name:ar'], ['get', 'name']], 'text-size': ['interpolate', ['linear'], ['zoom'], 4, 10, 9, 13, 14, 16], 'text-font': ['Open Sans Regular'], 'text-allow-overlap': false }, paint: { 'text-color': '#263e3c', 'text-halo-color': '#f8fbf8', 'text-halo-width': 1.4 } },
    ...style.layers,
  ];
  return style;
}

function App() {
  const mapContainer = useRef<HTMLDivElement>(null);
  const mapRef = useRef<MapLibreMap | null>(null);
  const [mapReady, setMapReady] = useState(false);
  const [packAvailable, setPackAvailable] = useState(false);
  const [demAvailable, setDemAvailable] = useState(false);
  const [darkMode, setDarkMode] = useState(false);
  const [terrainEnabled, setTerrainEnabled] = useState(true);
  const [layersOpen, setLayersOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  const [query, setQuery] = useState('');
  const [selected, setSelected] = useState<{ name?: string; type?: string; lng?: number; lat?: number } | null>(null);
  const [locationStatus, setLocationStatus] = useState('بيانات محلية جاهزة');

  const searchResults = useMemo(() => {
    const normalized = query.trim().toLowerCase();
    if (!normalized) return fallbackPlaces.slice(0, 6);
    return fallbackPlaces.filter((item) => [item.name, ...(item.aliases ?? [])].join(' ').toLowerCase().includes(normalized)).slice(0, 8);
  }, [query]);

  useEffect(() => {
    if (!mapContainer.current || mapRef.current) return;
    const protocol = new Protocol();
    maplibregl.addProtocol('pmtiles', protocol.tile);
    const pmtiles = new PMTiles(PMTILES_URL);
    protocol.add(pmtiles);
    const map = new maplibregl.Map({
      container: mapContainer.current,
      style: buildMapStyle(),
      center: YEMEN_CENTER,
      zoom: 5.2,
      minZoom: 3,
      maxZoom: 19,
      maxBounds: YEMEN_BOUNDS,
      renderWorldCopies: false,
      cooperativeGestures: false,
      attributionControl: false,
      dragRotate: false,
      pitchWithRotate: false,
      fadeDuration: 180,
    });
    mapRef.current = map;
    map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'bottom-left');
    map.addControl(new maplibregl.ScaleControl({ maxWidth: 120, unit: 'metric' }), 'bottom-right');
    map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-right');
    map.on('load', async () => {
      try {
        const response = await fetch(PMTILES_URL, { method: 'HEAD', cache: 'no-store' });
        if (response.ok) {
          setPackAvailable(true);
          const demResponse = await fetch(`${DATA_ROOT}dem/manifest.json`, { method: 'HEAD', cache: 'no-store' });
          setDemAvailable(demResponse.ok);
          setLocationStatus(demResponse.ok ? 'حزمة اليمن والتضاريس المحلية متاحة' : 'حزمة اليمن المحلية متاحة — أضف حزمة DEM لتفعيل التضاريس');
        } else {
          setLocationStatus('وضع المعاينة المحلية — أضف حزمة البيانات لتفعيل التفاصيل الكاملة');
        }
      } catch {
        setLocationStatus('بدون اتصال — تعمل الطبقات المحلية المتاحة');
      }
      setMapReady(true);
    });
    map.on('click', (event: MapMouseEvent) => {
      const features = map.queryRenderedFeatures(event.point);
      const feature = features.find((item) => item.properties && (item.properties.name || item.properties['name:ar']));
      if (feature) {
        setSelected({ name: String(feature.properties?.['name:ar'] || feature.properties?.name), type: String(feature.layer?.id || 'معلم'), lng: event.lngLat.lng, lat: event.lngLat.lat });
      } else {
        setSelected(null);
      }
    });
    return () => {
      map.remove();
      maplibregl.removeProtocol('pmtiles');
      mapRef.current = null;
    };
  }, []);

  useEffect(() => {
    const map = mapRef.current;
    if (!map || !mapReady) return;
    if (terrainEnabled && demAvailable) {
      if (!map.getSource('dem')) map.addSource('dem', { type: 'raster-dem', tiles: [`${DATA_ROOT}dem/{z}/{x}/{y}.png`], tileSize: 256, maxzoom: 12, encoding: 'terrarium' });
      map.setTerrain({ source: 'dem', exaggeration: 1.15 });
      if (!map.getLayer('hillshade')) map.addLayer({ id: 'hillshade', type: 'hillshade', source: 'dem', paint: { 'hillshade-shadow-color': '#33504b', 'hillshade-highlight-color': '#f4e8c9', 'hillshade-exaggeration': 0.45 } }, 'land');
    } else if (map.getLayer('hillshade')) {
      map.removeLayer('hillshade');
      if (map.getTerrain()) map.setTerrain(null);
    }
  }, [terrainEnabled, demAvailable, mapReady]);

  const flyTo = (item: SearchItem) => {
    mapRef.current?.flyTo({ center: [item.lng, item.lat], zoom: 12.5, duration: 1100, essential: true });
    setSearchOpen(false);
    setQuery('');
    setSelected(item);
  };

  const locate = () => {
    if (!navigator.geolocation) return setLocationStatus('المتصفح لا يدعم تحديد الموقع');
    setLocationStatus('جارٍ طلب الموقع…');
    navigator.geolocation.getCurrentPosition((position) => {
      mapRef.current?.flyTo({ center: [position.coords.longitude, position.coords.latitude], zoom: 14, duration: 900 });
      setLocationStatus('تم تحديد موقعك');
    }, () => setLocationStatus('تعذر الحصول على الموقع')); 
  };

  const reset = () => mapRef.current?.fitBounds(YEMEN_BOUNDS, { padding: 48, duration: 900 });
  const fullscreen = () => document.fullscreenElement ? document.exitFullscreen() : mapContainer.current?.parentElement?.requestFullscreen();

  return <main className={darkMode ? 'app dark' : 'app'}>
    <div ref={mapContainer} className="map-canvas" />
    <header className="topbar glass">
      <div className="brand"><div className="brand-mark"><MapPinned size={19} /></div><div><strong>يَمَن</strong><span>خريطة مفتوحة Offline</span></div></div>
      <div className="search-shell">
        <button className="search-trigger" onClick={() => setSearchOpen((value) => !value)} aria-label="فتح البحث"><Search size={18} /><span>ابحث عن مدينة، قرية أو شارع</span><kbd>⌘ K</kbd></button>
        {searchOpen && <div className="search-popover glass">
          <div className="search-input"><Search size={17} /><input autoFocus value={query} onChange={(event) => setQuery(event.target.value)} placeholder="اكتب اسم المكان بالعربية…" /></div>
          <div className="result-list">{searchResults.map((item) => <button key={item.id} onClick={() => flyTo(item)}><MapPinned size={16} /><span><b>{item.name}</b><small>{item.type}</small></span><span className="result-arrow">↗</span></button>)}{!searchResults.length && <p className="empty">لا توجد نتائج في الفهرس المحلي.</p>}</div>
          <div className="search-foot"><WifiOff size={13} /> البحث محلي ولا يحتاج إلى خدمة خارجية</div>
        </div>}
      </div>
      <div className="top-actions"><button onClick={() => setDarkMode((value) => !value)} title="الوضع الليلي">{darkMode ? <Sun size={18} /> : <Moon size={18} />}</button><button onClick={fullscreen} title="ملء الشاشة"><Maximize size={18} /></button></div>
    </header>
    <aside className="status-card glass"><span className="status-dot" />{packAvailable ? 'حزمة اليمن المحلية متاحة' : locationStatus}</aside>
    <div className="map-tools glass">
      <button onClick={reset} title="عرض اليمن"><RotateCcw size={18} /></button><button onClick={locate} title="تحديد الموقع"><LocateFixed size={18} /></button><button onClick={() => setTerrainEnabled((value) => !value)} className={terrainEnabled && demAvailable ? 'active' : ''} title={demAvailable ? 'التضاريس' : 'التضاريس — تحتاج حزمة DEM محلية'}><Mountain size={18} /></button><button onClick={() => setLayersOpen((value) => !value)} className={layersOpen ? 'active' : ''} title="الطبقات"><Layers3 size={18} /></button>
    </div>
    {layersOpen && <section className="layer-panel glass"><div className="panel-title"><span>طبقات الخريطة</span><button onClick={() => setLayersOpen(false)}><X size={16} /></button></div><label><input type="checkbox" checked readOnly /><MapIcon size={16} /> خريطة الأساس</label><label><input type="checkbox" checked={terrainEnabled} onChange={(event) => setTerrainEnabled(event.target.checked)} /><Mountain size={16} /> التضاريس و Hillshade</label><label><input type="checkbox" checked readOnly /><Route size={16} /> الطرق والمسارات</label><label><input type="checkbox" checked readOnly /><Building2 size={16} /> المباني المتوفرة</label><div className="panel-note"><Info size={14} /> تُعرض التفاصيل وفق مستوى التكبير وتوفرها في بيانات OSM.</div></section>}
    {selected && <section className="feature-card glass"><button className="close-feature" onClick={() => setSelected(null)}><X size={15} /></button><span className="feature-kicker">معلومة من الخريطة</span><h2>{selected.name}</h2><p>{selected.type}</p>{selected.lat && selected.lng && <code>{selected.lat.toFixed(5)}°، {selected.lng.toFixed(5)}°</code>}</section>}
    <footer className="map-footer"><span>© OpenStreetMap contributors · بيانات مفتوحة مرخصة ODbL</span><span className="data-badge"><WifiOff size={13} /> Local-first</span></footer>
  </main>;
}

createRoot(document.getElementById('root')!).render(<React.StrictMode><App /></React.StrictMode>);

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => navigator.serviceWorker.register('./sw.js').catch(() => undefined));
}
