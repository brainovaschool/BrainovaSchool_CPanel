const fs=require('fs');
const js=['engine.js','api.js','identity.js','core.js','views_a.js','views_b.js','views_c.js','views_run.js','actions.js'].map(f=>fs.readFileSync(f,'utf8').replace(/^if \(typeof module[^\n]*\n?/m,'')).join('\n');
const css=fs.readFileSync('style.css','utf8');
const html=`<title>Brainova Intelligent Assessment &amp; Placement System</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">
<style id="appcss">
${css}
</style>
<div id="root"><p style="padding:24px">Loading Brainova…</p></div>
<script>
${js}
</script>
`;
// Written straight into the Laravel app's public/ folder, so a normal
// git deploy serves the latest build automatically - no separate
// upload step, and no new Laravel route (public/.htaccess already
// serves any file that exists on disk before it ever reaches index.php).
const outDir = '../../public/assessment-system';
fs.mkdirSync(outDir, { recursive: true });
fs.writeFileSync(outDir + '/brainova-demo.html', html);
console.log('built ' + outDir + '/brainova-demo.html,', html.length, 'chars');
