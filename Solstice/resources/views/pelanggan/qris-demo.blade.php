<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Demo — Solstice Coffee</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #f5f1eb; color: #30251f; font-family: Arial, sans-serif; }
        main { width: min(100%, 520px); padding: 28px; border: 1px solid #e5ddd4; border-radius: 22px; background: #fff; text-align: center; box-shadow: 0 18px 50px #30251f12; }
        .eyebrow { color: #9a6b46; font-size: 12px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 8px 0; color: #4b2e20; font-size: clamp(26px, 7vw, 34px); }
        p { color: #6f6259; line-height: 1.6; }
        img { display: block; width: min(100%, 330px); height: auto; margin: 18px auto; image-rendering: pixelated; }
        .warning { padding: 12px 14px; border: 1px solid #f0d5a3; border-radius: 12px; background: #fff7e8; color: #704d1d; font-size: 14px; font-weight: 700; }
        code { display: block; margin-top: 14px; overflow-wrap: anywhere; color: #81746a; font-size: 11px; }
    </style>
</head>
<body>
    <main>
        <div class="eyebrow">Solstice Coffee</div>
        <h1>QR Demo QRIS</h1>
        <p>Tampilkan halaman ini di laptop atau perangkat lain, lalu pindai kodenya dari kamera pada halaman pemesanan pelanggan.</p>
        <img src="{{ asset('qr-meja/qris-demo.svg') }}" alt="QR demo simulasi Solstice Coffee">
        <div class="warning">Simulasi saja — bukan kode pembayaran dan tidak ada uang yang diproses.</div>
        <code>{{ url('/demo-qris/scan?demo=solstice') }}</code>
    </main>
</body>
</html>
