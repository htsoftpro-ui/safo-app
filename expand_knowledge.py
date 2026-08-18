from pathlib import Path
import json, hashlib, datetime

ROOT = Path('/home/ubuntu/safo-app/shaer-app/data')
ROOT.mkdir(parents=True, exist_ok=True)

meters = [
    ('الطويل','فعولن مفاعيلن فعولن مفاعلن',['فعولن','مفاعيلن','فعولن','مفاعلن'],'ملحمي، فخم، سردي'),
    ('البسيط','مستفعلن فاعلن مستفعلن فاعلن',['مستفعلن','فاعلن','مستفعلن','فاعلن'],'سردي، خطابي، مرن'),
    ('الوافر','مفاعلتن مفاعلتن فعولن',['مفاعلتن','مفاعلتن','فعولن'],'وجداني، غنائي، حماسي'),
    ('الكامل','متفاعلن متفاعلن متفاعلن',['متفاعلن','متفاعلن','متفاعلن'],'إنشادي، قوي، متدفق'),
    ('الهزج','مفاعيلن مفاعيلن',['مفاعيلن','مفاعيلن'],'غنائي، وجداني، قصير'),
    ('الرجز','مستفعلن مستفعلن مستفعلن',['مستفعلن']*3,'شعبي، حماسي، زامل'),
    ('الرمل','فاعلاتن فاعلاتن فاعلاتن',['فاعلاتن']*3,'ناعم، رومانسي، تأملي'),
    ('السريع','مستفعلن مستفعلن فاعلن',['مستفعلن','مستفعلن','فاعلن'],'خفيف، إيقاعي، سريع'),
    ('المنسرح','مستفعلن مفعولات مستفعلن',['مستفعلن','مفعولات','مستفعلن'],'متنوع، حركي'),
    ('الخفيف','فاعلاتن مستفعلن فاعلاتن',['فاعلاتن','مستفعلن','فاعلاتن'],'شفاف، حواري، تأملي'),
    ('المضارع','مفاعيلن فاعلاتن',['مفاعيلن','فاعلاتن'],'وجداني، نادر، غنائي'),
    ('المقتضب','مفعولات مستفعلن',['مفعولات','مستفعلن'],'مكثف، قصير'),
    ('المجتث','مستفعلن فاعلاتن',['مستفعلن','فاعلاتن'],'غنائي، رقيق'),
    ('المتقارب','فعولن فعولن فعولن فعولن',['فعولن']*4,'ملحمي، زامل، حماسي'),
    ('المتدارك','فاعلن فاعلن فاعلن فاعلن',['فاعلن']*4,'حديث، سريع، غنائي'),
    ('المديد','فاعلاتن فاعلن فاعلاتن',['فاعلاتن','فاعلن','فاعلاتن'],'مرن، حكائي'),
]

prosody = {
  'tashkeel_normalization': ['إزالة التطويل','توحيد الهمزات','تطبيع الألف المقصورة','تمييز الوصل والوقف','إسقاط حركات الإعراب غير المنطوقة'],
  'zihaf': [
    {'name':'الخبن','effect':'حذف الثاني الساكن','examples':['فاعلاتن → فعلاتن','مستفعلن → متفعلن']},
    {'name':'الطي','effect':'حذف الرابع الساكن','examples':['مستفعلن → مستعلن']},
    {'name':'الإضمار','effect':'تسكين الثاني المتحرك','examples':['متفاعلن → متْفاعلن']},
    {'name':'الوقص','effect':'حذف الثاني المتحرك','examples':['متفاعلن → مفاعلن']},
    {'name':'العصب','effect':'تسكين الخامس المتحرك','examples':['مفاعلتن → مفاعيلن']},
    {'name':'الكف','effect':'حذف السابع الساكن','examples':['مفاعيلن → مفاعيلُ']},
    {'name':'القبض','effect':'حذف الخامس الساكن','examples':['فعولن → فعولُ','مفاعيلن → مفاعلن']},
    {'name':'العقل','effect':'حذف الخامس المتحرك','examples':['مفاعلتن → مفاعتن']},
  ],
  'illah': [
    {'name':'الحذف','effect':'إسقاط السبب الخفيف من آخر التفعيلة'},
    {'name':'القصر','effect':'حذف ساكن السبب وإسكان ما قبله'},
    {'name':'القطع','effect':'حذف ساكن الوتد وإسكان ما قبله'},
    {'name':'التشعيث','effect':'حذف أحد حرفي الوتد المجموع في فاعلاتن'},
    {'name':'التذييل','effect':'زيادة حرف ساكن في نهاية التفعيلة'},
    {'name':'الترفيل','effect':'زيادة سبب خفيف في نهاية التفعيلة'},
  ],
  'forms': ['تام','مجزوء','مشطور','منهوك','مدوّر','مصرّع','مخبون','مقطوع','مرفّل'],
  'validation_order': ['تطبيع النطق','تقسيم المقاطع','مطابقة التفعيلات','تطبيق الزحافات المسموحة','تطبيق العلل في الضرب','تسجيل موضع الخلل','اقتراح بدائل تحفظ المعنى والروي']
}

rhyme = {
  'components': ['الروي','الوصل','الخروج','الردف','التأسيس','الدخيل'],
  'rules': ['تثبيت الروي قبل المسودة','فحص القافية كتابة ونطقًا','تمييز التنوين والوصل عن الروي','منع القوافي المتجانسة شكلاً المختلفة نطقًا','السماح بتغيير القافية في الجسر فقط إذا طلب المستخدم'],
  'families': [
    {'name':'ـاي','letters':['ا','ي'],'mood':'حنين وامتداد','examples':['هواي','خطاي','مناي']},
    {'name':'ـان','letters':['ا','ن'],'mood':'إيقاع شعبي','examples':['مكان','أمان','حنان']},
    {'name':'ـال','letters':['ا','ل'],'mood':'خليجي وغزلي','examples':['غزال','وصال','دلال']},
    {'name':'ـوم','letters':['و','م'],'mood':'شجن وتأمل','examples':['نجوم','علوم','حلوم']},
    {'name':'ـار','letters':['ا','ر'],'mood':'حماسي وملحمي','examples':['نهار','قرار','ديار']},
  ]
}

phonology = {
  'singability': ['تفضيل الجمل القصيرة في النفس الواحد','وضع المدود على المقاطع المفتوحة','تجنب التقاء السواكن في النبرة العالية','تخفيف الكلمات الثقيلة في اللازمة','ترك وقفات تنفس بين الجمل الطويلة'],
  'stress': ['النبر الطبيعي للكلمة','النبر الإيقاعي للمقام','عدم كسر النبر في الكلمات المحورية','توزيع المقاطع الثقيلة على الضربات القوية'],
  'normalization': ['ألف الوصل','همزة القطع','التنوين عند الوقف','الهاء والتاء المربوطة','المدود في اللهجات'],
  'syllable_classes': ['قصير CV','طويل CVV','مغلق CVC','مغلق طويل CVVC']
}

maqamat = [
  {'name':'بيات','mood':'دفء وحنين','common':'صنعاني، موال، غزلي','rhythms':['6/8','وحدة كبيرة']},
  {'name':'راست','mood':'ثبات ووضوح','common':'وطني، قصصي','rhythms':['4/4','2/4']},
  {'name':'حجاز','mood':'شجن واغتراب','common':'فراق، سفر، روحاني','rhythms':['6/8','4/4']},
  {'name':'صبا','mood':'حزن عميق','common':'شكوى، رثاء','rhythms':['حر','بطيء']},
  {'name':'نهاوند','mood':'رومانسية ودراما','common':'أغنية حديثة','rhythms':['4/4','6/8']},
  {'name':'عجم','mood':'فرح وانتصار','common':'وطني، احتفالي','rhythms':['4/4','2/4']},
  {'name':'كرد','mood':'تأمل وهدوء','common':'بالاد، حنين','rhythms':['4/4','6/8']},
  {'name':'سيكاه','mood':'حميمي وشرقي','common':'موال، طرب','rhythms':['حر','6/8']},
  {'name':'حجازكار','mood':'دراما ووقار','common':'مقدمة، تصعيد','rhythms':['4/4','6/8']},
]

rhythms = [
  {'name':'سامري','meter':'4/4 أو 2/4','feel':'خطوة راقصة خليجية','structure':'ضربة ثابتة مع ترديد اللازمة'},
  {'name':'شيلة','meter':'4/4','feel':'إنشادي حماسي','structure':'جمل قصيرة ولازمة جماعية'},
  {'name':'زامل','meter':'2/4 أو حر مضبوط','feel':'حماسي جماعي','structure':'صدر وعجز وتكرار'},
  {'name':'صنعاني','meter':'6/8 أو مرن','feel':'تراثي حواري','structure':'موال ومذهب وترديد'},
  {'name':'مقسوم','meter':'4/4','feel':'راقص شعبي','structure':'كوبليه ولازمة'},
  {'name':'وحدة كبيرة','meter':'4/4','feel':'طربي واسع','structure':'مقدمة وتصعيد'},
  {'name':'بالاد','meter':'4/4','feel':'حديث وتأملي','structure':'كوبليه وجسر وانفراج'},
]

dialects = {
  'gulf': {'name':'خليجية عامة','regions':['نجد','الرياض','الشرقية','الكويت','البحرين','قطر','الإمارات','عُمان'],'lexicon':['وش','يا بعدي','تمون','علومك','أبي','ترى','خلّك','يا زين'],'style':'جمل سلسة، مدود غنائية، تكرار لطيف، صور الدار والليل والبحر'},
  'saudi': {'name':'سعودية/نجدية','regions':['نجد','الرياض','القصيم','الحجاز'],'lexicon':['وشلون','مير','ترى','يا جعلني','علومك','أبشر','تمون'],'style':'نبريّة واضحة، صور نجد والبر والليل، قافية سهلة'},
  'yemeni': {'name':'يمنية عامة','regions':['صنعاء','تعز','عدن','حضرموت','تهامة'],'lexicon':['يا حبيب','عادك','معي','منك','قد','حقك','وينك'],'style':'إيقاع شعبي، دفء، زامل وشيلة، مفردات الدار والقهوة والطريق'},
  'sanaani': {'name':'صنعانية','regions':['صنعاء القديمة'],'lexicon':['يا ليل','البُن','الدار','عاد','حالي','يا سلام'],'style':'حوارية طربية، صور البن والمدينة والجبال، مدود ناعمة'},
  'nabati': {'name':'نبطية','regions':['الخليج والبادية'],'lexicon':['يا راكب','الطاري','الوجيه','الطنايا','المره','الليل'],'style':'صورة صحراوية، حكمة، قوة اللازمة، إيقاع الشيلة'},
  'msa': {'name':'فصحى غنائية','regions':['عربية عامة'],'lexicon':['يا ليل','الهوى','الحنين','الديار','النجوى'],'style':'وضوح نحوي، صورة مركزة، مرونة لحنية'},
}

songcraft = {
  'structures': [
    {'name':'إذاعي حديث','parts':['مقدمة قصيرة','كوبليه 1','لازمة','كوبليه 2','لازمة كبيرة','جسر','خاتمة']},
    {'name':'خليجي سامري','parts':['مطلع','مذهب','كوبليه','مذهب','كوبليه','مذهب كبير']},
    {'name':'شيلة','parts':['مطلع حماسي','لازمة جماعية','كوبليه قصير','لازمة']},
    {'name':'زامل','parts':['صدر','عجز','تكرار جماعي','تصعيد']},
    {'name':'موال طربي','parts':['موال حر','مذهب','كوبليه','مذهب']},
    {'name':'بالاد','parts':['مقدمة','كوبليه هادئ','لازمة','كوبليه متصاعد','جسر','لازمة نهائية']},
  ],
  'roles': ['مطلع جذاب','لازمة قابلة للتذكر','كوبليه يطور القصة','جسر يغير زاوية الشعور','خاتمة تغلق الصورة'],
  'quality_gates': ['وضوح الفكرة','ثبات الشخصية الصوتية','قابلية الترديد','عدم الحشو','تدرج الطاقة','اتساق اللهجة','اتساق القافية','قابلية الأداء']
}

sources = [
  {'id':'zenodo-12755351','title':'Arabic Poetry Analysis Datasets','license':'CC BY 4.0','status':'embedded','url':'https://doi.org/10.5281/zenodo.12755351','note':'بيانات تحليل عروض وشعر مرخصة لإعادة الاستخدام مع النسبة'},
  {'id':'diwan-index','title':'Diwan classified Arabic poetry dataset','license':'MIT for repository; verify source-text rights','status':'indexed-only','url':'https://github.com/NoorBayan/Diwan','note':'فهرسة المصدر دون إعادة توزيع النصوص حتى التحقق من حقوق النص الأصلي'},
  {'id':'arap​​oems','title':'AraPoems','license':'CC BY-NC 4.0','status':'catalogued-research-only','url':'https://doi.org/10.7910/DVN/PJPWOY','note':'أكثر من مليوني بيت بحسب بطاقة البيانات؛ غير مناسب لإدماج تجاري مباشر بسبب NC'},
  {'id':'habibi','title':'Habibi multi-dialect Arabic song lyrics corpus','license':'Freely available for research; verify dataset terms before redistribution','status':'catalogued-research-only','url':'https://aclanthology.org/2020.lrec-1.165/','note':'مصدر مفيد لتصنيف اللهجات وبنية الأغنية وليس لإعادة نشر كلمات محمية'},
  {'id':'openarabic-e-book-corpus','title':'Arabic E-Book Corpus','license':'Check source license per release','status':'catalogued','url':'https://arxiv.org/abs/2405.12267','note':'مصدر لغوي عام يمكن استخدامه للنمذجة بعد التحقق من الإصدار والترخيص'},
]

knowledge = {
  'schema_version':'2.0',
  'name':'Shaer Genius Arabic Poetry, Songcraft & Music Knowledge Base',
  'generated_at':datetime.datetime.utcnow().replace(microsecond=0).isoformat()+'Z',
  'coverage':{
    'meters':16,'prosody_rules':len(prosody['zihaf'])+len(prosody['illah']),'rhyme_families':len(rhyme['families']),
    'maqamat':len(maqamat),'rhythms':len(rhythms),'dialects':len(dialects),'song_structures':len(songcraft['structures']),
    'quality_gates':len(songcraft['quality_gates']),'source_records':len(sources)
  },
  'meters':[{'id':i+1,'name':n,'pattern':p,'feet':f,'style':s,'forms':['تام','مجزوء','مشطور','منهوك']} for i,(n,p,f,s) in enumerate(meters)],
  'prosody':prosody,'rhyme':rhyme,'phonology':phonology,'maqamat':maqamat,'rhythms':rhythms,'dialects':dialects,'songcraft':songcraft,'sources':sources,
  'retrieval_routes':[
    {'intent':'meter_detection','fields':['meters','prosody','phonology']},
    {'intent':'dialect_conversion','fields':['dialects','rhyme','songcraft']},
    {'intent':'melody_suggestion','fields':['maqamat','rhythms','songcraft','phonology']},
    {'intent':'song_completion','fields':['songcraft','rhyme','dialects']},
    {'intent':'repair','fields':['prosody','rhyme','phonology']},
    {'intent':'creative_generation','fields':['dialects','maqamat','rhythms','songcraft','rhyme']},
  ],
  'policy':'يستخدم شاعر المعرفة المرخصة أو المعرفة المنشأة داخليًا. لا يعيد توزيع دواوين أو كلمات أغنيات محمية، ويحتفظ بفهارس ومراجع ومقتطفات مسموحة فقط.'
}

payload = json.dumps(knowledge, ensure_ascii=False, indent=2)
(ROOT/'knowledge_base_genius.json').write_text(payload+'\n', encoding='utf-8')

index=[]
for section, values in knowledge.items():
    if isinstance(values, (list, dict)):
        text=json.dumps(values, ensure_ascii=False)
        index.append({'id':f'kb-{section}','section':section,'text':text[:12000],'tokens':len(text.split()),'sha256':hashlib.sha256(text.encode()).hexdigest()})
(ROOT/'rag_index.json').write_text(json.dumps({'schema_version':'1.0','documents':index}, ensure_ascii=False, indent=2)+'\n', encoding='utf-8')

(ROOT/'SOURCES_GENIUS.md').write_text('''# مصادر قاعدة شاعر الموسعة\n\nتمت إضافة طبقات معرفة مولّدة ومهيكلة للعروض والقافية والنطق واللهجات والمقامات والإيقاعات وبنية الأغنية.\n\n| المصدر | الترخيص/الحالة | الاستخدام داخل شاعر |\n|---|---|---|\n| [Arabic Poetry Analysis Datasets](https://doi.org/10.5281/zenodo.12755351) | CC BY 4.0 | مضمّن محليًا للتحليل العروضي مع النسبة |\n| [Diwan](https://github.com/NoorBayan/Diwan) | مستودع MIT، حقوق النصوص الأصلية تحتاج تحققًا | فهرس مرجعي فقط |\n| [AraPoems](https://doi.org/10.7910/DVN/PJPWOY) | CC BY-NC 4.0 | فهرس بحثي فقط، لا يضمّن في نشر تجاري |\n| [Habibi](https://aclanthology.org/2020.lrec-1.165/) | متاح للبحث، شروط إعادة التوزيع يجب مراجعتها | مرجع لتصنيف اللهجات وبنية الأغنية |\n\n## ملاحظة\n\nالهدف ليس نسخ «كل الشعر العربي» بلا تمييز، بل بناء معرفة قابلة للتدقيق: قواعد عروض، فهارس، أمثلة مرخصة، ومخططات استرجاع. هذا يجعل شاعر أقوى وأقل عرضة لتسريب نصوص محمية أو خلط اللهجات.\n''', encoding='utf-8')
print('generated', ROOT/'knowledge_base_genius.json', ROOT/'rag_index.json')
print('coverage', knowledge['coverage'])
