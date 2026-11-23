/**
 * Sea College Dive Club — Zero-Dependency Offline ISO/IEC 18004 QR Code Encoder (`/assets/erp/qrcode.js`)
 *
 * Standards-compliant ISO/IEC 18004 QR encoder supporting:
 *   - Versions 1..20 (21x21 up to 97x97 modules) in 8-bit UTF-8 Byte Mode
 *   - Error Correction Levels L (7%), M (15%), Q (25%), H (30%) via GF(256) Reed-Solomon (`0x11D`)
 *   - All 8 mask patterns evaluated against all 4 ISO/IEC 18004 penalty rules (`N1..N4`)
 *   - BCH(15,5) Format Information & BCH(18,6) Version Information (`V7..V20`)
 *   - Crisp SVG renderer (`toSvg`), HTML5 Canvas renderer (`toCanvas`), and pure-JS RFC 2083 binary PNG
 *     Data URL encoder (`toDataUrl`) — 100% offline with zero external network requests or dependencies.
 *
 * Exposes `window.SCQR` / `self.SCQR` and CommonJS `module.exports`.
 */
(function (root, factory) {
  'use strict';
  var api = factory();
  if (typeof window !== 'undefined') { window.SCQR = api; }
  if (typeof self !== 'undefined') { self.SCQR = api; }
  if (typeof module !== 'undefined' && module.exports) { module.exports = api; }
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  /* ------------------------------------------------------------------
   * 1. ISO/IEC 18004 Reed-Solomon Block Table (Versions 1..20)
   *    Format: [ecCodewordsPerBlock, group1Blocks, group1DataCw, group2Blocks, group2DataCw]
   * ------------------------------------------------------------------ */
  var RS_BLOCKS = [
    null,
    { L: [7, 1, 19, 0, 0],    M: [10, 1, 16, 0, 0],   Q: [13, 1, 13, 0, 0],    H: [17, 1, 9, 0, 0] },     // V1
    { L: [10, 1, 34, 0, 0],   M: [16, 1, 28, 0, 0],   Q: [22, 1, 22, 0, 0],    H: [28, 1, 16, 0, 0] },    // V2
    { L: [15, 1, 55, 0, 0],   M: [26, 1, 44, 0, 0],   Q: [18, 2, 17, 0, 0],    H: [22, 2, 13, 0, 0] },    // V3
    { L: [20, 1, 80, 0, 0],   M: [18, 2, 32, 0, 0],   Q: [26, 2, 24, 0, 0],    H: [16, 4, 9, 0, 0] },     // V4
    { L: [26, 1, 108, 0, 0],  M: [24, 2, 43, 0, 0],   Q: [18, 2, 15, 2, 16],   H: [22, 2, 11, 2, 12] },   // V5
    { L: [18, 2, 68, 0, 0],   M: [16, 4, 27, 0, 0],   Q: [24, 4, 19, 0, 0],    H: [28, 4, 15, 0, 0] },    // V6
    { L: [20, 2, 78, 0, 0],   M: [18, 4, 31, 0, 0],   Q: [18, 2, 14, 4, 15],   H: [26, 4, 13, 1, 14] },   // V7
    { L: [24, 2, 97, 0, 0],   M: [22, 2, 38, 2, 39],  Q: [22, 4, 18, 2, 19],   H: [26, 4, 14, 2, 15] },   // V8
    { L: [30, 2, 116, 0, 0],  M: [22, 3, 36, 2, 37],  Q: [20, 4, 16, 4, 17],   H: [24, 4, 12, 4, 13] },   // V9
    { L: [18, 2, 68, 2, 69],  M: [26, 4, 43, 1, 44],  Q: [24, 6, 19, 2, 20],   H: [28, 6, 15, 2, 16] },   // V10
    { L: [20, 4, 81, 0, 0],   M: [30, 1, 50, 4, 51],  Q: [28, 4, 22, 4, 23],   H: [24, 3, 12, 8, 13] },   // V11
    { L: [24, 2, 92, 2, 93],  M: [22, 6, 36, 2, 37],  Q: [26, 4, 20, 6, 21],   H: [28, 7, 14, 4, 15] },   // V12
    { L: [26, 4, 107, 0, 0],  M: [22, 8, 37, 1, 38],  Q: [24, 8, 20, 4, 21],   H: [22, 12, 11, 4, 12] },  // V13
    { L: [30, 3, 115, 1, 116],M: [24, 4, 40, 5, 41],  Q: [20, 11, 16, 5, 17],  H: [24, 11, 12, 5, 13] },  // V14
    { L: [22, 5, 87, 1, 88],  M: [24, 5, 41, 5, 42],  Q: [30, 5, 24, 7, 25],   H: [24, 11, 12, 7, 13] },  // V15
    { L: [24, 5, 98, 1, 99],  M: [28, 7, 45, 3, 46],  Q: [24, 15, 19, 2, 20],  H: [30, 3, 15, 13, 16] },  // V16
    { L: [28, 1, 107, 5, 108],M: [28, 10, 46, 1, 47], Q: [28, 1, 22, 15, 23],  H: [28, 2, 14, 17, 15] },  // V17
    { L: [30, 5, 120, 1, 121],M: [26, 9, 43, 4, 44],  Q: [28, 17, 22, 1, 23],  H: [28, 2, 14, 19, 15] },  // V18
    { L: [28, 3, 113, 4, 114],M: [26, 3, 44, 11, 45], Q: [26, 17, 21, 4, 22],  H: [26, 9, 13, 16, 14] },  // V19
    { L: [28, 3, 107, 5, 108],M: [26, 3, 41, 13, 42], Q: [30, 15, 24, 5, 25],  H: [28, 15, 15, 10, 16] }  // V20
  ];

  var ALIGN_POS = [
    null,
    [],
    [6, 18],
    [6, 22],
    [6, 26],
    [6, 30],
    [6, 34],
    [6, 22, 38],
    [6, 24, 42],
    [6, 26, 46],
    [6, 28, 50],
    [6, 30, 54],
    [6, 32, 58],
    [6, 34, 62],
    [6, 26, 46, 66],
    [6, 26, 48, 70],
    [6, 26, 50, 74],
    [6, 30, 54, 78],
    [6, 30, 56, 82],
    [6, 30, 58, 86],
    [6, 34, 62, 90]
  ];

  // ISO/IEC 18004 Table 25 Format Information EC bits: L=01 (1), M=00 (0), Q=11 (3), H=10 (2)
  var EC_BITS = { L: 1, M: 0, Q: 3, H: 2 };

  /* ------------------------------------------------------------------
   * 2. GF(256) Log/Antilog Tables (`0x11D`) & Reed-Solomon Generator
   * ------------------------------------------------------------------ */
  var GF_EXP = new Uint8Array(512);
  var GF_LOG = new Uint8Array(256);
  (function initGF() {
    var x = 1;
    for (var i = 0; i < 255; i++) {
      GF_EXP[i] = x;
      GF_LOG[x] = i;
      x <<= 1;
      if (x & 0x100) { x ^= 0x11D; }
    }
    for (var j = 255; j < 512; j++) {
      GF_EXP[j] = GF_EXP[j - 255];
    }
  })();

  function gfMul(a, b) {
    return (a === 0 || b === 0) ? 0 : GF_EXP[GF_LOG[a] + GF_LOG[b]];
  }

  var RS_GEN_CACHE = {};
  function getRsGenerator(ecLen) {
    if (RS_GEN_CACHE[ecLen]) { return RS_GEN_CACHE[ecLen]; }
    var poly = new Uint8Array([1]);
    for (var i = 0; i < ecLen; i++) {
      var next = new Uint8Array(poly.length + 1);
      var rootFactor = GF_EXP[i];
      for (var j = 0; j < poly.length; j++) {
        next[j] ^= poly[j];
        next[j + 1] ^= gfMul(poly[j], rootFactor);
      }
      poly = next;
    }
    RS_GEN_CACHE[ecLen] = poly;
    return poly;
  }

  function rsComputeRemainder(data, ecLen) {
    var gen = getRsGenerator(ecLen);
    var rem = new Uint8Array(ecLen);
    for (var i = 0; i < data.length; i++) {
      var factor = data[i] ^ rem[0];
      for (var k = 0; k < ecLen - 1; k++) {
        rem[k] = rem[k + 1];
      }
      rem[ecLen - 1] = 0;
      if (factor !== 0) {
        for (var j = 0; j < ecLen; j++) {
          rem[j] ^= gfMul(gen[j + 1], factor);
        }
      }
    }
    return rem;
  }

  /* ------------------------------------------------------------------
   * 3. UTF-8 Byte Conversion & Bit Buffer
   * ------------------------------------------------------------------ */
  function toUtf8Bytes(str) {
    var s = String(str == null ? '' : str);
    if (typeof TextEncoder !== 'undefined') {
      return new TextEncoder().encode(s);
    }
    var out = [];
    for (var i = 0; i < s.length; i++) {
      var c = s.charCodeAt(i);
      if (c >= 0xD800 && c <= 0xDBFF && i + 1 < s.length) {
        var c2 = s.charCodeAt(i + 1);
        if (c2 >= 0xDC00 && c2 <= 0xDFFF) {
          c = ((c - 0xD800) << 10) + (c2 - 0xDC00) + 0x10000;
          i++;
        }
      }
      if (c < 0x80) {
        out.push(c);
      } else if (c < 0x800) {
        out.push(0xC0 | (c >> 6), 0x80 | (c & 0x3F));
      } else if (c < 0x10000) {
        out.push(0xE0 | (c >> 12), 0x80 | ((c >> 6) & 0x3F), 0x80 | (c & 0x3F));
      } else {
        out.push(0xF0 | (c >> 18), 0x80 | ((c >> 12) & 0x3F), 0x80 | ((c >> 6) & 0x3F), 0x80 | (c & 0x3F));
      }
    }
    return new Uint8Array(out);
  }

  function pushBits(bitArr, val, len) {
    for (var i = len - 1; i >= 0; i--) {
      bitArr.push((val >>> i) & 1);
    }
  }

  /* ------------------------------------------------------------------
   * 4. Matrix Construction, Function Patterns, Masking & Penalties
   * ------------------------------------------------------------------ */
  function createMatrix(size) {
    var m = new Array(size);
    for (var r = 0; r < size; r++) {
      m[r] = new Uint8Array(size);
    }
    return m;
  }

  function cloneMatrix(m) {
    var size = m.length;
    var copy = new Array(size);
    for (var r = 0; r < size; r++) {
      copy[r] = new Uint8Array(m[r]);
    }
    return copy;
  }

  function setFunctionModule(modules, isFunc, r, c, dark) {
    modules[r][c] = dark ? 1 : 0;
    isFunc[r][c] = 1;
  }

  function drawFinderPattern(modules, isFunc, topR, leftC) {
    var size = modules.length;
    for (var dr = -1; dr <= 7; dr++) {
      var r = topR + dr;
      if (r < 0 || r >= size) { continue; }
      for (var dc = -1; dc <= 7; dc++) {
        var c = leftC + dc;
        if (c < 0 || c >= size) { continue; }
        var inBox = (dr >= 0 && dr <= 6 && dc >= 0 && dc <= 6);
        var dark = false;
        if (inBox) {
          var dist = Math.max(Math.abs(dr - 3), Math.abs(dc - 3));
          dark = (dist !== 2);
        }
        setFunctionModule(modules, isFunc, r, c, dark);
      }
    }
  }

  function drawAlignmentPattern(modules, isFunc, centerR, centerC) {
    for (var dr = -2; dr <= 2; dr++) {
      for (var dc = -2; dc <= 2; dc++) {
        var dark = (Math.max(Math.abs(dr), Math.abs(dc)) !== 1);
        setFunctionModule(modules, isFunc, centerR + dr, centerC + dc, dark);
      }
    }
  }

  function drawFormatBits(modules, isFunc, ecLevel, mask) {
    var size = modules.length;
    var data = (EC_BITS[ecLevel] << 3) | mask;
    var rem = data;
    for (var i = 0; i < 10; i++) {
      rem = (rem << 1) ^ ((rem >>> 9) * 0x537);
    }
    var bits = ((data << 10) | rem) ^ 0x5412;

    // Copy 1 around top-left finder: bit 0..5 at (0..5, 8), bit 6 at (7, 8), bit 7 at (8, 8), bit 8 at (8, 7), bit 9..14 at (8, 5..0)
    for (var b = 0; b <= 5; b++) {
      setFunctionModule(modules, isFunc, b, 8, (bits >>> b) & 1);
    }
    setFunctionModule(modules, isFunc, 7, 8, (bits >>> 6) & 1);
    setFunctionModule(modules, isFunc, 8, 8, (bits >>> 7) & 1);
    setFunctionModule(modules, isFunc, 8, 7, (bits >>> 8) & 1);
    for (var b2 = 9; b2 < 15; b2++) {
      setFunctionModule(modules, isFunc, 8, 14 - b2, (bits >>> b2) & 1);
    }

    // Copy 2 along top-right and bottom-left
    for (var b3 = 0; b3 < 8; b3++) {
      setFunctionModule(modules, isFunc, 8, size - 1 - b3, (bits >>> b3) & 1);
    }
    for (var b4 = 8; b4 < 15; b4++) {
      setFunctionModule(modules, isFunc, size - 15 + b4, 8, (bits >>> b4) & 1);
    }
    // Always-dark module
    setFunctionModule(modules, isFunc, size - 8, 8, 1);
  }

  function drawVersionBits(modules, isFunc, version) {
    if (version < 7) { return; }
    var size = modules.length;
    var rem = version;
    for (var i = 0; i < 12; i++) {
      rem = (rem << 1) ^ ((rem >>> 11) * 0x1F25);
    }
    var bits = (version << 12) | rem;
    for (var b = 0; b < 18; b++) {
      var dark = (bits >>> b) & 1;
      var a = size - 11 + (b % 3);
      var c = Math.floor(b / 3);
      setFunctionModule(modules, isFunc, c, a, dark);
      setFunctionModule(modules, isFunc, a, c, dark);
    }
  }

  function maskBit(mask, r, c) {
    switch (mask) {
      case 0: return ((r + c) & 1) === 0;
      case 1: return (r & 1) === 0;
      case 2: return (c % 3) === 0;
      case 3: return ((r + c) % 3) === 0;
      case 4: return ((Math.floor(r / 2) + Math.floor(c / 3)) & 1) === 0;
      case 5: return ((r * c) % 2 + (r * c) % 3) === 0;
      case 6: return ((((r * c) % 2) + ((r * c) % 3)) & 1) === 0;
      case 7: return ((((r + c) & 1) + ((r * c) % 3)) & 1) === 0;
      default: return false;
    }
  }

  function finderPenaltyCount(runHistory) {
    // Checks 1:1:3:1:1 dark:light:dark:light:dark preceded or followed by >=4 light modules
    var n = runHistory[1];
    if (n > 0 && runHistory[2] === n && runHistory[3] === n * 3 && runHistory[4] === n && runHistory[5] === n) {
      var score = 0;
      if (runHistory[0] >= n * 4) { score += 40; }
      if (runHistory[6] >= n * 4) { score += 40; }
      return score;
    }
    return 0;
  }

  function pushRun(runHistory, len) {
    for (var i = 6; i > 0; i--) {
      runHistory[i] = runHistory[i - 1];
    }
    runHistory[0] = len;
  }

  function computePenalty(m) {
    var size = m.length;
    var penalty = 0;
    var r, c;

    // N1 (5+ consecutive) + N3 (1:1:3:1:1 finder-like patterns) along rows
    for (r = 0; r < size; r++) {
      var row = m[r];
      var runColor = 0;
      var runLen = 0;
      var hist = [0, 0, 0, 0, 0, 0, 0];
      // Virtual white quiet zone before start of row
      pushRun(hist, size);
      for (c = 0; c < size; c++) {
        if (row[c] !== runColor) {
          pushRun(hist, runLen);
          if (!runColor) { penalty += finderPenaltyCount(hist); }
          runColor = row[c];
          runLen = 1;
        } else {
          runLen++;
          if (runLen === 5) { penalty += 3; }
          else if (runLen > 5) { penalty += 1; }
        }
      }
      if (runColor) {
        pushRun(hist, runLen);
        pushRun(hist, size);
        penalty += finderPenaltyCount(hist);
      } else {
        pushRun(hist, runLen + size);
        penalty += finderPenaltyCount(hist);
      }
    }

    // N1 + N3 along columns
    for (c = 0; c < size; c++) {
      var cColor = 0;
      var cLen = 0;
      var cHist = [0, 0, 0, 0, 0, 0, 0];
      pushRun(cHist, size);
      for (r = 0; r < size; r++) {
        var v = m[r][c];
        if (v !== cColor) {
          pushRun(cHist, cLen);
          if (!cColor) { penalty += finderPenaltyCount(cHist); }
          cColor = v;
          cLen = 1;
        } else {
          cLen++;
          if (cLen === 5) { penalty += 3; }
          else if (cLen > 5) { penalty += 1; }
        }
      }
      if (cColor) {
        pushRun(cHist, cLen);
        pushRun(cHist, size);
        penalty += finderPenaltyCount(cHist);
      } else {
        pushRun(cHist, cLen + size);
        penalty += finderPenaltyCount(cHist);
      }
    }

    // N2: 2x2 blocks of identical color
    for (r = 0; r < size - 1; r++) {
      for (c = 0; c < size - 1; c++) {
        var v0 = m[r][c];
        if (v0 === m[r][c + 1] && v0 === m[r + 1][c] && v0 === m[r + 1][c + 1]) {
          penalty += 3;
        }
      }
    }

    // N4: dark module balance vs 50%
    var darkCount = 0;
    for (r = 0; r < size; r++) {
      for (c = 0; c < size; c++) {
        if (m[r][c]) { darkCount++; }
      }
    }
    var total = size * size;
    var k = Math.ceil(Math.abs(darkCount * 20 - total * 10) / total) - 1;
    if (k > 0) { penalty += k * 10; }

    return penalty;
  }

  /* ------------------------------------------------------------------
   * 5. Core `encode(text, opts)` Entrypoint
   * ------------------------------------------------------------------ */
  function encode(text, opts) {
    var o = opts || {};
    var ecLevel = String(o.ecLevel || 'M').toUpperCase();
    if (!EC_BITS.hasOwnProperty(ecLevel)) { ecLevel = 'M'; }
    var minVer = Math.max(1, Math.min(20, parseInt(o.minVersion, 10) || 1));

    var utf8 = toUtf8Bytes(text);
    var chosenVer = 0;
    var rsSpec = null;

    for (var v = minVer; v <= 20; v++) {
      var spec = RS_BLOCKS[v][ecLevel];
      var dataCw = spec[1] * spec[2] + spec[3] * spec[4];
      var countBits = v <= 9 ? 8 : 16;
      var neededBits = 4 + countBits + utf8.length * 8;
      if (neededBits <= dataCw * 8) {
        chosenVer = v;
        rsSpec = spec;
        break;
      }
    }
    if (!chosenVer) {
      throw new Error('SCQR: payload exceeds QR Version 20 capacity (' + utf8.length + ' UTF-8 bytes at EC ' + ecLevel + ')');
    }

    var totalDataCw = rsSpec[1] * rsSpec[2] + rsSpec[3] * rsSpec[4];
    var capacityBits = totalDataCw * 8;
    var charCountBits = chosenVer <= 9 ? 8 : 16;

    // Build data bit stream: Mode 0100 (Byte) + byte count + UTF-8 payload + terminator + pad bytes
    var bits = [];
    pushBits(bits, 0x4, 4);
    pushBits(bits, utf8.length, charCountBits);
    for (var i = 0; i < utf8.length; i++) {
      pushBits(bits, utf8[i], 8);
    }
    var termLen = Math.min(4, capacityBits - bits.length);
    pushBits(bits, 0, termLen);
    while ((bits.length & 7) !== 0) {
      bits.push(0);
    }

    var dataBytes = new Uint8Array(totalDataCw);
    var byteLen = bits.length >>> 3;
    for (var bIdx = 0; bIdx < byteLen; bIdx++) {
      var val = 0;
      for (var bit = 0; bit < 8; bit++) {
        val = (val << 1) | bits[(bIdx << 3) + bit];
      }
      dataBytes[bIdx] = val;
    }
    var padByte = 0xEC;
    for (var pIdx = byteLen; pIdx < totalDataCw; pIdx++) {
      dataBytes[pIdx] = padByte;
      padByte = (padByte === 0xEC) ? 0x11 : 0xEC;
    }

    // Split into blocks, compute Reed-Solomon EC, and interleave
    var ecPerBlock = rsSpec[0];
    var g1Blocks = rsSpec[1];
    var g1DataCw = rsSpec[2];
    var g2Blocks = rsSpec[3];
    var g2DataCw = rsSpec[4];
    var totalBlocks = g1Blocks + g2Blocks;
    var dataBlocks = new Array(totalBlocks);
    var ecBlocks = new Array(totalBlocks);
    var offset = 0;

    for (var b = 0; b < totalBlocks; b++) {
      var dLen = (b < g1Blocks) ? g1DataCw : g2DataCw;
      var block = dataBytes.subarray(offset, offset + dLen);
      offset += dLen;
      dataBlocks[b] = block;
      ecBlocks[b] = rsComputeRemainder(block, ecPerBlock);
    }

    var totalCodewords = totalDataCw + totalBlocks * ecPerBlock;
    var codewords = new Uint8Array(totalCodewords);
    var cwPos = 0;
    var maxDataLen = Math.max(g1DataCw, g2DataCw);
    for (var col = 0; col < maxDataLen; col++) {
      for (var blk = 0; blk < totalBlocks; blk++) {
        if (col < dataBlocks[blk].length) {
          codewords[cwPos++] = dataBlocks[blk][col];
        }
      }
    }
    for (var ecCol = 0; ecCol < ecPerBlock; ecCol++) {
      for (var blk2 = 0; blk2 < totalBlocks; blk2++) {
        codewords[cwPos++] = ecBlocks[blk2][ecCol];
      }
    }

    // Build base matrix & function patterns
    var size = chosenVer * 4 + 17;
    var baseModules = createMatrix(size);
    var isFunc = createMatrix(size);

    // 3 Finder patterns + separators
    drawFinderPattern(baseModules, isFunc, 0, 0);
    drawFinderPattern(baseModules, isFunc, 0, size - 7);
    drawFinderPattern(baseModules, isFunc, size - 7, 0);

    // Timing patterns (row 6 & col 6)
    for (var t = 8; t < size - 8; t++) {
      var tDark = (t & 1) === 0 ? 1 : 0;
      setFunctionModule(baseModules, isFunc, 6, t, tDark);
      setFunctionModule(baseModules, isFunc, t, 6, tDark);
    }

    // Alignment patterns
    var align = ALIGN_POS[chosenVer];
    var numAlign = align.length;
    for (var ar = 0; ar < numAlign; ar++) {
      for (var ac = 0; ac < numAlign; ac++) {
        if ((ar === 0 && ac === 0) || (ar === 0 && ac === numAlign - 1) || (ar === numAlign - 1 && ac === 0)) {
          continue;
        }
        drawAlignmentPattern(baseModules, isFunc, align[ar], align[ac]);
      }
    }

    // Reserve format & version info modules before placing data
    drawFormatBits(baseModules, isFunc, ecLevel, 0);
    drawVersionBits(baseModules, isFunc, chosenVer);

    // Zigzag 2-column codeword placement (skipping vertical timing column 6!)
    var totalCodewordBits = codewords.length * 8;
    var bitCursor = 0;
    var upward = true;
    for (var right = size - 1; right >= 1; right -= 2) {
      if (right === 6) { right = 5; }
      for (var vert = 0; vert < size; vert++) {
        var rowIdx = upward ? (size - 1 - vert) : vert;
        for (var dx = 0; dx < 2; dx++) {
          var colIdx = right - dx;
          if (!isFunc[rowIdx][colIdx]) {
            var darkBit = 0;
            if (bitCursor < totalCodewordBits) {
              darkBit = (codewords[bitCursor >>> 3] >>> (7 - (bitCursor & 7))) & 1;
            }
            baseModules[rowIdx][colIdx] = darkBit;
            bitCursor++;
          }
        }
      }
      upward = !upward;
    }

    // Evaluate all 8 masks (or explicit forced mask) and pick lowest penalty
    var bestMask = 0;
    var bestMatrix = null;
    var bestPenalty = Infinity;
    var startMask = (typeof o.mask === 'number' && o.mask >= 0 && o.mask <= 7) ? o.mask : 0;
    var endMask = (typeof o.mask === 'number' && o.mask >= 0 && o.mask <= 7) ? o.mask : 7;

    for (var m = startMask; m <= endMask; m++) {
      var cand = cloneMatrix(baseModules);
      for (var r = 0; r < size; r++) {
        for (var c = 0; c < size; c++) {
          if (!isFunc[r][c] && maskBit(m, r, c)) {
            cand[r][c] ^= 1;
          }
        }
      }
      // Write exact format bits for mask `m`
      var dummyFunc = createMatrix(size);
      drawFormatBits(cand, dummyFunc, ecLevel, m);
      var pen = computePenalty(cand);
      if (pen < bestPenalty) {
        bestPenalty = pen;
        bestMask = m;
        bestMatrix = cand;
      }
    }

    return {
      version: chosenVer,
      size: size,
      ecLevel: ecLevel,
      mask: bestMask,
      modules: bestMatrix
    };
  }

  /* ------------------------------------------------------------------
   * 6. Renderers: `toSvg`, `toCanvas`, and Pure-JS RFC 2083 PNG `toDataUrl`
   * ------------------------------------------------------------------ */
  function sanitizeXmlAttr(s) {
    return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function toSvg(text, opts) {
    var o = opts || {};
    var qr = encode(text, o);
    var margin = (typeof o.margin === 'number' && o.margin >= 0) ? Math.floor(o.margin) : 4;
    var pxSize = Math.max(64, parseInt(o.size, 10) || 256);
    var dark = sanitizeXmlAttr(o.dark || '#000000');
    var light = sanitizeXmlAttr(o.light || '#ffffff');
    var full = qr.size + margin * 2;

    var parts = [];
    for (var r = 0; r < qr.size; r++) {
      var row = qr.modules[r];
      var c = 0;
      while (c < qr.size) {
        if (row[c]) {
          var start = c;
          while (c < qr.size && row[c]) { c++; }
          var run = c - start;
          parts.push('M' + (start + margin) + ' ' + (r + margin) + 'h' + run + 'v1h-' + run + 'z');
        } else {
          c++;
        }
      }
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + full + ' ' + full + '" width="' + pxSize + '" height="' + pxSize + '" shape-rendering="crispEdges" role="img" aria-label="QR Code">'
      + '<rect width="100%" height="100%" fill="' + light + '"/>'
      + '<path d="' + parts.join('') + '" fill="' + dark + '"/>'
      + '</svg>';
  }

  function toCanvas(canvas, text, opts) {
    if (!canvas || typeof canvas.getContext !== 'function') {
      throw new Error('SCQR.toCanvas requires a valid HTMLCanvasElement');
    }
    var o = opts || {};
    var qr = encode(text, o);
    var margin = (typeof o.margin === 'number' && o.margin >= 0) ? Math.floor(o.margin) : 4;
    var totalMods = qr.size + margin * 2;
    var targetPx = Math.max(totalMods * 2, parseInt(o.size, 10) || 256);

    canvas.width = targetPx;
    canvas.height = targetPx;
    var ctx = canvas.getContext('2d');
    ctx.imageSmoothingEnabled = false;
    ctx.fillStyle = o.light || '#ffffff';
    ctx.fillRect(0, 0, targetPx, targetPx);
    ctx.fillStyle = o.dark || '#000000';

    for (var r = 0; r < qr.size; r++) {
      var row = qr.modules[r];
      var y0 = Math.round(((r + margin) * targetPx) / totalMods);
      var y1 = Math.round(((r + margin + 1) * targetPx) / totalMods);
      var mh = Math.max(1, y1 - y0);
      for (var c = 0; c < qr.size; c++) {
        if (row[c]) {
          var x0 = Math.round(((c + margin) * targetPx) / totalMods);
          var x1 = Math.round(((c + margin + 1) * targetPx) / totalMods);
          ctx.fillRect(x0, y0, Math.max(1, x1 - x0), mh);
        }
      }
    }
    return canvas;
  }

  // Pure-JS CRC-32 & Adler-32 for RFC 2083 PNG encoding (works identically in browser & Node!)
  var CRC_TABLE = (function () {
    var tbl = new Uint32Array(256);
    for (var n = 0; n < 256; n++) {
      var c = n;
      for (var k = 0; k < 8; k++) {
        c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
      }
      tbl[n] = c >>> 0;
    }
    return tbl;
  })();

  function crc32(buf, start, len) {
    var c = 0xFFFFFFFF;
    for (var i = 0; i < len; i++) {
      c = CRC_TABLE[(c ^ buf[start + i]) & 0xFF] ^ (c >>> 8);
    }
    return (c ^ 0xFFFFFFFF) >>> 0;
  }

  function adler32(buf) {
    var s1 = 1;
    var s2 = 0;
    for (var i = 0; i < buf.length; i++) {
      s1 = (s1 + buf[i]) % 65521;
      s2 = (s2 + s1) % 65521;
    }
    return ((s2 << 16) | s1) >>> 0;
  }

  function writeU32BE(arr, pos, val) {
    arr[pos] = (val >>> 24) & 0xFF;
    arr[pos + 1] = (val >>> 16) & 0xFF;
    arr[pos + 2] = (val >>> 8) & 0xFF;
    arr[pos + 3] = val & 0xFF;
  }

  function makePngChunk(typeStr, dataBytes) {
    var len = dataBytes.length;
    var chunk = new Uint8Array(12 + len);
    writeU32BE(chunk, 0, len);
    for (var i = 0; i < 4; i++) {
      chunk[4 + i] = typeStr.charCodeAt(i);
    }
    chunk.set(dataBytes, 8);
    var crc = crc32(chunk, 4, 4 + len);
    writeU32BE(chunk, 8 + len, crc);
    return chunk;
  }

  function parseHexRgb(hex, fallback) {
    var s = String(hex || '').trim();
    if (/^#[0-9a-fA-F]{6}$/.test(s)) {
      return [
        parseInt(s.slice(1, 3), 16),
        parseInt(s.slice(3, 5), 16),
        parseInt(s.slice(5, 7), 16)
      ];
    }
    if (/^#[0-9a-fA-F]{3}$/.test(s)) {
      return [
        parseInt(s.charAt(1) + s.charAt(1), 16),
        parseInt(s.charAt(2) + s.charAt(2), 16),
        parseInt(s.charAt(3) + s.charAt(3), 16)
      ];
    }
    return fallback;
  }

  var B64_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
  function bytesToBase64(bytes) {
    if (typeof Buffer !== 'undefined' && typeof Buffer.from === 'function') {
      return Buffer.from(bytes).toString('base64');
    }
    var out = '';
    var len = bytes.length;
    for (var i = 0; i < len; i += 3) {
      var b0 = bytes[i];
      var b1 = (i + 1 < len) ? bytes[i + 1] : 0;
      var b2 = (i + 2 < len) ? bytes[i + 2] : 0;
      var trip = (b0 << 16) | (b1 << 8) | b2;
      out += B64_CHARS[(trip >>> 18) & 63];
      out += B64_CHARS[(trip >>> 12) & 63];
      out += (i + 1 < len) ? B64_CHARS[(trip >>> 6) & 63] : '=';
      out += (i + 2 < len) ? B64_CHARS[trip & 63] : '=';
    }
    return out;
  }

  function encodePngBytes(qr, opts) {
    var o = opts || {};
    var margin = (typeof o.margin === 'number' && o.margin >= 0) ? Math.floor(o.margin) : 4;
    var totalMods = qr.size + margin * 2;
    var targetPx = Math.max(totalMods * 2, parseInt(o.size, 10) || 256);
    var scale = Math.max(4, Math.floor(targetPx / totalMods));
    var width = totalMods * scale;
    var height = width;

    var darkRgb = parseHexRgb(o.dark, [0, 0, 0]);
    var lightRgb = parseHexRgb(o.light, [255, 255, 255]);
    var isGray = (darkRgb[0] === darkRgb[1] && darkRgb[1] === darkRgb[2] && lightRgb[0] === lightRgb[1] && lightRgb[1] === lightRgb[2]);

    // ColorType 0 = 8-bit grayscale (compact & universally decoded by zbar/ZXing/iOS/Android), ColorType 2 = 8-bit RGB
    var channels = isGray ? 1 : 3;
    var rowStride = 1 + width * channels; // 1 filter byte (0 = None) per scanline
    var rawScanlines = new Uint8Array(height * rowStride);

    for (var y = 0; y < height; y++) {
      var modR = Math.floor(y / scale) - margin;
      var lineStart = y * rowStride;
      rawScanlines[lineStart] = 0; // Filter type 0 (None)
      var inRow = (modR >= 0 && modR < qr.size) ? qr.modules[modR] : null;
      for (var x = 0; x < width; x++) {
        var modC = Math.floor(x / scale) - margin;
        var isDark = (inRow !== null && modC >= 0 && modC < qr.size && inRow[modC] === 1);
        var pxOff = lineStart + 1 + x * channels;
        if (isGray) {
          rawScanlines[pxOff] = isDark ? darkRgb[0] : lightRgb[0];
        } else {
          var rgb = isDark ? darkRgb : lightRgb;
          rawScanlines[pxOff] = rgb[0];
          rawScanlines[pxOff + 1] = rgb[1];
          rawScanlines[pxOff + 2] = rgb[2];
        }
      }
    }

    // Wrap rawScanlines in zlib stream (0x78 0x01 + uncompressed DEFLATE BTYPE=00 blocks <= 65535B + Adler-32)
    var maxBlock = 65535;
    var numBlocks = Math.max(1, Math.ceil(rawScanlines.length / maxBlock));
    var zlibLen = 2 + numBlocks * 5 + rawScanlines.length + 4;
    var zlib = new Uint8Array(zlibLen);
    zlib[0] = 0x78;
    zlib[1] = 0x01;
    var zPos = 2;
    var sPos = 0;
    for (var b = 0; b < numBlocks; b++) {
      var rem = rawScanlines.length - sPos;
      var bLen = Math.min(maxBlock, rem);
      var isFinal = (b === numBlocks - 1) ? 1 : 0;
      zlib[zPos++] = isFinal;
      zlib[zPos++] = bLen & 0xFF;
      zlib[zPos++] = (bLen >>> 8) & 0xFF;
      var nLen = (~bLen) & 0xFFFF;
      zlib[zPos++] = nLen & 0xFF;
      zlib[zPos++] = (nLen >>> 8) & 0xFF;
      zlib.set(rawScanlines.subarray(sPos, sPos + bLen), zPos);
      zPos += bLen;
      sPos += bLen;
    }
    writeU32BE(zlib, zPos, adler32(rawScanlines));

    // PNG Signature + IHDR + IDAT + IEND
    var sig = new Uint8Array([137, 80, 78, 71, 13, 10, 26, 10]);
    var ihdr = new Uint8Array(13);
    writeU32BE(ihdr, 0, width);
    writeU32BE(ihdr, 4, height);
    ihdr[8] = 8; // bit depth 8
    ihdr[9] = isGray ? 0 : 2; // color type
    ihdr[10] = 0; // compression
    ihdr[11] = 0; // filter
    ihdr[12] = 0; // interlace

    var cIhdr = makePngChunk('IHDR', ihdr);
    var cIdat = makePngChunk('IDAT', zlib);
    var cIend = makePngChunk('IEND', new Uint8Array(0));

    var png = new Uint8Array(sig.length + cIhdr.length + cIdat.length + cIend.length);
    var off = 0;
    png.set(sig, off); off += sig.length;
    png.set(cIhdr, off); off += cIhdr.length;
    png.set(cIdat, off); off += cIdat.length;
    png.set(cIend, off);
    return png;
  }

  function toDataUrl(text, opts) {
    var o = opts || {};
    if (String(o.format || 'png').toLowerCase() === 'svg') {
      var svgStr = toSvg(text, o);
      return 'data:image/svg+xml;base64,' + bytesToBase64(toUtf8Bytes(svgStr));
    }
    var qr = encode(text, o);
    var pngBytes = encodePngBytes(qr, o);
    return 'data:image/png;base64,' + bytesToBase64(pngBytes);
  }

  return {
    encode: encode,
    toSvg: toSvg,
    toCanvas: toCanvas,
    toDataUrl: toDataUrl
  };
});
