from pathlib import Path
p=Path('/home/ubuntu/safo-app/shaer-app/index.html')
s=p.read_text(encoding='utf-8')
if 'prosody-engine.js' not in s:
    s=s.replace('</head>','<script src="prosody-engine.js" defer></script></head>',1)
panel='''
<section class="card prosody-lab" id="prosodyLab" style="margin:28px 0;padding:24px;background:linear-gradient(135deg,#17162b,#242044);color:#fff;border-radius:24px;box-shadow:0 16px 40px rgba(25,18,60,.18)">
  <div style="display:flex;justify-content:space-between;gap:18px;align-items:flex-start;flex-wrap:wrap">
    <div><span style="color:#b7a6ff;font-size:12px;letter-spacing:.12em">المحرك الحتمي</span><h2 style="margin:6px 0 8px">مختبر التقطيع والوزن</h2><p style="margin:0;color:#c9c4dd">يطبّع النص، يقدّر المقاطع الصوتية، يفحص ثبات الروي، ويقترح بوابة جودة قبل اعتماد أي مسودة.</p></div>
    <span id="engineBadge" style="padding:8px 12px;border:1px solid #6d5ac8;border-radius:999px;color:#dcd5ff">جاهز محليًا</span>
  </div>
  <div style="display:grid;grid-template-columns:1.4fr .8fr;gap:16px;margin-top:18px">
    <textarea id="prosodyText" rows="6" style="width:100%;box-sizing:border-box;background:#0f0e1c;color:#fff;border:1px solid #4a416e;border-radius:14px;padding:14px;line-height:1.9" placeholder="ألصق أبياتك هنا…">يا مكحّلٍ في الرياض ارفق بحالي\nخلّيت قلبي لك ولا عاد لي غالي\nيا زين يا اللي حضورك عيد ليالي</textarea>
    <div style="display:grid;gap:10px;align-content:start"><select id="prosodyMeter" style="padding:12px;border-radius:12px;background:#0f0e1c;color:#fff;border:1px solid #4a416e"><option>المتدارك</option><option>الرجز</option><option>الوافر</option><option>المتقارب</option><option>الطويل</option><option>البسيط</option><option>الكامل</option><option>الرمل</option></select><button id="runProsody" style="padding:12px;border:0;border-radius:12px;background:#9d83ff;color:#151126;font-weight:800;cursor:pointer">حلّل النص حتميًا</button><button id="copyProsody" style="padding:12px;border:1px solid #5e5193;border-radius:12px;background:transparent;color:#fff;cursor:pointer">انسخ التقرير</button></div>
  </div>
  <pre id="prosodyReport" style="white-space:pre-wrap;background:#0d0c17;padding:16px;border-radius:14px;color:#ded8f5;margin:16px 0 0;line-height:1.8">بانتظار التحليل…</pre>
</section>
'''
if 'id="prosodyLab"' not in s:
    marker='</main>'
    if marker in s: s=s.replace(marker,panel+marker,1)
    else: s=s.replace('</body>',panel+'</body>',1)
script='''
<script>
(function(){
  const run=document.getElementById('runProsody'); if(!run || !window.ShaerProsody) return;
  const text=document.getElementById('prosodyText'), meter=document.getElementById('prosodyMeter'), report=document.getElementById('prosodyReport'), copy=document.getElementById('copyProsody');
  let last='';
  function render(){ const r=ShaerProsody.evaluate(text.value,meter.value); last=`تقرير مختبر شاعر\\nالبحر: ${r.meter}\\nالأسطر: ${r.rows.length}\\nالمقاطع المقدّرة: ${r.rows.reduce((a,x)=>a+x.syllables,0)}\\nمتوسط المورا: ${r.rows.length?(r.rows.reduce((a,x)=>a+x.morae,0)/r.rows.length).toFixed(1):0}\\nثبات الوزن: ${r.meterScore}%\\nثبات الروي: ${r.rhymeScore}%\\nالروي الغالب: ${r.dominantRhyme||'—'}\\nالحكم: ${r.verdict}\\n\\n${r.rows.map((x,i)=>`${i+1}. ${x.text} | ${x.syllables} مقطع | ${x.pattern}`).join('\\n')}\\n\\nملاحظة: ${r.note}`; report.textContent=last; }
  run.addEventListener('click',render); copy.addEventListener('click',()=>{navigator.clipboard?.writeText(last); if(typeof toast==='function')toast('تم نسخ تقرير العروض');}); render();
})();
</script>
'''
if 'تقرير مختبر شاعر' not in s: s=s.replace('</body>',script+'</body>',1)
p.write_text(s,encoding='utf-8')
