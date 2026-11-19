const E=require('./engine.js');
const bank=E.buildBank();
console.log('bank size',bank.length);
// validate every question
let bad=0;
for(const q of bank){
  const p=[];
  if(q.type==='mcq'){ if(q.options.length!==4) p.push('opts '+q.options.length); const ids=new Set(q.options.map(o=>o.t)); if(ids.size!==q.options.length)p.push('dup opts'); if(!q.options.find(o=>o.id===q.answer))p.push('no answer'); }
  if(q.type==='num'&&!(isFinite(q.answer)))p.push('num');
  const g=E.gradeAnswer(q, q.type==='mcq'||q.type==='tf'?q.answer:q.type==='num'?String(q.answer):q.type==='fill'?q.accept[0]:q.type==='short'?q.keywords[0]:q.type==='order'?q.items:q.type==='match'?q.pairs.map(x=>x[1]):'x');
  if(!(g.status==='correct'||g.status==='pending'))p.push('self-grade '+g.status);
  if(p.length){bad++;console.log(q.id,p.join(','),q.text)}
}
console.log('bad',bad);
const cnt={};bank.forEach(q=>{const k=q.topic+' '+q.diff;cnt[k]=(cnt[k]||0)+1});console.log(JSON.stringify(cnt));
// sample some generated
for(const t of ['Addition','Fractions','Decimals','Measurement','Geometry'])for(const d of ['S','H','E']){const q=bank.find(x=>x.topic===t&&x.diff===d);console.log(q.id,q.text,'=>',q.type==='mcq'?q.options.find(o=>o.id===q.answer).t+' | '+q.options.map(o=>o.t).join(','):q.answer)}
const S={id:'s1',name:'Test',grade:5};
function asmt(over){return Object.assign({id:'a1',title:'T',application:'school',model:'fixed',mode:'independent',grades:[5],topics:E.MATH_TOPICS.map(t=>({subject:'Mathematics',topic:t})),dist:{S:6,H:10,E:8},shuffleQ:false,shuffleOpts:true,adaptive:{min:10,max:20}},over||{})}
// fixed distribution
const fx=E.buildFixed(asmt(),bank,'x');console.log('fixed',fx.items.length,JSON.stringify(fx.shortages),fx.items.filter(q=>q.diff==='S').length,fx.items.filter(q=>q.diff==='H').length,fx.items.filter(q=>q.diff==='E').length);
// adaptive over true thetas
for(const th of [-1.8,-0.8,0,0.8,1.8]){
  const idx=[],lens=[];let ex=0;
  for(let i=0;i<30;i++){
    const at=E.simulateAttempt(asmt({model:'adaptive',topics:['Addition','Subtraction','Multiplication','Division','Fractions'].map(t=>({subject:'Mathematics',topic:t}))}),bank,S,{theta:th,speed:1,skipP:0},'seed'+th+i,Date.now());
    const an=E.analyze(at,E.DEFAULT_LEVELS,null);idx.push(an.overallIdx);lens.push(at.responses.length);
  }
  const m=idx.reduce((a,b)=>a+b)/idx.length;console.log('adaptive true',th,'mean level idx',m.toFixed(2),'len',(lens.reduce((a,b)=>a+b)/lens.length).toFixed(1),'dist',[0,1,2,3,4].map(k=>idx.filter(x=>x===k).length).join('/'));
}
for(const th of [-1.8,-0.8,0,0.8,1.8]){
  const idx=[];
  for(let i=0;i<30;i++){const at=E.simulateAttempt(asmt(),bank,S,{theta:th,speed:1,skipP:0.02},'f'+th+i,Date.now());idx.push(E.analyze(at,E.DEFAULT_LEVELS,null).overallIdx)}
  console.log('fixed true',th,'dist',[0,1,2,3,4].map(k=>idx.filter(x=>x===k).length).join('/'));
}
// one adaptive report
const at=E.simulateAttempt(asmt({model:'adaptive',topics:['Addition','Subtraction','Multiplication','Division','Fractions'].map(t=>({subject:'Mathematics',topic:t})),application:'course',courseId:'c-mathfun'}),bank,S,{theta:0.2,byTopic:{Division:-1.3,Addition:0.9},speed:1},'demo',Date.now());
at.application='course';
const an=E.analyze(at,E.DEFAULT_LEVELS,E.COURSES.find(c=>c.id==='c-mathfun'));
console.log(at.responses.length,at.stopReason);console.log(an.topics.map(t=>`${t.topic}:${t.idx}/${t.conf}/${t.evidence}`).join('\n'));console.log(JSON.stringify(an.placement,null,1));
console.log(at.trace.slice(0,4).map(t=>t.why).join('\n'));
