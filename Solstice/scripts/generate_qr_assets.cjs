const fs = require('node:fs');
const path = require('node:path');
const { BarcodeFormat, QRCodeWriter } = require('../node_modules/html5-qrcode/third_party/zxing-js.umd.js');

const baseUrl = (process.argv[2] || process.env.SOLSTICE_PUBLIC_URL || 'https://amazingly-peso-sandworm.ngrok-free.dev').replace(/\/+$/, '');
const outputDir = path.join(__dirname, '..', 'public', 'qr-meja');
const writer = new QRCodeWriter();
fs.mkdirSync(outputDir, { recursive: true });

function createSvg(payload, title, subtitle, footer) {
  const matrix = writer.encode(payload, BarcodeFormat.QR_CODE, 0, 0, null);
  const size = matrix.getWidth();
  let pathData = '';

  for (let y = 0; y < size; y++) {
    for (let x = 0; x < size; x++) {
      if (matrix.get(x, y)) pathData += `M${x} ${y}h1v1h-1z`;
    }
  }

  return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="720" viewBox="0 0 600 720" role="img" aria-label="${title}">
  <rect width="600" height="720" fill="#fff"/>
  <text x="300" y="54" text-anchor="middle" font-family="Arial,sans-serif" font-size="27" font-weight="700" fill="#4b2e20">SOLSTICE COFFEE</text>
  <text x="300" y="96" text-anchor="middle" font-family="Arial,sans-serif" font-size="23" font-weight="700" fill="#29231f">${title}</text>
  <text x="300" y="128" text-anchor="middle" font-family="Arial,sans-serif" font-size="15" fill="#655c55">${subtitle}</text>
  <g transform="translate(70 150) scale(${460 / size})" shape-rendering="crispEdges">
    <rect width="${size}" height="${size}" fill="#fff"/>
    <path d="${pathData}" fill="#111"/>
  </g>
  <text x="300" y="665" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#777">${footer}</text>
</svg>
`;
}

const tables = [];
for (const tableNo of [1, 2, 3, 4]) {
  const padded = String(tableNo).padStart(2, '0');
  const destination = `${baseUrl}/pesan?meja=${tableNo}`;
  const filename = `meja-${padded}.svg`;
  fs.writeFileSync(path.join(outputDir, filename), createSvg(destination, `PESAN DI MEJA ${padded}`, 'Scan untuk membuka menu pelanggan', destination), 'utf8');
  tables.push({ tableNo, padded, filename, destination });
}

const qrisUrl = `${baseUrl}/qris/scan?token=solstice`;
fs.writeFileSync(path.join(outputDir, 'qris.svg'), createSvg(qrisUrl, 'QRIS SOLSTICE COFFEE', 'Simulasi kamera — bukan pembayaran nyata', qrisUrl), 'utf8');

const cards = tables.map(({ padded, filename, destination }) => `
  <section class="card">
    <h2>Solstice Coffee · Meja ${padded}</h2>
    <p>Pindai untuk membuka menu dan memesan dari meja ini.</p>
    <img src="${filename}" alt="QR meja ${padded}">
    <small>${destination}</small>
  </section>`).join('\n');

const html = `<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cetak QR Meja — Solstice Coffee</title>
<style>
*{box-sizing:border-box}body{margin:0;padding:20px;background:#f4f1ed;color:#29231f;font-family:Arial,sans-serif}.sheet{max-width:1000px;margin:auto;display:grid;grid-template-columns:1fr 1fr;gap:18px}.card{background:#fff;border:1px solid #ddd4cc;border-radius:14px;padding:16px;text-align:center;break-inside:avoid}.card h2{margin:0 0 6px;color:#4b2e20;font-size:20px}.card p{margin:0 0 6px;color:#655c55;font-size:14px}.card img{display:block;width:min(100%,300px);height:auto;margin:0 auto;image-rendering:pixelated}.card small{display:block;overflow-wrap:anywhere;color:#777;font-size:10px}@media print{@page{size:A4 portrait;margin:8mm}body{padding:0;background:#fff}.sheet{gap:6mm}.card{border:1px solid #aaa;border-radius:0;padding:3mm}.card img{width:66mm}.card h2{font-size:15pt}.card p{font-size:9pt}.card small{font-size:7pt}}
</style></head><body><main class="sheet">${cards}\n</main></body></html>`;
fs.writeFileSync(path.join(outputDir, 'cetak.html'), html, 'utf8');
console.log(`QR meja 1–4 dan QRIS dibuat di ${outputDir}`);
console.log(`Domain yang dipakai: ${baseUrl}`);
