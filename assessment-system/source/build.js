const fs=require('fs');
const js=['engine.js','api.js','identity.js','core.js','views_a.js','views_b.js','views_c.js','views_run.js','pathways.js','voice.js','rewards.js','actions.js'].map(f=>fs.readFileSync(f,'utf8').replace(/^if \(typeof module[^\n]*\n?/m,'')).join('\n');
const css=fs.readFileSync('style.css','utf8');
// The page had no <!DOCTYPE>/<html>/<head> at all before, which left a
// browser guessing at its character encoding whenever the server's own
// Content-Type header didn't pin it down - that's what turned every
// en dash (and any other non-ASCII character) into garbled "â€"”-style
// text. An explicit <meta charset="utf-8"> as the very first thing in
// <head> is what actually fixes it, regardless of server config.
const html=`<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Brainova Intelligent Assessment &amp; Placement System</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<style id="appcss">
${css}
</style>
</head>
<body>
<div id="root"><p style="padding:24px">Loading Brainova…</p></div>
<script>
${js}
</script>
</body>
</html>
`;
// Written straight into the Laravel app's public/ folder, so a normal
// git deploy serves the latest build automatically - no separate
// upload step, and no new Laravel route (public/.htaccess already
// serves any file that exists on disk before it ever reaches index.php).
const outDir = '../../public/assessment-system';
fs.mkdirSync(outDir, { recursive: true });
// A UTF-8 byte-order mark outranks BOTH the server's Content-Type
// header and the <meta charset> tag in every browser's own encoding
// rules - both of those were already correct (verified directly
// against the live server), so this is a third, even more direct
// signal in case something about how this specific server serves the
// file still isn't being read as intended.
fs.writeFileSync(outDir + '/brainova-demo.html', '﻿' + html);
console.log('built ' + outDir + '/brainova-demo.html,', html.length, 'chars');
