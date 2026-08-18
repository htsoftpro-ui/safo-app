from pathlib import Path
import re

p=Path('/home/ubuntu/safo-app/shaer-app/index.html')
s=p.read_text(encoding='utf-8')
start=s.index('let knowledgeBase=null;')
end=s.index('// مركز الإدارة المحلي:', start)
new=r'''let knowledgeBase=null;let geniusKB=null;
function dialectEntries(kb){return Object.entries(kb?.dialects||{}).map(([id,x])=>({id,name:x.name,detail:[...(x.lexicon||[]),x.style||''].join('،')}))}
function toolEntries(kb){return (kb?.tools||[{name:'محرك العروض',status:'enabled'},{name:'فاحص القافية',status:'enabled'},{name:'فاحص النطق والغنائية',status:'enabled'},{name:'موجّه النماذج',status:'enabled'},{name:'حلقة الإصلاح',status:'enabled'},{name:'مسترجع RAG',status:'enabled'}])}
function applyKnowledgeStats(kb){const c=kb.coverage||{};document.getElementById('meterCount').textContent=c.meters||kb.meters?.length||16;document.getElementById('maqamCount').textContent=(c.maqamat||kb.maqamat?.length||6)+'+';document.getElementById('toolCount').textContent=(c.quality_gates?12:toolEntries(kb).length);document.getElementById('kbStatus').textContent=`قاعدة عبقرية · ${c.meters||16} بحرًا · RAG جاهز`;document.getElementById('toolList').innerHTML=toolEntries(kb).slice(0,8).map(t=>`<div class="tool-item"><span>●</span><b>${escapeHTML(t.name)}</b><small>${t.status==='enabled'?'مفعّل':'جاهز'}</small></div>`).join('')}
async function loadKnowledge(){try{let r=await fetch('data/knowledge_base_genius.json');if(!r.ok)throw new Error('genius unavailable');geniusKB=await r.json();knowledgeBase=geniusKB;applyKnowledgeStats(knowledgeBase);document.getElementById('searchResults')?.setAttribute('data-kb','genius')}catch(e){try{const r=await fetch('data/knowledge_base.json');knowledgeBase=await r.json();applyKnowledgeStats(knowledgeBase);document.getElementById('kbStatus').textContent='قاعدة أساسية محملة'}catch(_){document.getElementById('kbStatus').textContent='وضع احتياطي'}}}
function searchKnowledge(){const q=document.getElementById('kbSearch').value.trim().toLowerCase();if(!q||!knowledgeBase){document.getElementById('kbResults').textContent='اكتب كلمة للبحث داخل البحور والمقامات واللهجات والأدوات.';return}const dialects=dialectEntries(knowledgeBase);const pool=[...(knowledgeBase.meters||[]).map(x=>({type:'بحر',name:x.name,detail:[x.pattern,x.style||''].join(' · ')})),...(knowledgeBase.maqamat||[]).map(x=>({type:'مقام',name:x.name,detail:[x.mood,x.common||x.use||''].join(' · ')})),...(knowledgeBase.rhythms||[]).map(x=>({type:'إيقاع',name:x.name,detail:[x.meter,x.feel||''].join(' · ')})),...dialects.map(x=>({type:'لهجة',name:x.name,detail:x.detail})),...toolEntries(knowledgeBase).map(x=>({type:'أداة',name:x.name,detail:x.status}))];const hits=pool.filter(x=>(x.name+' '+x.detail).toLowerCase().includes(q)).slice(0,8);document.getElementById('kbResults').innerHTML=hits.length?hits.map(x=>`<div><b>${escapeHTML(x.type)}:</b> ${escapeHTML(x.name)} — ${escapeHTML(x.detail)}</div>`).join(''):`لا توجد نتيجة مطابقة. جرّب بحرًا أو زحافًا أو مقامًا أو لهجة.`}
document.getElementById('kbSearchBtn').addEventListener('click',searchKnowledge);document.getElementById('kbSearch').addEventListener('keydown',e=>{if(e.key==='Enter')searchKnowledge()});loadKnowledge();function toast(text){const t=document.getElementById('toast');t.textContent=text;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2300)}

'''
s=s[:start]+new+s[end:]
s=s.replace('16</b><small>بحرًا خليليًا','16+</b><small>بحرًا واشتقاقًا')
s=s.replace('6</b><small>مقامات وإيقاعات','9+</b><small>مقامات وإيقاعات')
s=s.replace('10</b><small>أدوات مفعلة','12</b><small>أدوات مفعلة')
p.write_text(s,encoding='utf-8')
print('integrated genius KB loader')
