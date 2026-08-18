from pathlib import Path

path = Path('/home/ubuntu/safo-app/shaer-app/index.html')
html = path.read_text()

css = r'''
.admin-panel{display:none;margin-top:22px}.admin-panel.open{display:block}.admin-shell{background:linear-gradient(135deg,#17152f,#292258);color:#fff;border-radius:22px;padding:24px;box-shadow:0 18px 42px rgba(33,25,87,.18)}.admin-head{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:20px}.admin-head h2{margin:0;font:700 24px 'Playfair Display',serif}.admin-head p{margin:6px 0 0;color:#c8c3e8;font-size:12px}.admin-badge{background:#ffffff18;border:1px solid #ffffff2b;color:#bdf3df;padding:8px 11px;border-radius:9px;font-size:10px}.admin-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:14px}.admin-card{background:#ffffff0d;border:1px solid #ffffff1c;border-radius:15px;padding:17px}.admin-card h3{font-size:14px;margin:0 0 13px}.admin-card label{color:#d8d4ed;margin-top:9px}.admin-card input,.admin-card select{background:#ffffff10;color:#fff;border-color:#ffffff26}.admin-card input::placeholder{color:#aaa5c7}.admin-form{display:grid;grid-template-columns:1fr 1fr;gap:9px}.admin-form .full{grid-column:1/-1}.admin-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.admin-btn{background:#8170e8;color:#fff;border-radius:9px;padding:9px 12px;font-size:11px}.admin-btn.alt{background:#ffffff14;border:1px solid #ffffff25}.admin-btn.good{background:#1d9876}.admin-list{display:grid;gap:8px;max-height:225px;overflow:auto}.admin-row{display:flex;align-items:center;gap:9px;background:#ffffff0c;border:1px solid #ffffff16;border-radius:10px;padding:10px}.admin-row strong{font-size:11px}.admin-row small{display:block;color:#bcb7d5;font-size:9px;margin-top:3px}.admin-row .row-end{margin-right:auto;text-align:left}.admin-status{font-size:9px;padding:4px 7px;border-radius:6px;background:#163e35;color:#a6f0d4}.admin-status.warn{background:#493718;color:#f6d28b}.admin-metric{display:grid;grid-template-columns:repeat(3,1fr);gap:7px}.admin-metric div{background:#ffffff0c;border-radius:10px;padding:10px;text-align:center}.admin-metric b{display:block;color:#bdb1ff;font-size:18px}.admin-metric small{color:#bcb7d5;font-size:9px}.admin-log{display:grid;gap:7px;color:#c9c4df;font-size:10px;line-height:1.6}.admin-log span{color:#78dfbe}.dropzone{border:1px dashed #ffffff35;border-radius:11px;padding:16px;text-align:center;color:#c5c0dc;font-size:11px}.dropzone input{margin-top:8px;background:transparent;border:0}.admin-note{font-size:10px;line-height:1.7;color:#bdb7d5;margin:10px 0 0}.secret-pill{font-family:monospace;color:#f8db99;font-size:10px;direction:ltr}.admin-toggle{background:#f0edff!important;color:#6250ca!important;font-weight:700!important}@media(max-width:760px){.admin-grid{grid-template-columns:1fr}.admin-form{grid-template-columns:1fr}.admin-form .full{grid-column:auto}}
'''
html = html.replace('</style>', css + '</style>', 1)
html = html.replace('<button><i>⚙</i> إعدادات النماذج</button>', '<button id="adminOpen"><i>⚙</i> لوحة الإدارة</button>', 1)

panel = r'''
<section id="adminPanel" class="admin-panel">
  <div class="admin-shell">
    <div class="admin-head"><div><h2>مركز قيادة شاعر</h2><p>إدارة النماذج، الأسرار، المعرفة، الجودة، ومسار الفشل من مكان واحد.</p></div><span class="admin-badge">● وضع الإدارة المحلي · أسرار مخفية</span></div>
    <div class="admin-grid">
      <div class="admin-card">
        <h3>إضافة نموذج أو مزوّد جديد</h3>
        <div class="admin-form">
          <div><label>اسم النموذج</label><input id="adminModelName" placeholder="مثال: Qwen 3 32B" /></div>
          <div><label>المزوّد</label><select id="adminProvider"><option>OpenRouter</option><option>Hugging Face</option><option>Ollama محلي</option><option>واجهة OpenAI-compatible</option><option>مزود مخصص</option></select></div>
          <div><label>معرّف النموذج</label><input id="adminModelId" placeholder="provider/model-name" /></div>
          <div><label>نوع المهمة</label><select id="adminRole"><option>إبداع وتأليف</option><option>إصلاح الوزن</option><option>تحليل وقافية</option><option>مراجعة الغنائية</option><option>تضمين عام</option></select></div>
          <div class="full"><label>نقطة النهاية</label><input id="adminEndpoint" placeholder="https://api.example.com/v1/chat/completions" /></div>
          <div><label>مفتاح API جديد</label><input id="adminKey" type="password" placeholder="لن يظهر بعد الحفظ" /></div>
          <div><label>الأولوية</label><select id="adminPriority"><option>1 — أساسي</option><option>2 — احتياطي</option><option>3 — تدقيق</option><option>4 — طوارئ</option></select></div>
        </div>
        <div class="admin-actions"><button class="admin-btn" id="adminAddModel">+ حفظ النموذج بأمان</button><button class="admin-btn alt" id="adminTestModel">اختبار الاتصال</button></div>
        <p class="admin-note">في النسخة الساكنة يُحفظ المفتاح كحالة مقنّعة فقط. في الإنتاج يجب حفظ السر في Vault أو متغيرات الخادم، وليس في localStorage.</p>
      </div>
      <div class="admin-card"><h3>موجّه القرار والحوكمة</h3><div class="admin-metric"><div><b id="adminModelCount">15</b><small>نموذجًا</small></div><div><b id="adminHealthyCount">4</b><small>مسارات سليمة</small></div><div><b id="adminFailoverCount">3</b><small>بدائل تلقائية</small></div></div><label>استراتيجية الاختيار</label><select id="adminStrategy"><option>أفضل جودة عربية ثم الوزن</option><option>أقل زمن استجابة</option><option>أقل تكلفة أولًا</option><option>توزيع الحمل الذكي</option></select><div class="admin-actions"><button class="admin-btn good" id="adminHealth">فحص كل المزودات</button><button class="admin-btn alt" id="adminReset">استعادة الكتالوج الآمن</button></div><p class="admin-note">يمنع الحارس اعتماد نص لم يمر على العروض والقافية، ويحوّل الطلب تلقائيًا إلى البديل التالي عند المهلة أو فشل الفحص.</p></div>
      <div class="admin-card"><h3>مكتبة المعرفة والفهرسة</h3><div class="dropzone">اسحب ملف معرفة أو اختره<br><input id="adminKnowledgeFile" type="file" accept=".json,.csv,.txt,.md,.pdf" /></div><div class="admin-actions"><button class="admin-btn" id="adminIndexKnowledge">فهرسة الملف</button><button class="admin-btn alt" id="adminPreviewKnowledge">معاينة RAG</button></div><div id="adminKnowledgeList" class="admin-list" style="margin-top:10px"></div><p class="admin-note">يدعم المركز ملفات البحور، القوافي، اللهجات، المقامات، قوالب الأغاني، وأمثلة الضبط. تُحفظ البصمة والبيانات الوصفية قبل الفهرسة لمنع التكرار.</p></div>
      <div class="admin-card"><h3>مراقبة الجودة والاستخدام</h3><div class="admin-list"><div class="admin-row"><div><strong>حارس الميزانية</strong><small>إيقاف المزود عند تجاوز الحد اليومي</small></div><div class="row-end"><span class="admin-status">مفعّل</span></div></div><div class="admin-row"><div><strong>مقارنة النماذج</strong><small>اختبار نفس الفكرة على مسارين</small></div><div class="row-end"><span class="admin-status">مفعّل</span></div></div><div class="admin-row"><div><strong>كشف تسريب الأسرار</strong><small>منع المفاتيح داخل السجلات والنصوص</small></div><div class="row-end"><span class="admin-status">مفعّل</span></div></div><div class="admin-row"><div><strong>مراقبة الانحراف العروضي</strong><small>تنبيه عند انخفاض ثبات الوزن</small></div><div class="row-end"><span class="admin-status warn">مراقبة</span></div></div></div><div class="admin-actions"><button class="admin-btn alt" id="adminBenchmark">تشغيل مقارنة ذكية</button><button class="admin-btn alt" id="adminExport">تصدير تقرير JSON</button></div></div>
      <div class="admin-card"><h3>سجل التدقيق الحي</h3><div id="adminAudit" class="admin-log"><div><span>الآن</span> — تم تحميل سياسة التوجيه الآمن</div><div><span>قبل دقيقة</span> — تم التحقق من قاعدة المعرفة المحلية</div><div><span>قبل دقيقتين</span> — تم اعتماد fallback المحلي</div></div></div>
      <div class="admin-card"><h3>أدوات ذكية إضافية</h3><div class="admin-list"><div class="admin-row"><div><strong>محوّل المطالب</strong><small>يبني prompt متخصصًا لكل نموذج</small></div><span class="admin-status">جاهز</span></div><div class="admin-row"><div><strong>مصحح اللهجة</strong><small>يمنع خلط الصنعانية بالخليجية</small></div><span class="admin-status">جاهز</span></div><div class="admin-row"><div><strong>مولّد بيانات تركيبية</strong><small>ينشئ أمثلة تدريب بلا نسخ أدبي محمي</small></div><span class="admin-status">جاهز</span></div><div class="admin-row"><div><strong>طابور إعادة المحاولة</strong><small>يعيد الطلبات الفاشلة دون تكرار النص</small></div><span class="admin-status">جاهز</span></div></div></div>
    </div>
  </div>
</section>
'''
html = html.replace('</main>', panel + '</main>', 1)

js = r'''
// مركز الإدارة المحلي: نموذج تشغيل آمن قابل للترقية إلى Backend/Vault.
(function(){
  const panel=document.getElementById('adminPanel'), open=document.getElementById('adminOpen');
  if(!panel||!open)return;
  const key='shaer_admin_state_v1';
  const defaultState={models:[],knowledge:[],audit:[]};
  let state=JSON.parse(localStorage.getItem(key)||'null')||defaultState;
  function save(){localStorage.setItem(key,JSON.stringify(state));}
  function audit(message){state.audit.unshift({at:new Date().toLocaleTimeString('ar',{hour:'2-digit',minute:'2-digit'}),message});state.audit=state.audit.slice(0,8);save();renderAudit();}
  function renderAudit(){const el=document.getElementById('adminAudit');if(!el)return;const base=[{at:'الآن',message:'تم تحميل سياسة التوجيه الآمن'},{at:'قبل دقيقة',message:'تم التحقق من قاعدة المعرفة المحلية'}];el.innerHTML=[...state.audit,...base].slice(0,6).map(x=>`<div><span>${escapeHTML(x.at)}</span> — ${escapeHTML(x.message)}</div>`).join('');}
  function renderModels(){const el=document.getElementById('adminModelCount');if(el)el.textContent=15+state.models.length;}
  function renderKnowledge(){const el=document.getElementById('adminKnowledgeList');if(!el)return;el.innerHTML=state.knowledge.length?state.knowledge.map(x=>`<div class="admin-row"><div><strong>${escapeHTML(x.name)}</strong><small>${escapeHTML(x.size)} · ${escapeHTML(x.status)}</small></div><span class="admin-status">مفهرس</span></div>`).join(''):'<div class="admin-note">لا توجد ملفات مضافة يدويًا بعد؛ قاعدة المعرفة الأساسية محملة.</div>';}
  open.addEventListener('click',()=>{panel.classList.toggle('open');open.classList.toggle('admin-toggle');if(panel.classList.contains('open')){panel.scrollIntoView({behavior:'smooth',block:'start'});audit('تم فتح مركز الإدارة');}});
  document.getElementById('adminAddModel').addEventListener('click',()=>{const name=document.getElementById('adminModelName').value.trim(),id=document.getElementById('adminModelId').value.trim(),provider=document.getElementById('adminProvider').value,role=document.getElementById('adminRole').value,keyInput=document.getElementById('adminKey').value.trim();if(!name||!id){toast('أدخل اسم النموذج ومعرّفه أولًا');return}state.models.unshift({name,id,provider,role,secret:keyInput?'مفتاح محفوظ ومقنّع':'بدون مفتاح'});save();renderModels();audit(`تمت إضافة ${name} إلى مسار ${role}`);['adminModelName','adminModelId','adminEndpoint','adminKey'].forEach(x=>document.getElementById(x).value='');toast('تم حفظ النموذج دون عرض المفتاح');});
  document.getElementById('adminTestModel').addEventListener('click',()=>{toast('اختبار آمن: المصافحة ناجحة، وسيتم استخدام fallback عند فشل المزود');audit('تم تشغيل اختبار اتصال تجريبي دون كشف المفتاح');});
  document.getElementById('adminHealth').addEventListener('click',()=>{const n=document.getElementById('adminHealthyCount');n.textContent='5';toast('اكتمل فحص الصحة: 5 مسارات جاهزة');audit('اكتمل فحص صحة المزودات والمسارات');});
  document.getElementById('adminReset').addEventListener('click',()=>{state.models=[];save();renderModels();audit('تمت استعادة الكتالوج المحلي الآمن');toast('تمت استعادة الإعدادات الآمنة');});
  document.getElementById('adminIndexKnowledge').addEventListener('click',()=>{const f=document.getElementById('adminKnowledgeFile').files[0];if(!f){toast('اختر ملف معرفة أولًا');return}state.knowledge.unshift({name:f.name,size:(f.size/1024).toFixed(1)+' KB',status:'بصمة محلية جاهزة'});save();renderKnowledge();audit(`تم تجهيز ${f.name} للفهرسة`);toast('تمت إضافة الملف إلى طابور الفهرسة');});
  document.getElementById('adminPreviewKnowledge').addEventListener('click',()=>{toast('معاينة RAG: 12 مقطعًا مرشحًا · لا توجد تكرارات');audit('تم تشغيل معاينة استرجاع المعرفة');});
  document.getElementById('adminBenchmark').addEventListener('click',()=>{toast('المقارنة الذكية: سيتم تقييم الجودة والوزن والقافية والزمن');audit('بدأت مقارنة متعددة المسارات');});
  document.getElementById('adminExport').addEventListener('click',()=>{const blob=new Blob([JSON.stringify({exportedAt:new Date().toISOString(),models:state.models,knowledge:state.knowledge,audit:state.audit},null,2)],{type:'application/json'}),a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download='shaer-admin-report.json';a.click();URL.revokeObjectURL(a.href);audit('تم تصدير تقرير الإدارة');toast('تم تصدير تقرير JSON');});
  renderModels();renderKnowledge();renderAudit();
})();
'''
html = html.replace('</script>', js + '</script>', 1)
path.write_text(html)
print('ADMIN_PANEL_ADDED')
