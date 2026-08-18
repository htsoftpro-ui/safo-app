from pathlib import Path
import gzip
import mapbox_vector_tile

payload = Path('/tmp/yemen-z5.pbf').read_bytes()
if payload[:2] == b'\x1f\x8b':
    payload = gzip.decompress(payload)
parsed = mapbox_vector_tile.decode(payload)
for name, layer in parsed.items():
    print(name, len(layer.get('features', [])), sorted(layer.get('features', [{}])[0].get('properties', {}).keys()) if layer.get('features') else [])
