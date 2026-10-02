<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — Solstice Coffe</title>
    <style>
        :root{
            --primary:#6F4E37; --primary-dark:#4B2E1F; --cream:#F5E6CC;
            --bg:#FAF8F4; --border:#E6E0D8; --muted:#7a7a7a; --danger:#C62828;
        }
        *{box-sizing:border-box}
        body{
            margin:0; min-height:100vh; display:grid; place-items:center; padding:20px;
            background:var(--bg); color:#222;
            font-family:"Segoe UI",system-ui,Arial,sans-serif;
        }
        .card{
            width:100%; max-width:420px; background:#fff; border:1px solid var(--border);
            border-radius:22px; padding:36px 34px; box-shadow:0 12px 40px rgba(75,46,31,.08);
        }
        .brand{text-align:center; padding-bottom:22px; border-bottom:1px solid var(--border)}
        .logo{
            width:68px; height:68px; margin:0 auto 12px; border-radius:16px; overflow:hidden;
            background:var(--cream); border:2px solid var(--primary);
            display:grid; place-items:center; font-size:32px;
        }
        .logo img{width:100%; height:100%; object-fit:cover}
        .brand h1{margin:0; font-size:26px; color:var(--primary)}
        .brand p{margin:4px 0 0; color:var(--muted); font-size:14px}

        .tabs{display:flex; gap:8px; margin:22px 0 4px}
        .tab{
            flex:1; padding:10px; border-radius:10px; border:1px solid var(--border);
            background:#fff; color:var(--primary); font-weight:700; font-size:14px; cursor:pointer;
        }
        .tab.active{background:var(--cream); border-color:var(--primary)}

        h2{margin:18px 0 6px; font-size:18px}
        label{display:block; margin-top:16px; font-size:13px; font-weight:700}
        .field{position:relative}
        input[type=text], input[type=password]{
            width:100%; margin-top:6px; padding:13px 14px; font:inherit;
            border:1px solid var(--border); border-radius:10px; outline:none; background:#fff;
        }
        input:focus{border-color:var(--primary); box-shadow:0 0 0 3px rgba(111,78,55,.12)}
        input.invalid{border-color:var(--danger)}
        .toggle{
            position:absolute; right:10px; top:50%; transform:translateY(-50%);
            margin-top:3px; background:none; border:0; cursor:pointer; font-size:18px; color:var(--muted);
        }
        .error{color:var(--danger); font-size:13px; margin-top:6px}
        .alert{
            margin-top:16px; padding:12px 14px; border-radius:10px; font-size:14px;
            background:#FDE8EA; color:var(--danger); border:1px solid var(--danger);
        }
        .btn{
            width:100%; margin-top:24px; padding:14px; border:0; border-radius:10px;
            background:var(--primary); color:#fff; font:inherit; font-weight:700; cursor:pointer;
        }
        .btn:hover{background:var(--primary-dark)}
        .back{display:block; margin-top:16px; text-align:center; font-size:13px; color:var(--primary); text-decoration:none}
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">
    <div class="logo">
        @if (file_exists(public_path('logo.png.jpeg')))
            <img src="{{ asset('logo.png.jpeg') }}" alt="Logo Solstice Coffe">
        @else
            ☕
        @endif
    </div>
    <h1>Solstice Coffe</h1>
    <p id="roleLabel">Kasir</p>
</div>

        <form method="POST" action="{{ route('login.process') }}" novalidate>
            @csrf
            <input type="hidden" name="role" id="role" value="{{ old('role', 'kasir') }}">

            <div class="tabs">
                <button type="button" class="tab" data-role="kasir">Kasir</button>
                <button type="button" class="tab" data-role="admin">Admin</button>
            </div>

            <h2 id="title">Login Kasir</h2>

            @if (session('error'))
                <div class="alert">{{ session('error') }}</div>
            @endif

            <label for="login">Email atau Username</label>
            <input type="text" id="login" name="login" value="{{ old('login') }}"
                   placeholder="kasir" autocomplete="username" required autofocus
                   class="{{ $errors->has('login') ? 'invalid' : '' }}">
            @error('login') <div class="error">{{ $message }}</div> @enderror

            <label for="password">Password</label>
            <div class="field">
                <input type="password" id="password" name="password" placeholder="........"
                       autocomplete="current-password" required
                       class="{{ $errors->has('password') ? 'invalid' : '' }}">
                <button type="button" class="toggle" id="togglePw" aria-label="Tampilkan password">👁</button>
            </div>
            @error('password') <div class="error">{{ $message }}</div> @enderror

            <button type="submit" class="btn" id="submitBtn">Masuk</button>
        </form>

        <a class="back" href="{{ url('/') }}">← Kembali ke Beranda</a>
    </div>

    <script>
        const roleInput = document.getElementById('role');
        const tabs = document.querySelectorAll('.tab');

        function setRole(role) {
            roleInput.value = role;
            const isKasir = role === 'kasir';
            document.getElementById('roleLabel').textContent = isKasir ? 'Kasir' : 'Admin';
            document.getElementById('title').textContent = isKasir ? 'Login Kasir' : 'Login Admin';
            document.getElementById('login').placeholder = isKasir ? 'kasir' : 'admin';
            document.getElementById('submitBtn').textContent = isKasir ? 'Masuk Sekarang' : 'Masuk';
            tabs.forEach(t => t.classList.toggle('active', t.dataset.role === role));
        }
        tabs.forEach(t => t.addEventListener('click', () => setRole(t.dataset.role)));
        setRole(roleInput.value);

        document.getElementById('togglePw').addEventListener('click', () => {
            const pw = document.getElementById('password');
            pw.type = pw.type === 'password' ? 'text' : 'password';
        });
    </script>
</body>
</html>
