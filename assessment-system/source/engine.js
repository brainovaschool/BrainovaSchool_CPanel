/* ===================== BRAINOVA ENGINE (pure logic, no DOM) ===================== */
const DIFF = { S: { name: 'Strengthen', b: -1.1 }, H: { name: 'Hard', b: 0 }, E: { name: 'Extend', b: 1.1 } };
const DIFF_KEYS = ['S', 'H', 'E'];
const ERR = {
  calc: 'Calculation slip', concept: 'Conceptual misunderstanding', misread: 'Misread the question',
  formula: 'Incorrect formula or method', foundation: 'Weak foundation', incomplete: 'Incomplete working'
};
const DEFAULT_LEVELS = [
  { name: 'Foundation', min: -99 }, { name: 'Developing', min: -1.0 }, { name: 'Intermediate', min: -0.35 },
  { name: 'Proficient', min: 0.35 }, { name: 'Advanced', min: 1.0 }
];
const PREREQ = {
  Subtraction: 'Addition', Multiplication: 'Addition', Division: 'Multiplication', Fractions: 'Division',
  Decimals: 'Fractions', Geometry: 'Multiplication', Measurement: 'Multiplication',
  Tenses: 'Parts of Speech', 'Reading Comprehension': 'Parts of Speech', 'Forces & Energy': 'Matter',
  Conditionals: 'Variables & Output', Loops: 'Conditionals', Debugging: 'Variables & Output'
};
const MATH_TOPICS = ['Addition', 'Subtraction', 'Multiplication', 'Division', 'Fractions', 'Decimals', 'Geometry', 'Measurement'];

/* ---------- utilities ---------- */
function hashStr(s) { let h = 2166136261; for (let i = 0; i < s.length; i++) { h ^= s.charCodeAt(i); h = Math.imul(h, 16777619); } return h >>> 0; }
function rng(seed) {
  let a = (typeof seed === 'string' ? hashStr(seed) : seed) >>> 0;
  const f = () => { a |= 0; a = a + 0x6D2B79F5 | 0; let t = Math.imul(a ^ a >>> 15, 1 | a); t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t; return ((t ^ t >>> 14) >>> 0) / 4294967296; };
  return {
    f, int: (lo, hi) => lo + Math.floor(f() * (hi - lo + 1)), pick: arr => arr[Math.floor(f() * arr.length)],
    shuffle(arr) { const r = arr.slice(); for (let i = r.length - 1; i > 0; i--) { const j = Math.floor(f() * (i + 1)); [r[i], r[j]] = [r[j], r[i]]; } return r; },
    norm() { let u = 0, v = 0; while (!u) u = f(); while (!v) v = f(); return Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * v); }
  };
}
const gcd = (a, b) => b ? gcd(b, a % b) : a;
const fracStr = (n, d) => { const g = gcd(n, d); n /= g; d /= g; return d === 1 ? String(n) : n + '/' + d; };
const dec = x => String(+(+x).toFixed(2));
const addNoCarry = (a, b) => { let r = 0, m = 1; while (a > 0 || b > 0) { r += ((a % 10 + b % 10) % 10) * m; a = Math.floor(a / 10); b = Math.floor(b / 10); m *= 10; } return r; };
const absDiffDigits = (a, b) => { let r = 0, m = 1; while (a > 0 || b > 0) { r += Math.abs(a % 10 - b % 10) * m; a = Math.floor(a / 10); b = Math.floor(b / 10); m *= 10; } return r; };
const fmtTime = m => Math.floor(m / 60) + ':' + String(m % 60).padStart(2, '0');

/* ---------- procedural Mathematics generators ---------- */
const GEN = {
  Addition: {
    S: R => { let a, b; do { a = R.int(11, 59); b = R.int(11, 59); } while (a % 10 + b % 10 >= 10 || a + b > 99); const c = a + b;
      return { type: 'mcq', text: `${a} + ${b} = ?`, correct: c, obj: 'Add two-digit numbers without regrouping', time: 25, expl: `${a} + ${b} = ${c}. Add the ones, then the tens.`,
        wrongs: [[c + 10, 'calc', 'Added an extra ten'], [c - 10, 'calc', 'Missed a ten'], [c + 1, 'calc', 'Off by one in the ones place'], [Math.abs(a - b), 'concept', 'Subtracted instead of adding']] }; },
    H: R => { let a, b; do { a = R.int(246, 879); b = R.int(137, 999 - 100); } while (a % 10 + b % 10 < 10 || a + b > 999); const c = a + b;
      return { type: 'num', text: `Find ${a} + ${b}.`, correct: c, obj: 'Add three-digit numbers with regrouping', time: 50, expl: `Regroup (carry) when a column makes ten or more. ${a} + ${b} = ${c}.`,
        wrongs: [[addNoCarry(a, b), 'concept', 'Forgot to regroup (carry)'], [c - 10, 'calc', 'Carried incorrectly'], [c + 100, 'calc', 'Carried into the wrong place'], [c + 10, 'calc', 'Carried twice']] }; },
    E: R => { const X = R.int(300, 700), n = R.int(3, 6), y = R.int(12, 25), z = R.int(40, 120), c = X + n * y - z;
      return { type: 'mcq', text: `A school library has ${X} books. It receives ${n} boxes with ${y} books in each box, and then lends out ${z} books. How many books does the library have now?`, correct: c, obj: 'Solve multi-step addition and subtraction word problems', time: 80,
        expl: `${n} × ${y} = ${n * y} new books. ${X} + ${n * y} − ${z} = ${c}.`,
        wrongs: [[X + y - z, 'concept', 'Used one box instead of all the boxes'], [X + n * y + z, 'misread', 'Added the lent books'], [X + n * y, 'incomplete', 'Forgot to subtract the lent books'], [X - z + n, 'concept', 'Added the number of boxes, not the books']] }; }
  },
  Subtraction: {
    S: R => { let a, b; do { a = R.int(31, 99); b = R.int(11, a - 10); } while (b % 10 > a % 10 || b >= a); const c = a - b;
      return { type: 'num', text: `${a} − ${b} = ?`, correct: c, obj: 'Subtract two-digit numbers without regrouping', time: 25, expl: `${a} − ${b} = ${c}.`,
        wrongs: [[a + b, 'concept', 'Added instead of subtracting'], [c + 10, 'calc', 'Tens slip'], [c - 10, 'calc', 'Tens slip']] }; },
    H: R => { let a, b; do { a = R.int(302, 900); b = R.int(118, a - 100); } while (b % 10 <= a % 10 || b >= a); const c = a - b;
      return { type: 'mcq', text: `Find ${a} − ${b}.`, correct: c, obj: 'Subtract three-digit numbers with regrouping', time: 50, expl: `Regroup (borrow) from the next column when the top digit is smaller. ${a} − ${b} = ${c}.`,
        wrongs: [[absDiffDigits(a, b), 'concept', 'Subtracted the smaller digit from the larger in each column'], [c + 10, 'calc', 'Borrowed but did not reduce the next column'], [c - 10, 'calc', 'Borrowed twice'], [a + b, 'concept', 'Added instead of subtracting']] }; },
    E: R => { const X = R.int(500, 900), A = R.int(60, 200), B = R.int(40, 150), C = R.int(30, 120), c = X - A - B + C;
      return { type: 'mcq', text: `Ali has Rs ${X}. He spends Rs ${A} on a book and Rs ${B} on pens. His sister then gives him Rs ${C}. How much money does Ali have now?`, correct: c, obj: 'Solve multi-step subtraction word problems', time: 80,
        expl: `${X} − ${A} − ${B} + ${C} = ${c}.`,
        wrongs: [[X - A - B - C, 'misread', 'Subtracted the money he received'], [X - A + C, 'incomplete', 'Forgot one of the purchases'], [X - A - B, 'incomplete', 'Forgot the money he received'], [X + A + B + C, 'concept', 'Added every amount']] }; }
  },
  Multiplication: {
    S: R => { const a = R.int(3, 9), b = R.int(3, 9), c = a * b;
      return { type: 'mcq', text: `${a} × ${b} = ?`, correct: c, obj: 'Recall multiplication tables to 9 × 9', time: 20, expl: `${a} × ${b} = ${c}.`,
        wrongs: [[a + b, 'concept', 'Added instead of multiplying'], [a * (b + 1), 'calc', 'Table fact: one group too many'], [a * (b - 1), 'calc', 'Table fact: one group too few'], [c + 2, 'calc', 'Close but incorrect']] }; },
    H: R => { let a, b; do { a = R.int(12, 49); b = R.int(3, 9); } while ((a % 10) * b < 10); const c = a * b;
      const noCarry = Math.floor(a / 10) * b * 10 + (a % 10 * b) % 10;
      return { type: 'num', text: `Find ${a} × ${b}.`, correct: c, obj: 'Multiply a two-digit number by a one-digit number', time: 50, expl: `${a % 10} × ${b} = ${a % 10 * b}; ${Math.floor(a / 10) * 10} × ${b} = ${Math.floor(a / 10) * 10 * b}; total ${c}.`,
        wrongs: [[noCarry, 'calc', 'Forgot to add the carried digit'], [c + 10, 'calc', 'Carry error'], [c - 10, 'calc', 'Carry error'], [a + b, 'concept', 'Added instead of multiplying']] }; },
    E: R => { const a = R.int(21, 49), b = R.int(12, 39), c = a * b, ones = b % 10, tens = Math.floor(b / 10);
      return { type: 'mcq', text: `A box holds ${a} pencils. How many pencils are there in ${b} boxes?`, correct: c, obj: 'Multiply two-digit numbers (place value in partial products)', time: 70,
        expl: `${a} × ${ones} = ${a * ones}; ${a} × ${tens * 10} = ${a * tens * 10}; ${a * ones} + ${a * tens * 10} = ${c}.`,
        wrongs: [[a * ones + a * tens, 'concept', 'Did not shift the second partial product (place value)'], [a * ones + a * tens * 100, 'calc', 'Shifted two places instead of one'], [c - a, 'calc', 'Used one box too few'], [c + 10, 'calc', 'Addition slip']] }; }
  },
  Division: {
    S: R => { const b = R.int(2, 9), q = R.int(2, 9), a = b * q;
      return { type: 'mcq', text: `${a} ÷ ${b} = ?`, correct: q, obj: 'Recall division facts linked to multiplication tables', time: 20, expl: `${b} × ${q} = ${a}, so ${a} ÷ ${b} = ${q}.`,
        wrongs: [[q + 1, 'calc', 'Table fact off by one'], [q - 1, 'calc', 'Table fact off by one'], [a - b, 'concept', 'Subtracted instead of dividing'], [b, 'concept', 'Confused divisor and quotient']] }; },
    H: R => { const b = R.int(2, 4), q = R.int(1, 2) * 100 + R.int(2, 9), a = b * q;
      return { type: 'mcq', text: `Find ${a} ÷ ${b}.`, correct: q, obj: 'Divide a three-digit number by a one-digit number', time: 55, expl: `${b} × ${q} = ${a}. Remember a zero placeholder in the tens place.`,
        wrongs: [[Math.floor(q / 100) * 10 + q % 10, 'concept', 'Left out the zero placeholder in the quotient'], [q + 10, 'calc', 'Place value slip'], [q - 1, 'calc', 'Off by one'], [q + 1, 'calc', 'Off by one']] }; },
    E: R => { let n, k; do { n = R.int(70, 200); k = R.int(6, 9); } while (n % k === 0); const r = n % k, q = Math.floor(n / k);
      return { type: 'mcq', text: `${n} sweets are shared equally among ${k} children. How many sweets are left over?`, correct: r, obj: 'Interpret remainders in division problems', time: 60, expl: `${n} ÷ ${k} = ${q} remainder ${r}.`,
        wrongs: [[q, 'misread', 'Gave how many each child gets, not the remainder'], [k - r, 'concept', 'Found how many more are needed for another sweet each'], [r + 1, 'calc', 'Off by one'], [q + r, 'concept', 'Added quotient and remainder']] }; }
  },
  Fractions: {
    S: R => { const d = R.pick([2, 4, 5]), N = d * R.int(2, 9), c = N / d;
      return { type: 'mcq', text: `What is 1/${d} of ${N}?`, correct: c, obj: 'Find a unit fraction of a whole number', time: 30, expl: `1/${d} of ${N} means ${N} ÷ ${d} = ${c}.`,
        wrongs: [[N * d, 'concept', 'Multiplied instead of dividing'], [N - d, 'concept', 'Subtracted the denominator'], [c + 1, 'calc', 'Off by one'], [d, 'misread', 'Gave the denominator']] }; },
    H: R => { const [b, d] = R.pick([[2, 3], [2, 5], [3, 4], [3, 5], [4, 5]]), a = R.int(1, b - 1), c = R.int(1, d - 1), n = a * d + c * b, dd = b * d;
      const ans = fracStr(n, dd);
      return { type: 'mcq', text: `Find ${a}/${b} + ${c}/${d}. Give the answer in simplest form.`, correct: ans, obj: 'Add fractions with unlike denominators', time: 70, expl: `Common denominator ${dd}: ${a * d}/${dd} + ${c * b}/${dd} = ${n}/${dd} = ${ans}.`,
        wrongs: [[`${a + c}/${b + d}`, 'concept', 'Added the numerators and the denominators'], [`${a + c}/${dd}`, 'concept', 'Used a common denominator but did not convert the numerators'], [`${a * c}/${dd}`, 'formula', 'Multiplied instead of adding'], [fracStr(n + 1, dd), 'calc', 'Arithmetic slip']] }; },
    E: R => { const [p, q] = R.pick([[2, 3], [3, 4], [3, 5], [2, 5], [4, 5]]), W = q * R.int(3, 9), g = p * W / q;
      return { type: 'mcq', text: `${p}/${q} of a number is ${g}. What is the number?`, correct: W, obj: 'Find the whole when a fraction of it is known', time: 75, expl: `${g} ÷ ${p} = ${g / p} is one part, so the whole is ${g / p} × ${q} = ${W}.`,
        wrongs: [[g * q, 'concept', 'Multiplied by the denominator without dividing by the numerator'], [dec(g * p / q), 'formula', 'Took the fraction of the given number instead of undoing it'], [g / p, 'incomplete', 'Found one part but not the whole'], [g + (q - p), 'concept', 'Added the missing parts']] }; }
  },
  Decimals: {
    S: R => { const ai = R.int(11, 89), bi = R.int(11, 89), s = ai + bi;
      return { type: 'mcq', text: `${dec(ai / 10)} + ${dec(bi / 10)} = ?`, correct: dec(s / 10), obj: 'Add decimals with one decimal place', time: 35, expl: `Line up the decimal points: ${dec(ai / 10)} + ${dec(bi / 10)} = ${dec(s / 10)}.`,
        wrongs: [[dec(s / 100), 'concept', 'Misplaced the decimal point'], [dec(s / 10 + 1), 'calc', 'Carry error'], [dec(Math.abs(ai - bi) / 10), 'concept', 'Subtracted instead of adding'], [dec(s / 10 - 0.1), 'calc', 'Tenths slip']] }; },
    H: R => { const ai = R.int(110, 990), bi = R.int(11, 89) * 10, s = ai + bi;
      return { type: 'num', tol: 0.001, text: `Find ${dec(ai / 100)} + ${dec(bi / 100)}.`, correct: s / 100, obj: 'Add decimals with different numbers of decimal places', time: 55, expl: `Line up the decimal points (write ${dec(bi / 100)} as ${(bi / 100).toFixed(2)}). The sum is ${dec(s / 100)}.`,
        wrongs: [[(ai + bi / 10) / 100, 'concept', 'Lined up the last digits instead of the decimal points'], [s / 10, 'concept', 'Misplaced the decimal point'], [(s + 10) / 100, 'calc', 'Tenths slip'], [(s - 10) / 100, 'calc', 'Tenths slip']] }; },
    E: R => { const k = R.int(6, 16), L = k * 0.25;
      return { type: 'mcq', text: `A ribbon ${dec(L)} m long is cut into pieces that are each 0.25 m long. How many pieces are there?`, correct: k, obj: 'Divide by a decimal in context', time: 80, expl: `${dec(L)} ÷ 0.25 = ${k}. (There are four 0.25 m pieces in every metre.)`,
        wrongs: [[dec(k / 10), 'concept', 'Moved the decimal point the wrong way'], [k * 10, 'concept', 'Moved the decimal point too far'], [dec(L * 0.25), 'formula', 'Multiplied instead of dividing'], [Math.floor(L), 'misread', 'Counted whole metres only']] }; }
  },
  Geometry: {
    S: R => { const l = R.int(5, 15), w = R.int(3, l - 1), c = 2 * (l + w);
      return { type: 'mcq', text: `A rectangle is ${l} cm long and ${w} cm wide. What is its perimeter in cm?`, correct: c, obj: 'Calculate the perimeter of a rectangle', time: 40, expl: `Perimeter = 2 × (${l} + ${w}) = ${c} cm.`,
        wrongs: [[l * w, 'formula', 'Calculated area instead of perimeter'], [l + w, 'incomplete', 'Added only one length and one width'], [2 * l + w, 'incomplete', 'Counted one width only'], [l * w * 2, 'formula', 'Doubled the area']] }; },
    H: R => { const b = 2 * R.int(3, 9), h = R.int(4, 12), c = b * h / 2;
      return { type: 'mcq', text: `The base of a triangle is ${b} cm and its height is ${h} cm. What is its area in cm²?`, correct: c, obj: 'Calculate the area of a triangle', time: 50, expl: `Area = ½ × base × height = ½ × ${b} × ${h} = ${c} cm².`,
        wrongs: [[b * h, 'formula', 'Forgot to halve'], [b + h, 'concept', 'Added base and height'], [b * h / 4, 'formula', 'Halved twice'], [(b + h) * 2, 'formula', 'Used the perimeter idea']] }; },
    E: R => { if (R.f() < 0.5) { const s = R.int(4, 12);
        return { type: 'mcq', text: `A square has an area of ${s * s} cm². What is its perimeter in cm?`, correct: 4 * s, obj: 'Solve multi-step area and perimeter problems', time: 70, expl: `Side = √${s * s} = ${s} cm, so perimeter = 4 × ${s} = ${4 * s} cm.`,
          wrongs: [[s * s / 4, 'formula', 'Divided the area by 4'], [s, 'incomplete', 'Found the side only'], [2 * s, 'incomplete', 'Found half the perimeter'], [s * s, 'concept', 'Gave the area']] }; }
      const l = R.int(10, 16), w = R.int(7, 9), c = R.int(2, 4);
      return { type: 'mcq', text: `A rectangle ${l} cm by ${w} cm has a square corner of side ${c} cm cut out. What is the area of the remaining shape in cm²?`, correct: l * w - c * c, obj: 'Solve multi-step area and perimeter problems', time: 80, expl: `${l} × ${w} = ${l * w}; the corner is ${c} × ${c} = ${c * c}; remaining area ${l * w - c * c} cm².`,
        wrongs: [[l * w, 'incomplete', 'Forgot to subtract the corner'], [l * w - c * 4, 'formula', 'Subtracted the corner’s perimeter'], [l * w - c, 'formula', 'Subtracted the side length instead of its area'], [l * w + c * c, 'misread', 'Added the corner']] }; }
  },
  Measurement: {
    S: R => { const k = R.int(2, 9);
      return { type: 'mcq', text: `${k} metres = ? centimetres`, correct: k * 100, obj: 'Convert metres to centimetres', time: 20, expl: `1 m = 100 cm, so ${k} m = ${k * 100} cm.`,
        wrongs: [[k * 10, 'concept', 'Used 10 cm in a metre'], [k * 1000, 'concept', 'Used 1000 cm in a metre'], [dec(k / 100), 'concept', 'Divided instead of multiplying'], [k * 100 + 10, 'calc', 'Slip']] }; },
    H: R => { const kg = R.int(1, 6), g = R.int(1, 9) * 50, c = kg * 1000 + g;
      return { type: 'num', text: `${kg} kg ${g} g = ? g`, correct: c, obj: 'Convert mixed units of mass', time: 45, expl: `${kg} kg = ${kg * 1000} g, plus ${g} g = ${c} g.`,
        wrongs: [[kg * 100 + g, 'concept', 'Used 100 g in a kilogram'], [kg + g, 'concept', 'Added without converting'], [kg * 1000 + g * 10, 'calc', 'Place value slip']] }; },
    E: R => { const m1 = R.pick([15, 30, 45]), h1 = R.int(8, 10), dur = R.int(95, 200), s = h1 * 60 + m1, e = s + dur, h2 = Math.floor(e / 60), m2 = e % 60;
      const t1 = fmtTime(s), t2 = fmtTime(e);
      return { type: 'mcq', text: `A train leaves at ${t1} and arrives at ${t2}. How many minutes does the journey take?`, correct: dur, obj: 'Calculate elapsed time', time: 75, expl: `From ${t1} to ${t2} is ${Math.floor(dur / 60)} h ${dur % 60} min = ${dur} minutes.`,
        wrongs: [[(h2 * 100 + m2) - (h1 * 100 + m1), 'concept', 'Subtracted the times as ordinary numbers'], [dur + 60, 'calc', 'Added an extra hour'], [dur - 60, 'calc', 'Lost an hour'], [Math.floor(dur / 60) * 100 + dur % 60, 'concept', 'Wrote hours and minutes together as one number']] }; }
  }
};
const MATH_GRADES = { Addition: [1, 5], Subtraction: [1, 5], Multiplication: [3, 6], Division: [3, 6], Fractions: [4, 7], Decimals: [4, 7], Geometry: [3, 7], Measurement: [3, 6] };

function mkOptions(R, correct, wrongs) {
  const seen = new Set([String(correct)]), opts = [{ t: String(correct), ok: true }];
  for (const [v, tag, why] of wrongs) {
    const s = String(v);
    if (seen.has(s) || (typeof v === 'number' && (!isFinite(v) || v < 0))) continue;
    seen.add(s); opts.push({ t: s, tag, why }); if (opts.length === 4) break;
  }
  if (typeof correct === 'number') for (let k = 1; opts.length < 4 && k < 40; k++) for (const v of [correct + k, correct - k]) {
    if (opts.length < 4 && v >= 0 && !seen.has(String(v))) { seen.add(String(v)); opts.push({ t: String(v), tag: 'calc', why: 'Close to the answer but not correct' }); }
  }
  return R.shuffle(opts).map((o, i) => { const out = { id: 'abcd'[i], t: o.t }; if (o.tag) { out.tag = o.tag; out.why = o.why; } if (o.ok) out.ok = true; return out; });
}
function bFor(id, diff) { return +(DIFF[diff].b + ((hashStr(id) % 61) - 30) / 100).toFixed(2); }
function cFor(type) { return type === 'mcq' ? 0.22 : type === 'tf' ? 0.45 : 0.02; }

function genMathQ(topic, diff, seed, n) {
  const R = rng(seed), g = GEN[topic][diff](R), id = `MATH-${topic.slice(0, 3).toUpperCase()}-${diff}${String(n).padStart(2, '0')}`;
  const q = { id, subject: 'Mathematics', topic, unit: null, grades: MATH_GRADES[topic].slice(), diff, type: g.type, text: g.text, objective: g.obj, explanation: g.expl,
    marks: 1, time: g.time, prereq: PREREQ[topic] || null, source: 'Brainova generator v1', status: 'approved', b: bFor(id, diff), c: cFor(g.type), marking: 'Full marks for the correct answer.' };
  if (g.type === 'mcq') { q.options = mkOptions(R, g.correct, g.wrongs); q.answer = q.options.find(o => o.ok).id; q.options.forEach(o => delete o.ok); }
  else { q.answer = g.correct; q.tol = g.tol || 0; q.wrong = g.wrongs.map(w => [Number(w[0]), w[1], w[2]]); }
  return q;
}

/* ---------- static question bank (English, Science, Coding, Design) ---------- */
const OBJ = {
  'Parts of Speech': { S: 'Identify nouns and adjectives', H: 'Use adverbs and conjunctions correctly', E: 'Identify words with more than one part of speech' },
  Tenses: { S: 'Use simple past and present', H: 'Use continuous and perfect tenses', E: 'Use past perfect and conditional forms accurately' },
  'Reading Comprehension': { S: 'Locate explicit information', H: 'Infer purpose and verify statements', E: 'Predict outcomes and infer word meaning from context' },
  'Living Things': { S: 'Name plant and animal features', H: 'Explain functions and food chains', E: 'Apply adaptation and ecosystem ideas' },
  Matter: { S: 'Identify states and changes of matter', H: 'Distinguish physical and chemical change', E: 'Choose separation methods and reason about heating' },
  'Forces & Energy': { S: 'Name common forces and light sources', H: 'Explain friction and energy transfer', E: 'Reason about balanced forces and heat transfer' },
  'Variables & Output': { S: 'Use print and assign variables', H: 'Predict output of simple expressions', E: 'Use integer division, modulus and data types' },
  Conditionals: { S: 'Read simple if statements', H: 'Trace if/else and compound conditions', E: 'Trace multiple conditions and write correct ranges' },
  Loops: { S: 'Read for loops with range', H: 'Trace accumulation and while loops', E: 'Trace loops with conditions and doubling' },
  Debugging: { S: 'Recognise common errors', H: 'Locate missing syntax and name errors', E: 'Fix logic errors and operator precedence' },
  'Layout & Colour': { S: 'Explain basic colour and layout choices', H: 'Critique a design using design principles', E: 'Plan a complete layout' }
};
const STATIC_PASSAGE = 'Honeybees live in large groups called colonies. Each colony has one queen, thousands of female workers and a few male drones. Workers collect nectar from flowers and carry it back to the hive, where it is turned into honey. When a worker finds a good patch of flowers, she performs a "waggle dance" to tell the others the direction and distance. Without bees, many fruits and vegetables would not grow, because bees carry pollen from one flower to another.';
// row: [subject, topic, diff, type, text, options|answer, correctIndex|extra, explanation, extra]
const STATIC_ROWS = [
  ['English', 'Parts of Speech', 'S', 'mcq', 'Which word is a noun in this sentence? “The dog barked loudly.”', ['The', 'dog', 'barked', 'loudly'], 1, 'A noun names a person, animal, place or thing. “Dog” is the noun.'],
  ['English', 'Parts of Speech', 'S', 'mcq', 'Choose the adjective in this sentence: “She wore a red scarf.”', ['wore', 'She', 'red', 'scarf'], 2, 'An adjective describes a noun. “Red” describes the scarf.'],
  ['English', 'Parts of Speech', 'H', 'mcq', 'Which sentence uses an adverb correctly?', ['She sings beautiful.', 'She sings beautifully.', 'She beautifully song.', 'She is sing beauty.'], 1, '“Beautifully” is an adverb describing how she sings.'],
  ['English', 'Parts of Speech', 'H', 'mcq', 'Choose the best word to complete the sentence: “I wanted to play outside, ___ it was raining.”', ['because', 'so', 'but', 'and'], 2, '“But” joins two contrasting ideas.'],
  ['English', 'Parts of Speech', 'E', 'mcq', 'In which sentence is the word “light” used as a verb?', ['The light is too bright.', 'Please light the candle.', 'This bag is very light.', 'She chose a light jacket.'], 1, 'In “light the candle” the word is an action, so it is a verb.'],
  ['English', 'Parts of Speech', 'E', 'mcq', 'Which sentence contains a pronoun that correctly replaces the noun phrase “Sara and her brother”?', ['They went to the market.', 'Them went to the market.', 'Him went to the market.', 'She went to the market.'], 0, '“They” is the plural subject pronoun.'],
  ['English', 'Tenses', 'S', 'mcq', 'Choose the correct word: “Yesterday, we ___ to the park.”', ['go', 'went', 'goes', 'going'], 1, '“Yesterday” signals the simple past: went.'],
  ['English', 'Tenses', 'S', 'fill', 'Write the correct past tense of the word in brackets: “Yesterday I ____ (eat) lunch at school.”', ['ate'], 0, 'The past tense of “eat” is “ate”.'],
  ['English', 'Tenses', 'H', 'mcq', 'Which sentence is in the present continuous tense?', ['They are playing football.', 'They played football.', 'They will play football.', 'They play football.'], 0, 'Present continuous = am/is/are + verb-ing.'],
  ['English', 'Tenses', 'H', 'mcq', 'Choose the correct form: “By the time we arrived, the film ___.”', ['had started', 'has started', 'starts', 'is starting'], 0, 'The film started before we arrived, so use the past perfect.'],
  ['English', 'Tenses', 'E', 'mcq', 'Which sentence uses the past perfect correctly?', ['She had finish her homework before dinner.', 'She had finished her homework before dinner.', 'She has finished her homework yesterday.', 'She finished had her homework before dinner.'], 1, 'Past perfect = had + past participle.'],
  ['English', 'Tenses', 'E', 'mcq', 'Which sentence is correct?', ['If I would have known, I would have helped.', 'If I had known, I would have helped.', 'If I knew, I would had helped.', 'If I have known, I will have helped.'], 1, 'The third conditional uses “if + past perfect, would have + past participle”.'],
  ['English', 'Reading Comprehension', 'S', 'mcq', 'What is a group of honeybees called?', ['colony', 'herd', 'flock', 'pack'], 0, 'The first sentence says honeybees live in groups called colonies.', { passage: STATIC_PASSAGE }],
  ['English', 'Reading Comprehension', 'S', 'mcq', 'What do worker bees collect from flowers?', ['pollen only', 'nectar', 'water', 'wax'], 1, 'The passage says workers collect nectar from flowers.', { passage: STATIC_PASSAGE }],
  ['English', 'Reading Comprehension', 'H', 'mcq', 'Why does a worker bee perform the “waggle dance”?', ['To keep the hive warm', 'To scare away enemies', 'To tell other bees where the flowers are', 'To call the queen'], 2, 'The dance tells the others the direction and distance of the flowers.', { passage: STATIC_PASSAGE }],
  ['English', 'Reading Comprehension', 'H', 'mcq', 'Which statement is TRUE according to the passage?', ['Each colony has many queens.', 'Drones are female.', 'Nectar is turned into honey in the hive.', 'Bees do not help plants grow.'], 2, 'The passage says nectar is turned into honey in the hive.', { passage: STATIC_PASSAGE }],
  ['English', 'Reading Comprehension', 'E', 'mcq', 'What would most likely happen to fruit crops if bees disappeared?', ['More fruit would grow.', 'Fewer fruits would grow because pollen would not be carried between flowers.', 'Nothing would change.', 'Fruit would grow faster.'], 1, 'The passage links bees to carrying pollen between flowers.', { passage: STATIC_PASSAGE }],
  ['English', 'Reading Comprehension', 'E', 'mcq', 'Which word is closest in meaning to “patch” in “a good patch of flowers”?', ['area', 'piece of cloth', 'repair', 'scratch'], 0, 'Here “patch” means a small area of ground.', { passage: STATIC_PASSAGE }],

  ['Science', 'Living Things', 'S', 'mcq', 'Which part of a plant absorbs water from the soil?', ['Leaves', 'Roots', 'Flowers', 'Fruit'], 1, 'Roots take in water and minerals from the soil.'],
  ['Science', 'Living Things', 'S', 'mcq', 'Which of these animals is a mammal?', ['Shark', 'Frog', 'Dolphin', 'Eagle'], 2, 'Dolphins breathe air and feed milk to their young, so they are mammals.'],
  ['Science', 'Living Things', 'H', 'mcq', 'What is the main job of chlorophyll in a leaf?', ['To absorb sunlight for making food', 'To take in water', 'To make the leaf fall', 'To attract insects'], 0, 'Chlorophyll absorbs light energy for photosynthesis.'],
  ['Science', 'Living Things', 'H', 'mcq', 'In the food chain grass → grasshopper → frog → snake, which organism is the secondary consumer?', ['Grass', 'Grasshopper', 'Frog', 'Snake'], 2, 'The grasshopper is the primary consumer; the frog eats it, so the frog is the secondary consumer.'],
  ['Science', 'Living Things', 'H', 'order', 'Put the stages of a flowering plant’s life cycle in the correct order.', ['Seed', 'Seedling', 'Adult plant', 'Flowers', 'Fruit with seeds'], null, 'A seed germinates into a seedling, grows into an adult plant, flowers and then forms fruit with new seeds.'],
  ['Science', 'Living Things', 'E', 'mcq', 'Algae in a pond suddenly die. What is the most likely first effect on the pond’s food chain?', ['Animals that eat algae have less food', 'Fish grow faster', 'The pond fills with more water', 'Nothing changes'], 0, 'Algae are producers; losing them reduces food for the animals that eat them.'],
  ['Science', 'Living Things', 'E', 'mcq', 'Why do cacti have thick stems and few leaves?', ['To store water and reduce water loss', 'To catch more sunlight', 'To grow faster', 'To attract bees'], 0, 'Thick stems store water and few leaves reduce loss through evaporation.'],
  ['Science', 'Matter', 'S', 'tf', 'True or false: Water is a liquid at normal room temperature.', true, null, 'Water is liquid between 0 °C and 100 °C.'],
  ['Science', 'Matter', 'S', 'mcq', 'What do we call the change from liquid to gas?', ['Melting', 'Freezing', 'Evaporation', 'Condensation'], 2, 'Liquid turning into gas is evaporation.'],
  ['Science', 'Matter', 'H', 'mcq', 'Which of these is a chemical change?', ['Ice melting', 'Burning a piece of paper', 'Sugar dissolving in water', 'Cutting wood'], 1, 'Burning forms new substances, so it is a chemical change.'],
  ['Science', 'Matter', 'H', 'fill', 'Fill in the blank: When sugar mixes completely into water and seems to disappear, we say it has ____.', ['dissolved'], 0, 'Sugar dissolves in water to make a solution.'],
  ['Science', 'Matter', 'E', 'mcq', 'How can you separate sand and salt from a mixture of sand, salt and water?', ['Filtration followed by evaporation', 'Evaporation only', 'Freezing', 'Using a magnet'], 0, 'Filter out the sand, then evaporate the water to leave the salt.'],
  ['Science', 'Matter', 'E', 'mcq', 'Water is boiling at 100 °C in an open pan. While it keeps boiling, what happens to the temperature of the water?', ['It keeps rising', 'It stays at 100 °C', 'It falls', 'It rises then falls'], 1, 'During boiling, heat energy changes liquid to gas, so the temperature stays constant.'],
  ['Science', 'Forces & Energy', 'S', 'mcq', 'Which force pulls objects towards the Earth?', ['Friction', 'Gravity', 'Magnetism', 'Air resistance'], 1, 'Gravity pulls objects towards the centre of the Earth.'],
  ['Science', 'Forces & Energy', 'S', 'mcq', 'Which of these is a source of light?', ['Moon', 'Mirror', 'Sun', 'Window'], 2, 'The Sun produces its own light. The Moon and a mirror only reflect it.'],
  ['Science', 'Forces & Energy', 'H', 'mcq', 'A ball rolls across grass and slowly stops. Which force mainly slows it down?', ['Gravity', 'Friction', 'Magnetism', 'Buoyancy'], 1, 'Friction between the ball and the grass opposes the motion.'],
  ['Science', 'Forces & Energy', 'H', 'mcq', 'Which energy change takes place when a torch is switched on?', ['Chemical to electrical to light', 'Light to electrical', 'Sound to light', 'Heat to chemical'], 0, 'The battery’s chemical energy becomes electrical energy, then light.'],
  ['Science', 'Forces & Energy', 'E', 'mcq', 'Two students push a box in opposite directions, each with a force of 30 N. What happens to the box?', ['It moves to the left', 'It moves to the right', 'It does not move because the forces are balanced', 'It speeds up'], 2, 'Equal opposite forces are balanced, so there is no change in motion.'],
  ['Science', 'Forces & Energy', 'E', 'short', 'Name one way to reduce friction between two surfaces.', ['oil', 'lubricant', 'grease', 'polish', 'wax', 'powder', 'wheels', 'smooth', 'ball bearings'], 1, 'Lubricants, smoother surfaces and wheels all reduce friction.'],

  ['Computer Science', 'Variables & Output', 'S', 'mcq', 'What does this Python code print?', ['Hi', '"Hi"', 'print Hi', 'An error'], 0, 'print() shows the text without the quotation marks.', { code: 'print("Hi")' }],
  ['Computer Science', 'Variables & Output', 'S', 'mcq', 'Which line correctly stores the number 5 in a variable named score?', ['score = 5', '5 = score', 'let score 5', 'score := 5'], 0, 'In Python you assign with a single equals sign: name = value.'],
  ['Computer Science', 'Variables & Output', 'H', 'mcq', 'What does this code print?', ['7', '34', 'x + y', 'An error'], 0, 'x and y are numbers, so + adds them: 3 + 4 = 7.', { code: 'x = 3\ny = 4\nprint(x + y)' }],
  ['Computer Science', 'Variables & Output', 'H', 'mcq', 'What does this code print?', [['7', 'concept', 'Treated the text values as numbers'], '34', 'x + y', 'An error'], 1, 'x and y are strings, so + joins them: "3" + "4" = "34".', { code: 'x = "3"\ny = "4"\nprint(x + y)' }],
  ['Computer Science', 'Variables & Output', 'E', 'mcq', 'What does this code print?', ['3 1', '3.33 1', '3 3', '10 3'], 0, '10 // 3 is 3 (whole part) and 10 % 3 is 1 (remainder).', { code: 'a = 10\nb = 3\nprint(a // b, a % b)' }],
  ['Computer Science', 'Variables & Output', 'E', 'mcq', 'In Python 3, what is the data type of the result of 7 / 2?', ['int', 'float', 'str', 'bool'], 1, 'The / operator always gives a float, so 7 / 2 is 3.5.'],
  ['Computer Science', 'Conditionals', 'S', 'mcq', 'What does this code print?', ['big', 'small', 'Nothing', 'An error'], 0, '5 > 3 is true, so “big” is printed.', { code: 'x = 5\nif x > 3:\n    print("big")' }],
  ['Computer Science', 'Conditionals', 'S', 'mcq', 'Which symbol checks whether two values are equal?', ['=', '==', '=>', '!'], 1, '== compares values. A single = assigns a value.'],
  ['Computer Science', 'Conditionals', 'H', 'mcq', 'What does this code print?', ['teen', 'child', 'Nothing', 'An error'], 1, '12 >= 13 is false, so the else branch prints “child”.', { code: 'age = 12\nif age >= 13:\n    print("teen")\nelse:\n    print("child")' }],
  ['Computer Science', 'Conditionals', 'H', 'mcq', 'What does this code print?', ['Fizz', 'FizzBuzz', 'Buzz', 'Nothing'], 1, '15 is divisible by both 3 and 5, so the first condition is true.', { code: 'n = 15\nif n % 3 == 0 and n % 5 == 0:\n    print("FizzBuzz")\nelif n % 3 == 0:\n    print("Fizz")' }],
  ['Computer Science', 'Conditionals', 'E', 'mcq', 'What does this code print?', ['A', 'A then B (two lines)', 'A then C (two lines)', 'B'], 1, 'The first if is true, so A prints. The second if is separate: 7 > 6 is true, so B prints.', { code: 'x = 7\nif x > 5:\n    print("A")\nif x > 6:\n    print("B")\nelse:\n    print("C")' }],
  ['Computer Science', 'Conditionals', 'E', 'mcq', 'Which condition is true only when score is from 50 to 100 inclusive?', ['score >= 50 or score <= 100', 'score >= 50 and score <= 100', 'score > 50 and score < 100', 'score >= 50 and score >= 100'], 1, 'Both bounds must hold, so use and with >= 50 and <= 100.'],
  ['Computer Science', 'Loops', 'S', 'mcq', 'What does this code print?', ['0, 1, 2 (each on a new line)', '1, 2, 3', '0, 1, 2, 3', '3'], 0, 'range(3) gives 0, 1, 2.', { code: 'for i in range(3):\n    print(i)' }],
  ['Computer Science', 'Loops', 'S', 'mcq', 'How many times does the body of this loop run?', ['4', '5', '6', '0'], 1, 'range(5) produces five values: 0 to 4.', { code: 'for i in range(5):\n    print("Hello")' }],
  ['Computer Science', 'Loops', 'H', 'mcq', 'What does this code print?', ['3', '6', '10', '4'], 1, 'The loop adds 1, 2 and 3 to total, giving 6.', { code: 'total = 0\nfor i in range(1, 4):\n    total += i\nprint(total)' }],
  ['Computer Science', 'Loops', 'H', 'mcq', 'How many times is “Hi” printed?', ['2', '3', '4', 'It never stops'], 1, 'i takes the values 0, 1 and 2 before i < 3 becomes false.', { code: 'i = 0\nwhile i < 3:\n    print("Hi")\n    i += 1' }],
  ['Computer Science', 'Loops', 'E', 'mcq', 'What does this code print?', ['4', '5', '6', '10'], 1, 'The even numbers from 1 to 10 are 2, 4, 6, 8 and 10: five of them.', { code: 'count = 0\nfor i in range(1, 11):\n    if i % 2 == 0:\n        count += 1\nprint(count)' }],
  ['Computer Science', 'Loops', 'E', 'mcq', 'What does this code print?', ['32', '50', '64', '128'], 2, 'n goes 1, 2, 4, 8, 16, 32, 64. At 64 the condition n < 50 is false.', { code: 'n = 1\nwhile n < 50:\n    n *= 2\nprint(n)' }],
  ['Computer Science', 'Debugging', 'S', 'mcq', 'What is wrong with this line?', ['The closing quotation mark is missing', 'print is spelled wrongly', 'It needs an import', 'Nothing is wrong'], 0, 'The string starts with " but never closes, which causes a SyntaxError.', { code: 'print("Hello)' }],
  ['Computer Science', 'Debugging', 'S', 'mcq', 'What does a SyntaxError mean?', ['The code breaks Python’s grammar rules so it cannot run', 'The code ran but gave a wrong answer', 'The computer is out of memory', 'A variable has the wrong value'], 0, 'A SyntaxError means Python cannot understand the code as written.'],
  ['Computer Science', 'Debugging', 'H', 'mcq', 'What is missing in this code?', ['A colon at the end of the for line', 'Brackets around i', 'The word then', 'A semicolon'], 0, 'A for statement must end with a colon.', { code: 'for i in range(3)\n    print(i)' }],
  ['Computer Science', 'Debugging', 'H', 'mcq', 'What happens when this code runs?', ['NameError', 'SyntaxError', 'ZeroDivisionError', 'It prints 5'], 0, 'Python is case-sensitive: X was never defined, only x.', { code: 'x = 5\nprint(X)' }],
  ['Computer Science', 'Debugging', 'E', 'mcq', 'The program should print the sum of 1 to 5 (15) but prints 10. What change fixes it?', ['Change range(1, 5) to range(1, 6)', 'Change total = 0 to total = 1', 'Change += to =', 'Change print(total) to print(i)'], 0, 'range(1, 5) stops at 4. Use range(1, 6) to include 5.', { code: 'total = 0\nfor i in range(1, 5):\n    total += i\nprint(total)' }],
  ['Computer Science', 'Debugging', 'E', 'mcq', 'avg(4, 8) is meant to return 6. What does it actually return?', ['8.0, because division happens before addition', '6.0', '12.0', '4.0'], 0, 'b / 2 is evaluated first: 4 + 4.0 = 8.0. Use (a + b) / 2.', { code: 'def avg(a, b):\n    return a + b / 2\n\nprint(avg(4, 8))' }],

  ['Graphic Design', 'Layout & Colour', 'S', 'manual', 'In 2–3 sentences, explain how you would choose three colours for a school bake-sale poster.', null, null, 'Look for contrast, audience fit and consistency.', { rubric: ['Colours are readable against each other (contrast)', 'Colours suit the audience and event', 'Uses a limited, consistent palette'], max: 4 }],
  ['Graphic Design', 'Layout & Colour', 'H', 'manual', 'Describe two changes that would improve a poster that has five fonts, small text and no clear heading.', null, null, 'Look for hierarchy, font limits and readability.', { rubric: ['Reduces the number of fonts', 'Creates a clear heading and hierarchy', 'Improves text size or spacing'], max: 4 }]
];

function buildStaticBank() {
  const counters = {}, out = [];
  for (const r of STATIC_ROWS) {
    const [subject, topic, diff, type, text, a, idx, expl, extra] = r, key = topic + diff;
    counters[key] = (counters[key] || 0) + 1;
    const id = `${subject.slice(0, 3).toUpperCase()}-${topic.replace(/[^A-Za-z]/g, '').slice(0, 4).toUpperCase()}-${diff}${String(counters[key]).padStart(2, '0')}`;
    const q = Object.assign({ id, subject, topic, unit: null, grades: subject === 'Computer Science' || subject === 'Graphic Design' ? [6, 10] : [4, 7], diff, type, text,
      objective: OBJ[topic][diff], explanation: expl, marks: type === 'manual' ? 4 : 1, time: type === 'manual' ? 150 : (extra && extra.code ? 60 : 40), prereq: PREREQ[topic] || null,
      source: 'Brainova curriculum team', status: 'approved', b: bFor(id, diff), c: cFor(type), marking: 'Full marks for the correct answer.' }, extra || {});
    if (type === 'mcq') {
      q.options = a.map((o, i) => { const t = Array.isArray(o) ? o[0] : o; const op = { id: 'abcd'[i], t }; if (Array.isArray(o)) { op.tag = o[1]; op.why = o[2]; } return op; });
      q.answer = 'abcd'[idx];
    } else if (type === 'tf') { q.options = [{ id: 't', t: 'True' }, { id: 'f', t: 'False' }]; q.answer = a ? 't' : 'f'; }
    else if (type === 'fill') { q.accept = a; }
    else if (type === 'short') { q.keywords = a; q.need = 1; q.marking = 'Full marks if the answer names at least one valid way (teacher can override).'; }
    else if (type === 'order') { q.items = a; q.answer = a.slice(); }
    else if (type === 'manual') { q.marking = 'Teacher scores against the rubric (0–' + q.max + ').'; }
    out.push(q);
  }
  // matching question
  out.push({ id: 'SCI-LIVI-H90', subject: 'Science', topic: 'Living Things', unit: null, grades: [4, 7], diff: 'H', type: 'match', text: 'Match each plant part to its main job.', pairs: [['Roots', 'Absorb water from the soil'], ['Leaves', 'Make food using sunlight'], ['Stem', 'Support the plant and carry water'], ['Flower', 'Make seeds for reproduction']],
    objective: OBJ['Living Things'].H, explanation: 'Roots absorb water, leaves make food, the stem supports and transports, flowers make seeds.', marks: 1, time: 60, prereq: null, source: 'Brainova curriculum team', status: 'approved', b: bFor('SCI-LIVI-H90', 'H'), c: 0.02, marking: 'Full marks only if every pair is correct.' });
  return out;
}

function buildBank() {
  const bank = [];
  for (const t of MATH_TOPICS) for (const d of DIFF_KEYS) {
    const seen = new Set();
    for (let n = 1, tries = 0; seen.size < 8 && tries < 60; tries++) {
      const q = genMathQ(t, d, `${t}-${d}-${tries}`, n);
      if (seen.has(q.text)) continue;
      seen.add(q.text); bank.push(q); n++;
    }
  }
  return bank.concat(buildStaticBank());
}

/* ---------- syllabus & courses ---------- */
const SYLLABUS = [
  { subject: 'Mathematics', units: [
    { unit: 'Whole Numbers', term: 1, topics: ['Addition', 'Subtraction', 'Multiplication', 'Division'] },
    { unit: 'Fractions and Decimals', term: 2, topics: ['Fractions', 'Decimals'] },
    { unit: 'Shape and Measure', term: 3, topics: ['Geometry', 'Measurement'] }] },
  { subject: 'English', units: [
    { unit: 'Grammar', term: 1, topics: ['Parts of Speech', 'Tenses'] },
    { unit: 'Reading', term: 2, topics: ['Reading Comprehension'] }] },
  { subject: 'Science', units: [
    { unit: 'Life Science', term: 1, topics: ['Living Things'] },
    { unit: 'Physical Science', term: 2, topics: ['Matter', 'Forces & Energy'] }] },
  { subject: 'Computer Science', units: [
    { unit: 'Python Basics', term: 1, topics: ['Variables & Output', 'Conditionals'] },
    { unit: 'Control and Debugging', term: 2, topics: ['Loops', 'Debugging'] }] },
  { subject: 'Graphic Design', units: [{ unit: 'Design Principles', term: 1, topics: ['Layout & Colour'] }] },
  { subject: 'Urdu', units: [{ unit: 'Language Skills', term: 1, topics: ['Reading (Urdu)', 'Grammar (Urdu)'] }] },
  { subject: 'Artificial Intelligence', units: [{ unit: 'Foundations', term: 1, topics: ['AI Concepts'] }] },
  { subject: 'Robotics', units: [{ unit: 'Foundations', term: 1, topics: ['Sensors & Motors'] }] }
];
const COURSES = [
  { id: 'c-addsub', name: 'Addition and Subtraction', subject: 'Mathematics', topics: ['Addition', 'Subtraction'], levels: ['Foundation: Number Basics', 'Intermediate: Regrouping and Multi-digit', 'Advanced: Problem Solving and Mental Maths'] },
  { id: 'c-muldiv', name: 'Multiplication and Division', subject: 'Mathematics', topics: ['Multiplication', 'Division'], levels: ['Foundation: Tables and Facts', 'Intermediate: Multi-digit Methods', 'Advanced: Word Problems and Remainders'] },
  { id: 'c-frac', name: 'Fractions', subject: 'Mathematics', topics: ['Fractions', 'Decimals'], levels: ['Foundation: Parts of a Whole', 'Intermediate: Operations with Fractions', 'Advanced: Fraction Reasoning'] },
  { id: 'c-mathfun', name: 'Mathematics Fundamentals', subject: 'Mathematics', topics: MATH_TOPICS, levels: ['Foundation: Number Sense', 'Intermediate: Operations and Problem Solving', 'Advanced: Reasoning and Extension'] },
  { id: 'c-eng', name: 'English Grammar', subject: 'English', topics: ['Parts of Speech', 'Tenses'], levels: ['Foundation: Sentence Basics', 'Intermediate: Grammar in Use', 'Advanced: Complex Structures'] },
  { id: 'c-read', name: 'Reading and Comprehension', subject: 'English', topics: ['Reading Comprehension'], levels: ['Foundation: Finding Facts', 'Intermediate: Inference', 'Advanced: Analysis'] },
  { id: 'c-urdu', name: 'Urdu Language', subject: 'Urdu', topics: ['Reading (Urdu)', 'Grammar (Urdu)'], levels: ['Foundation', 'Intermediate', 'Advanced'] },
  { id: 'c-sci', name: 'Science Fundamentals', subject: 'Science', topics: ['Living Things', 'Matter', 'Forces & Energy'], levels: ['Foundation: Observing the World', 'Intermediate: Explaining Phenomena', 'Advanced: Investigating'] },
  { id: 'c-code', name: 'Coding and Programming (Python)', subject: 'Computer Science', topics: ['Variables & Output', 'Conditionals', 'Loops', 'Debugging'], levels: ['Foundation: First Programs', 'Intermediate: Logic and Loops', 'Advanced: Problem Solving in Python'] },
  { id: 'c-ai', name: 'Artificial Intelligence', subject: 'Artificial Intelligence', topics: ['AI Concepts'], levels: ['Foundation', 'Intermediate', 'Advanced'] },
  { id: 'c-robo', name: 'Robotics', subject: 'Robotics', topics: ['Sensors & Motors'], levels: ['Foundation', 'Intermediate', 'Advanced'] },
  { id: 'c-canva', name: 'Canva and Graphic Design', subject: 'Graphic Design', topics: ['Layout & Colour'], levels: ['Foundation: Design Basics', 'Intermediate: Layout and Colour', 'Advanced: Brand and Portfolio'] }
];

/* ---------- item response model ---------- */
const A_DISC = 1.2;
const P = (t, b, c) => c + (1 - c) / (1 + Math.exp(-A_DISC * (t - b)));
function eap(resps, mu = 0, sd = 1) {
  let den = 0, num = 0, num2 = 0;
  for (let t = -3; t <= 3.0001; t += 0.1) {
    let lp = -0.5 * ((t - mu) / sd) ** 2;
    for (const r of resps) { const p = P(t, r.b, r.c); lp += Math.log(r.correct ? p : 1 - p); }
    const w = Math.exp(lp); den += w; num += w * t; num2 += w * t * t;
  }
  const m = num / den;
  return { theta: m, sd: Math.sqrt(Math.max(0, num2 / den - m * m)) };
}
function levelIdx(theta, levels) { let k = 0; levels.forEach((l, i) => { if (theta >= l.min) k = i; }); return k; }

/* ---------- answer handling ---------- */
const norm = s => String(s == null ? '' : s).trim().toLowerCase().replace(/\s+/g, ' ').replace(/[.,;!?]+$/, '');
function parseNum(s) { s = String(s).replace(/,/g, '').trim(); if (/^-?\d+\s*\/\s*\d+$/.test(s)) { const [a, b] = s.split('/').map(Number); return a / b; } return /^-?\d*\.?\d+$/.test(s) ? parseFloat(s) : NaN; }
function isBlank(raw) { return raw == null || raw === '' || (Array.isArray(raw) && (!raw.length || raw.every(x => x == null || x === ''))); }
function gradeAnswer(q, raw) {
  if (isBlank(raw)) return { status: 'unanswered', marks: 0 };
  let ok = false;
  switch (q.type) {
    case 'mcq': case 'tf': ok = raw === q.answer; break;
    case 'num': { const v = parseNum(raw); ok = !isNaN(v) && Math.abs(v - q.answer) <= (q.tol || 1e-9); break; }
    case 'fill': ok = q.accept.some(a => norm(a) === norm(raw)); break;
    case 'short': { const s = norm(raw); ok = q.keywords.filter(k => s.includes(norm(k))).length >= (q.need || 1); break; }
    case 'order': ok = Array.isArray(raw) && raw.length === q.items.length && raw.every((x, i) => x === q.items[i]); break;
    case 'match': ok = Array.isArray(raw) && q.pairs.every((p, i) => raw[i] === p[1]); break;
    case 'manual': return { status: 'pending', marks: 0 };
  }
  return { status: ok ? 'correct' : 'incorrect', marks: ok ? q.marks : 0 };
}
function wrongTag(q, raw) {
  if (q.type === 'mcq') { const o = q.options.find(o => o.id === raw); return o && o.tag ? { tag: o.tag, why: o.why } : null; }
  if (q.type === 'num' && q.wrong) { const v = parseNum(raw); const w = q.wrong.find(w => Math.abs(w[0] - v) < 1e-9); return w ? { tag: w[1], why: w[2] } : null; }
  return null;
}
function ansText(q, raw) {
  if (isBlank(raw)) return '';
  if (q.type === 'mcq' || q.type === 'tf') { const o = q.options.find(o => o.id === raw); return o ? o.t : String(raw); }
  if (q.type === 'order') return raw.join(' → ');
  if (q.type === 'match') return q.pairs.map((p, i) => p[0] + ' = ' + (raw[i] || '—')).join('; ');
  return String(raw);
}
function correctText(q) {
  switch (q.type) {
    case 'mcq': case 'tf': return q.options.find(o => o.id === q.answer).t;
    case 'num': return String(q.answer);
    case 'fill': return q.accept[0];
    case 'short': return 'Any valid answer, e.g. ' + q.keywords.slice(0, 3).join(', ');
    case 'order': return q.items.join(' → ');
    case 'match': return q.pairs.map(p => p[0] + ' = ' + p[1]).join('; ');
    default: return 'Marked by teacher against the rubric';
  }
}
function present(q, R, shuffleOpts) {
  const p = JSON.parse(JSON.stringify(q));
  if (shuffleOpts) {
    if (p.type === 'mcq') p.options = R.shuffle(p.options);
    if (p.type === 'order') p.shown = R.shuffle(p.items);
    if (p.type === 'match') p.rights = R.shuffle(p.pairs.map(x => x[1]));
  } else {
    if (p.type === 'order') p.shown = p.items.slice().reverse();
    if (p.type === 'match') p.rights = p.pairs.map(x => x[1]).reverse();
  }
  return p;
}
function makeResponse(seq, q, raw, ms, meta = {}) {
  const g = gradeAnswer(q, raw);
  let status = g.status;
  if (status === 'unanswered' && meta.visited) status = 'skipped';
  const wt = status === 'incorrect' ? wrongTag(q, raw) : null;
  return { seq, qid: q.id, topic: q.topic, subject: q.subject, diff: q.diff, type: q.type, objective: q.objective, b: q.b, c: q.c, status, marks: g.marks, maxMarks: q.marks,
    answerRaw: raw == null ? null : raw, answer: ansText(q, raw), correctAnswer: correctText(q), timeMs: Math.round(ms || 0), estSec: q.time || 45,
    changed: meta.changed || 0, visits: meta.visits || 1, timedOut: !!meta.timedOut, probe: !!meta.probe, tag: wt ? wt.tag : null, why: wt ? wt.why : null };
}

/* ---------- question pools and assembly ---------- */
const topicKey = (s, t) => s + '|' + t;
function gradeOk(q, a) { if (!a.grades || !a.grades.length || !q.grades) return true; return a.grades.some(g => g >= q.grades[0] && g <= q.grades[1]); }
function poolFor(bank, a) {
  const keys = new Set((a.topics || []).map(t => topicKey(t.subject, t.topic))), ex = new Set(a.excludeObj || []);
  return bank.filter(q => q.status === 'approved' && keys.has(topicKey(q.subject, q.topic)) && gradeOk(q, a) && !ex.has(q.subject + '|' + q.topic + '|' + q.objective));
}
function poolCounts(pool, topics) {
  return topics.map(t => { const row = { subject: t.subject, topic: t.topic, S: 0, H: 0, E: 0 }; pool.forEach(q => { if (q.subject === t.subject && q.topic === t.topic) row[q.diff]++; }); return row; });
}
function buildFixed(a, bank, seed) {
  const R = rng(seed), pool = poolFor(bank, a), topics = a.topics.map(t => t.topic), items = [], shortages = [], used = new Set();
  const byTD = {};
  pool.forEach(q => { const k = q.topic + '|' + q.diff; (byTD[k] = byTD[k] || []).push(q); });
  Object.keys(byTD).forEach(k => byTD[k] = R.shuffle(byTD[k]));
  const rot = R.int(0, Math.max(0, topics.length - 1));
  for (const d of DIFF_KEYS) {
    const want = a.dist[d] || 0; let got = 0, i = 0, stall = 0;
    while (got < want && stall < topics.length) {
      const t = topics[(i + rot) % topics.length]; i++;
      const list = (byTD[t + '|' + d] || []).filter(q => !used.has(q.id));
      if (!list.length) { stall++; continue; }
      stall = 0; used.add(list[0].id); items.push(list[0]); got++;
    }
    if (got < want) shortages.push({ diff: d, missing: want - got });
  }
  let out = items;
  if (a.shuffleQ) out = R.shuffle(items);
  else out = items.slice().sort((x, y) => DIFF_KEYS.indexOf(x.diff) - DIFF_KEYS.indexOf(y.diff) || topics.indexOf(x.topic) - topics.indexOf(y.topic));
  return { items: out, shortages };
}

/* ---------- adaptive engine ---------- */
const ADAPT_DEFAULT = { min: 10, max: 20, minPer: 3, sdTarget: 0.7, startB: -0.5, maxStep: 1.2, probe: true };
class Adaptive {
  constructor(a, bank, seed) {
    this.a = a; this.cfg = Object.assign({}, ADAPT_DEFAULT, a.adaptive || {});
    this.R = rng(seed); this.pool = poolFor(bank, a); this.resps = []; this.used = new Set(); this.trace = []; this.probed = new Set();
    this.topics = a.topics.map(t => t.topic).filter(t => this.pool.some(q => q.topic === t));
    this.fullBank = bank;
    const k = Math.max(1, this.topics.length);
    if (this.cfg.minPer * k > this.cfg.max) this.cfg.minPer = Math.max(1, Math.floor(this.cfg.max / k));
    this.cfg.min = Math.min(this.cfg.min, this.cfg.max);
  }
  overall() { return eap(this.resps.filter(r => r.correct !== null), 0, 1); }
  topicEst(t) { const rs = this.resps.filter(r => r.topic === t && r.correct !== null), o = this.overall(); return Object.assign(eap(rs, rs.length ? o.theta : 0, 0.8), { n: rs.length }); }
  countOf(t) { return this.resps.filter(r => r.topic === t).length; }
  done() {
    const n = this.resps.length;
    if (!this.topics.length) return 'No approved questions are available for the selected topics';
    if (n >= this.cfg.max) return `Reached the configured maximum of ${this.cfg.max} questions`;
    const avail = this.topics.some(t => this.pool.some(q => q.topic === t && !this.used.has(q.id)));
    if (!avail) return 'Question pool exhausted';
    if (n < this.cfg.min) return null;
    const ok = this.topics.every(t => this.countOf(t) >= this.cfg.minPer && this.topicEst(t).sd <= this.cfg.sdTarget);
    return ok ? 'Enough evidence collected for every topic' : null;
  }
  next() {
    const cfg = this.cfg, n = this.resps.length, ov = this.overall();
    const avail = t => this.pool.filter(q => q.topic === t && !this.used.has(q.id));
    let cand = this.topics.filter(t => avail(t).length);
    const last2 = this.resps.slice(-2).map(r => r.topic);
    if (last2.length === 2 && last2[0] === last2[1] && cand.length > 1) cand = cand.filter(t => t !== last2[0]);
    // prerequisite probe after repeated difficulty in a topic
    const lastR = this.resps[this.resps.length - 1];
    if (cfg.probe && lastR && lastR.correct === false) {
      const t = lastR.topic, pre = PREREQ[t], est = this.topicEst(t);
      if (pre && !this.probed.has(pre) && est.n >= 2 && est.theta < -0.5) {
        const pl = this.fullBank.filter(q => q.status === 'approved' && q.topic === pre && q.subject === lastR.subject && q.diff !== 'E' && !this.used.has(q.id) && gradeOk(q, this.a));
        if (pl.length) {
          this.probed.add(pre);
          const q = pl.sort((x, y) => Math.abs(x.b - (est.theta - 0.3)) - Math.abs(y.b - (est.theta - 0.3)))[0];
          this.used.add(q.id);
          this.trace.push({ n: n + 1, topic: pre, qid: q.id, diff: q.diff, b: q.b, probe: true, why: `Two or more misses in ${t} (estimate ${est.theta.toFixed(2)}). Checking the prerequisite topic ${pre} to see whether the gap starts there.` });
          return Object.assign({}, q, { _probe: true });
        }
      }
    }
    // topic choice: least evidence first, then most uncertain
    const needMin = cand.filter(t => this.countOf(t) < cfg.minPer);
    const group = needMin.length ? needMin : cand;
    const scored = group.map(t => ({ t, n: this.countOf(t), sd: this.topicEst(t).sd, r: this.R.f() }));
    scored.sort((x, y) => needMin.length ? (x.n - y.n || y.sd - x.sd || x.r - y.r) : (y.sd - x.sd || x.r - y.r));
    const topic = scored[0].t, est = this.topicEst(topic);
    let target = est.n ? est.theta : (n ? Math.max(-1.2, Math.min(0.6, ov.theta - 0.2)) : cfg.startB);
    const lastInTopic = [...this.resps].reverse().find(r => r.topic === topic && !r.probe);
    if (lastInTopic) target = Math.max(lastInTopic.b - cfg.maxStep, Math.min(lastInTopic.b + cfg.maxStep, target));
    const list = avail(topic).sort((x, y) => Math.abs(x.b - target) - Math.abs(y.b - target));
    const q = this.R.pick(list.slice(0, 3));
    this.used.add(q.id);
    const reason = est.n === 0 ? `${topic} has not been assessed yet, so the first question starts near the ${n ? 'student’s current overall estimate' : 'grade baseline'} (target difficulty ${target.toFixed(2)}).`
      : `${topic} has ${est.n} question${est.n > 1 ? 's' : ''} so far with an estimate of ${est.theta.toFixed(2)} (uncertainty ±${est.sd.toFixed(2)}). ${needMin.length ? 'It still needs more evidence.' : 'It is the least certain topic.'} Next difficulty targets ${target.toFixed(2)}, limited to a step of ±${cfg.maxStep} from the previous ${topic} question.`;
    this.trace.push({ n: n + 1, topic, qid: q.id, diff: q.diff, b: q.b, probe: false, why: reason });
    return q;
  }
  record(resp) { this.resps.push({ topic: resp.topic, b: resp.b, c: resp.c, diff: resp.diff, correct: resp.status === 'correct' ? true : resp.status === 'incorrect' ? false : null, probe: resp.probe, subject: resp.subject }); }
}

/* ---------- analysis ---------- */
const sum = (a, f = x => x) => a.reduce((s, x) => s + f(x), 0);
function analyze(att, levels, courseOrNull) {
  const rs = att.responses, total = rs.length;
  const done = r => r.status === 'correct' || r.status === 'incorrect';
  const attempted = rs.filter(r => done(r) || r.status === 'pending');
  const graded = rs.filter(done), correct = rs.filter(r => r.status === 'correct').length, incorrect = rs.filter(r => r.status === 'incorrect').length;
  const skipped = rs.filter(r => r.status === 'skipped').length, unanswered = rs.filter(r => r.status === 'unanswered').length, pending = rs.filter(r => r.status === 'pending').length;
  const marks = sum(rs, r => r.marks), maxMarks = sum(rs, r => r.maxMarks);
  const toR = r => ({ b: r.b, c: r.c, correct: r.status === 'correct' });
  const ev = graded.map(toR), ov = eap(ev, 0, 1);
  const an = { total, attempted: attempted.length, correct, incorrect, skipped, unanswered, pending, marks, maxMarks,
    accuracy: graded.length ? correct / graded.length : null, scorePct: maxMarks ? marks / maxMarks : null, completion: total ? attempted.length / total : 0,
    totalTimeMs: att.durationMs || sum(rs, r => r.timeMs), avgTimeMs: attempted.length ? sum(attempted, r => r.timeMs) / attempted.length : 0, theta: ov.theta, sd: ov.sd, overallIdx: graded.length ? levelIdx(ov.theta, levels) : null };
  an.byDiff = {}; DIFF_KEYS.forEach(d => { const x = rs.filter(r => r.diff === d && !r.probe), g = x.filter(done), c = x.filter(r => r.status === 'correct').length; an.byDiff[d] = { n: x.length, attempted: g.length, correct: c, acc: g.length ? c / g.length : null }; });
  const ok = d => an.byDiff[d].attempted >= 2 && an.byDiff[d].acc >= 0.67;
  an.consistent = ok('E') && ok('H') ? 'Extend' : ok('H') ? 'Hard' : ok('S') ? 'Strengthen' : 'Below Strengthen';
  // topics
  const names = []; rs.forEach(r => { if (!names.includes(r.topic)) names.push(r.topic); });
  an.topics = names.map(t => {
    const x = rs.filter(r => r.topic === t), g = x.filter(done), c = x.filter(r => r.status === 'correct').length;
    const est = eap(g.map(toR), g.length ? ov.theta : 0, 0.8), probeOnly = x.every(r => r.probe);
    const bd = {}; DIFF_KEYS.forEach(d => { const y = x.filter(r => r.diff === d && done(r)); bd[d] = { n: y.length, c: y.filter(r => r.status === 'correct').length }; });
    const objs = {}; g.forEach(r => { const o = objs[r.objective] = objs[r.objective] || { n: 0, c: 0 }; o.n++; if (r.status === 'correct') o.c++; });
    const conf = g.length < 2 ? 'none' : g.length < 4 ? 'limited' : 'adequate';
    const idx = conf === 'none' ? null : levelIdx(est.theta, levels);
    const wrongTags = {}; x.filter(r => r.status === 'incorrect' && r.tag).forEach(r => wrongTags[r.tag] = (wrongTags[r.tag] || 0) + 1);
    const evidence = DIFF_KEYS.filter(d => bd[d].n).map(d => `${bd[d].c}/${bd[d].n} ${DIFF[d].name}`).join(', ');
    return { topic: t, subject: x[0].subject, n: x.length, attempted: g.length, correct: c, incorrect: g.length - c, skipped: x.filter(r => r.status === 'skipped').length, acc: g.length ? c / g.length : null,
      avgTimeMs: g.length ? sum(g, r => r.timeMs) / g.length : 0, theta: est.theta, sd: est.sd, idx, conf, bd, evidence, probe: probeOnly,
      mastered: Object.keys(objs).filter(o => objs[o].c === objs[o].n), improve: Object.keys(objs).filter(o => objs[o].c < objs[o].n), wrongTags };
  });
  // errors
  const wrong = rs.filter(r => r.status === 'incorrect'), tagCounts = {}; wrong.forEach(r => { if (r.tag) tagCounts[r.tag] = (tagCounts[r.tag] || 0) + 1; });
  const fastWrong = wrong.filter(r => r.timeMs > 0 && r.timeMs < Math.min(4000, r.estSec * 1000 * 0.2));
  an.errors = { counts: tagCounts, classified: sum(Object.values(tagCounts)), unclassified: wrong.length - sum(Object.values(tagCounts)), wrong: wrong.length, fastWrong: fastWrong.length,
    guessFlag: fastWrong.length >= 3 && fastWrong.length / Math.max(1, wrong.length) >= 0.5 };
  an.slowAccurate = an.avgTimeMs > 1.5 * 1000 * (sum(attempted, r => r.estSec) / Math.max(1, attempted.length)) && (an.accuracy || 0) >= 0.7;
  an.events = att.events || [];
  an.placement = placement(an, att, levels, courseOrNull);
  return an;
}
function depth(t) { let d = 0; while (PREREQ[t]) { t = PREREQ[t]; d++; } return d; }
function placement(an, att, levels, course) {
  const tp = an.topics.filter(t => !t.probe), assessed = tp.filter(t => t.idx != null), needs = tp.filter(t => t.idx == null);
  const lvl = i => levels[i].name, get = n => an.topics.find(t => t.topic === n);
  const out = { lines: [], ready: [], revise: [], missing: [], sequence: [], extra: [], mixed: false, tier: null, grade: null };
  if (an.overallIdx == null) { out.lines.push('Not enough graded answers to recommend a level yet.'); return out; }
  const idxs = assessed.map(t => t.idx), mn = Math.min(...idxs), mx = Math.max(...idxs);
  out.mixed = mx - mn >= 2;
  const oi = an.overallIdx;
  out.overallLabel = lvl(oi);
  if (att.application === 'course' && course) {
    let tier = oi <= 1 ? 0 : oi === 2 ? 1 : 2;
    if (tier === 2 && mn <= 1) { tier = 1; out.lines.push(`Overall evidence points to an advanced start, but ${assessed.filter(t => t.idx <= 1).map(t => t.topic).join(', ')} is still at ${lvl(mn)} level, so the recommendation is held at the intermediate level with targeted support.`); }
    out.tier = course.levels[tier]; out.tierIdx = tier;
    out.lines.unshift(`Recommended start: ${course.levels[tier]}. Estimated overall level: ${lvl(oi)}.`);
  } else {
    const g = att.grade;
    out.grade = oi <= 1 ? `Below expected level for Grade ${g}: begin with Grade ${Math.max(1, g - 1)} foundations in the weaker topics`
      : oi === 2 ? `At the expected level for Grade ${g}: begin Grade ${g} content` : oi === 3 ? `Secure at Grade ${g}: begin Grade ${g} with extension tasks`
      : `Ahead of Grade ${g}: consider Grade ${g + 1} content or enrichment`;
    out.lines.unshift(`${out.grade}. Estimated overall level: ${lvl(oi)}.`);
  }
  if (out.mixed) out.lines.push('The profile is mixed across topics, so the topic-wise recommendations below matter more than the single overall level.');
  assessed.forEach(t => {
    const pre = PREREQ[t.topic], pt = pre && get(pre);
    if (t.idx >= 3 || (t.idx === 2 && (!pt || pt.idx == null || pt.idx >= 1))) out.ready.push({ topic: t.topic, level: lvl(t.idx), note: t.idx >= 3 ? 'Secure; ready for extension or the next unit' : 'Ready to learn the next steps' });
    if (t.idx <= 1) out.revise.push({ topic: t.topic, level: lvl(t.idx), note: t.improve.length ? 'Practise: ' + t.improve.slice(0, 2).join('; ') : 'Needs more practice at this level' });
    if (t.idx <= 1 && pre) {
      if (pt && pt.idx != null) out.missing.push(pt.idx <= 1 ? `${pre} is also weak (${lvl(pt.idx)}), which may be holding back ${t.topic}.` : `${pre} looks secure (${lvl(pt.idx)}), so the gap in ${t.topic} is probably specific to that topic.`);
      else out.missing.push(`${pre} is a prerequisite for ${t.topic} but was not assessed. Add a short ${pre} diagnostic.`);
    }
  });
  needs.forEach(t => out.extra.push(`${t.topic}: only ${t.attempted} graded answer${t.attempted === 1 ? '' : 's'}. Add a short follow-up assessment before deciding.`));
  assessed.filter(t => t.conf === 'limited').forEach(t => out.extra.push(`${t.topic}: limited evidence (${t.attempted} answers). Treat the level as an estimate.`));
  if (an.pending) out.extra.push(`${an.pending} answer${an.pending > 1 ? 's' : ''} await teacher scoring, so the level may change.`);
  if (an.skipped + an.unanswered >= Math.max(2, an.total * 0.2)) out.extra.push(`${an.skipped + an.unanswered} questions were skipped or not reached. The estimate uses answered questions only.`);
  const need = [...tp].filter(t => t.idx == null || t.idx <= 2).sort((a, b) => depth(a.topic) - depth(b.topic) || (a.idx ?? -1) - (b.idx ?? -1));
  need.slice(0, 5).forEach((t, i) => out.sequence.push(`${i + 1}. ${t.topic}${t.idx == null ? ' (assess further first)' : ' (' + lvl(t.idx) + ')'}${t.improve.length ? ': ' + t.improve[0] : ''}`));
  const strong = assessed.filter(t => t.idx >= 3).sort((a, b) => b.idx - a.idx)[0];
  if (strong) out.sequence.push(`${out.sequence.length + 1}. Extension: ${strong.topic} (${lvl(strong.idx)})`);
  return out;
}

/* ---------- simulation (used for seeded demo data, live class and previews) ---------- */
function makeAttempt(a, student, extra) {
  return Object.assign({ id: 'att_' + Math.random().toString(36).slice(2, 10), asmtId: a.id, studentId: student.id, studentName: student.name, grade: student.grade, application: a.application, courseId: a.courseId || null,
    mode: a.mode, model: a.model, subjectList: [...new Set(a.topics.map(t => t.subject))], title: a.title, started: Date.now(), finished: Date.now(), durationMs: 0, items: [], responses: [], events: [], trace: [],
    supervision: null, review: { notes: '', override: '', finalized: false }, released: false, scores: {} }, extra || {});
}
function simulateAttempt(a, bank, student, ability, seed, when) {
  const R = rng(seed), att = makeAttempt(a, student, { started: when, finished: when });
  const th = t => (ability.byTopic && ability.byTopic[t] != null ? ability.byTopic[t] : 0) + ability.theta;
  const resolve = (q, seq, probe) => {
    const qi = present(q, R, a.shuffleOpts); att.items.push(qi);
    let raw = null, ms = q.time * 1000 * Math.exp(0.35 * R.norm()) * (ability.speed || 1);
    const guess = ability.guessy && R.f() < 0.45;
    if (R.f() < (ability.skipP || 0.02)) return makeResponse(seq, qi, null, ms * 0.4, { visited: true, probe });
    const pc = guess ? cFor(q.type) : P(th(q.topic), q.b, q.c);
    if (guess) ms = Math.min(ms, 1500 + R.f() * 1500);
    const good = R.f() < pc;
    if (q.type === 'mcq' || q.type === 'tf') { const pick = good ? qi.options.find(o => o.id === qi.answer) : R.pick(qi.options.filter(o => o.id !== qi.answer)); raw = pick.id; }
    else if (q.type === 'num') raw = good ? String(q.answer) : String(q.wrong && q.wrong.length && R.f() < 0.7 ? R.pick(q.wrong)[0] : q.answer + R.int(1, 9));
    else if (q.type === 'fill') raw = good ? q.accept[0] : 'not sure';
    else if (q.type === 'short') raw = good ? q.keywords[0] : 'I do not know';
    else if (q.type === 'order') raw = good ? q.items.slice() : R.shuffle(q.items);
    else if (q.type === 'match') raw = q.pairs.map((p, i) => good ? p[1] : q.pairs[(i + 1) % q.pairs.length][1]);
    else if (q.type === 'manual') raw = 'Sample response text for review.';
    return makeResponse(seq, qi, raw, ms, { probe, changed: R.f() < 0.06 ? 1 : 0, visits: 1 });
  };
  if (a.model === 'adaptive') {
    const ad = new Adaptive(a, bank, seed + '-ad');
    while (!ad.done()) { const q = ad.next(), r = resolve(q, att.responses.length + 1, !!q._probe); att.responses.push(r); ad.record(r); }
    att.trace = ad.trace; att.stopReason = ad.done();
  } else {
    const fx = buildFixed(a, bank, seed + '-fx'); fx.items.forEach((q, i) => att.responses.push(resolve(q, i + 1, false)));
  }
  att.durationMs = Math.round(sum(att.responses, r => r.timeMs) + 15000);
  att.finished = when + att.durationMs;
  att.supervision = a.parentSupervision ? { confirmed: true, by: 'Parent/guardian', at: when - 60000 } : null;
  return att;
}

if (typeof module !== 'undefined') module.exports = { DIFF, DIFF_KEYS, DEFAULT_LEVELS, PREREQ, MATH_TOPICS, buildBank, SYLLABUS, COURSES, rng, eap, levelIdx, gradeAnswer, poolFor, poolCounts, buildFixed, Adaptive, analyze, simulateAttempt, makeAttempt, makeResponse, present, ansText, correctText, P, hashStr, ERR, ADAPT_DEFAULT, wrongTag, parseNum, norm, cFor, bFor };
