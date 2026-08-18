/* Shaer Prosody Engine — deterministic Arabic normalization, syllable approximation, meter/cadence gates. */
(function (global) {
  'use strict';
  const DIAC = /[ًٌٍَُِّْـ]/g;
  const TATWEEL = /ـ/g;
  const ALEF = /[إأآٱ]/g;
  const ARABIC_DIGITS = /[٠-٩]/g;
  const meterPatterns = {
    'الطويل': ['فعولن','مفاعيلن','فعولن','مفاعيلن'],
    'البسيط': ['مستفعلن','فاعلن','مستفعلن','فاعلن'],
    'الوافر': ['مفاعلتن','مفاعلتن','فعولن'],
    'الكامل': ['متفاعلن','متفاعلن','متفاعلن'],
    'الرجز': ['مستفعلن','مستفعلن','مستفعلن'],
    'الرمل': ['فاعلاتن','فاعلاتن','فاعلاتن'],
    'السريع': ['مستفعلن','مستفعلن','فاعلن'],
    'المنسرح': ['مستفعلن','مفعولاتُ','مستفعلن'],
    'الخفيف': ['فاعلاتن','مستفعلن','فاعلاتن'],
    'المضارع': ['مفاعيلن','فاعلاتن'],
    'المقتضب': ['مفعولاتُ','مستفعلن'],
    'المجتث': ['مستفعلن','فاعلاتن'],
    'المتقارب': ['فعولن','فعولن','فعولن','فعولن'],
    'المتدارك': ['فاعلن','فاعلن','فاعلن','فاعلن'],
    'الهزج': ['مفاعيلن','مفاعيلن'],
    'المديد': ['فاعلاتن','فاعلن','فاعلاتن']
  };
  function normalize(text) {
    return String(text || '').normalize('NFKC').replace(TATWEEL,'').replace(DIAC,'').replace(ALEF,'ا').replace(/ى/g,'ي').replace(/ة/g,'ه').replace(/[ؤ]/g,'و').replace(/[ئ]/g,'ي').replace(/[^\u0600-\u06FF\s]/g,' ').replace(/\s+/g,' ').trim();
  }
  function letters(word) { return normalize(word).replace(/[^ء-ي]/g,''); }
  function syllables(word) {
    const w = letters(word); const out=[];
    for(let i=0;i<w.length;i++) {
      const c=w[i], next=w[i+1]||'';
      if('اوي'.includes(c) && out.length) out[out.length-1]+='V';
      else if(c) out.push('C');
      if('اوي'.includes(next) && c && !'اوي'.includes(c)) { out[out.length-1] += 'V'; i++; }
    }
    return out;
  }
  function scan(line) {
    const clean=normalize(line); const words=clean?clean.split(' '):[];
    const syll = words.flatMap(syllables); const cv = syll.join(' ');
    const morae = syll.reduce((n,s)=>n+(s.includes('V')?2:1),0);
    return { text:clean, words:words.length, syllables:syll.length, morae, pattern:cv };
  }
  function rhyme(line) {
    const w=normalize(line).split(' ').filter(Boolean).pop()||'';
    const tail=w.slice(-2) || w; return {word:w, ending:tail, normalized:tail};
  }
  function evaluate(lines, meterName) {
    const rows=String(lines||'').split(/\n+/).map(x=>x.trim()).filter(Boolean).map(scan);
    const target=(meterPatterns[meterName]||meterPatterns['المتدارك']).length;
    const avg=rows.length?rows.reduce((a,r)=>a+r.morae,0)/rows.length:0;
    const spread=rows.length?Math.max(...rows.map(r=>r.morae))-Math.min(...rows.map(r=>r.morae)):0;
    const meterScore=Math.max(0,Math.min(100,Math.round(100-(spread*12)-(Math.abs(target-4)*3))));
    const endings=String(lines||'').split(/\n+/).map(rhyme).filter(x=>x.word);
    const counts={}; endings.forEach(x=>counts[x.ending]=(counts[x.ending]||0)+1);
    const dominant=Object.entries(counts).sort((a,b)=>b[1]-a[1])[0]?.[0]||'';
    const rhymeScore=endings.length?Math.round((endings.filter(x=>x.ending===dominant).length/endings.length)*100):0;
    return {meter:meterName||'المتدارك', rows, meterScore, rhymeScore, dominantRhyme:dominant, verdict: meterScore>=75&&rhymeScore>=65?'مقبول مبدئيًا':'يحتاج إصلاحًا', note:'التقطيع حتمي-تقريبي ويحتاج التشكيل والسماع للتحقق النهائي'};
  }
  global.ShaerProsody={normalize,syllables,scan,rhyme,evaluate,meterPatterns};
})(window);
