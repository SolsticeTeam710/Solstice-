<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $status }} | Solstice Coffee</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #34251d;
            background: #f7f3ee;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            display: grid;
            place-items: center;
        }

        .card {
            width: min(100%, 460px);
            padding: 36px;
            text-align: center;
            background: #fff;
            border: 1px solid #e6ddd5;
            border-radius: 20px;
            box-shadow: 0 18px 50px rgba(52, 37, 29, .08);
        }

        .brand {
            margin-bottom: 28px;
            color: #704b35;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .status {
            display: inline-grid;
            min-width: 76px;
            min-height: 76px;
            margin-bottom: 18px;
            place-items: center;
            color: #704b35;
            background: #f4e9df;
            border-radius: 50%;
            font-size: 22px;
            font-weight: 800;
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(22px, 5vw, 28px);
        }

        p {
            margin: 0;
            color: #76685e;
            line-height: 1.65;
        }

        .button {
            display: inline-block;
            margin-top: 26px;
            padding: 12px 18px;
            color: #fff;
            background: #704b35;
            border-radius: 10px;
            font-weight: 700;
            text-decoration: none;
        }

        .button:hover { background: #5d3c2b; }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">Solstice Coffee</div>
        <div class="status">{{ $status }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a class="button" href="{{ url('/') }}">Kembali ke aplikasi</a>
    </main>
</body>
</html>