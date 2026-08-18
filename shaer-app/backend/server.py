from __future__ import annotations
import json, os, time
from pathlib import Path
from flask import Flask, jsonify, request
import requests

ROOT = Path(__file__).resolve().parents[1]
app = Flask(__name__)

PROVIDERS = {
    'builtin': {'base': os.getenv('OPENAI_API_BASE',''), 'key': os.getenv('OPENAI_API_KEY','')},
    'openrouter': {'base': os.getenv('OPENROUTER_BASE_URL','https://openrouter.ai/api/v1'), 'key': os.getenv('OPENROUTER_API_KEY','')},
    'huggingface': {'base': os.getenv('HF_BASE_URL','https://router.huggingface.co/v1'), 'key': os.getenv('HF_API_KEY','')},
}

def mask(value: str) -> str:
    return ('•'*8 + value[-4:]) if value else 'غير مهيأ'

def provider_status():
    return [{'id': k, 'configured': bool(v['key'] and v['base']), 'base': v['base'], 'keyHint': mask(v['key'])} for k,v in PROVIDERS.items()]

@app.get('/api/health')
def health():
    return jsonify({'ok': True, 'service': 'shaer-backend', 'time': int(time.time()), 'providers': provider_status()})

@app.get('/api/models')
def models():
    results=[]
    for pid,p in PROVIDERS.items():
        if not p['key'] or not p['base']:
            continue
        try:
            r=requests.get(p['base'].rstrip('/')+'/models', headers={'Authorization':'Bearer '+p['key']}, timeout=8)
            if r.ok:
                data=r.json().get('data',[])
                for item in data:
                    results.append({'id':item.get('id'), 'provider':pid, 'free': ':free' in item.get('id','').lower() or item.get('pricing',{}).get('prompt') in ('0','0.0'), 'source':'live'})
        except requests.RequestException:
            continue
    return jsonify({'models':results, 'providers':provider_status()})

@app.post('/api/generate')
def generate():
    body=request.get_json(silent=True) or {}
    provider=body.get('provider','builtin')
    model=body.get('model')
    messages=body.get('messages') or []
    p=PROVIDERS.get(provider)
    if not p or not p['key']:
        return jsonify({'ok':False,'fallback':True,'error':'المزود غير مهيأ؛ استخدم fallback المحلي'}), 200
    payload={'model':model,'messages':messages,'temperature':body.get('temperature',0.85),'max_tokens':body.get('max_tokens',1200)}
    try:
        r=requests.post(p['base'].rstrip('/')+'/chat/completions', headers={'Authorization':'Bearer '+p['key'],'Content-Type':'application/json'}, json=payload, timeout=45)
        return (r.text, r.status_code, {'Content-Type':'application/json'})
    except requests.RequestException as exc:
        return jsonify({'ok':False,'fallback':True,'error':str(exc)}), 200

@app.post('/api/prosody/evaluate')
def evaluate():
    from importlib.util import spec_from_file_location, module_from_spec
    # Server-side lightweight gate; the browser engine remains the detailed UI path.
    text=(request.get_json(silent=True) or {}).get('text','')
    lines=[x.strip() for x in text.splitlines() if x.strip()]
    endings=[x.split()[-1][-2:] for x in lines if x.split()]
    dominant=max(set(endings), key=endings.count) if endings else ''
    return jsonify({'ok':True,'lines':len(lines),'dominantRhyme':dominant,'rhymeScore':round((endings.count(dominant)/len(endings))*100) if endings else 0,'note':'يجب اعتماد التقطيع الصوتي العربي المتقدم في مرحلة لاحقة للتدقيق النهائي'})

@app.get('/api/knowledge')
def knowledge():
    path=ROOT/'data'/'knowledge_base_genius.json'
    data=json.loads(path.read_text(encoding='utf-8')) if path.exists() else {}
    return jsonify({'ok':True,'knowledge':data,'indexedAt':int(path.stat().st_mtime) if path.exists() else None})

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=int(os.getenv('SHAER_PORT','8787')), debug=False)
