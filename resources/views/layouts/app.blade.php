<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Coffe Shop - @yield('title', 'Menu')
    </title>

    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #F8F1E7;
            color: #3E2723;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        .navbar-custom {
            background-color: #5D4037;
        }

        .navbar-brand {
            color: #F5E6D3 !important;
        }

        .navbar-brand span {
            color: #FFFFFF;
        }

        .btn-coffee {
            background-color: #795548;
            color: #FFFFFF;
        }

        .btn-coffee:hover {
            background-color: #5D4037;
            color: #FFFFFF;
        }

        .card-coffee {
            background-color: #FFFFFF;
            border: 1px solid #E6D5C3;
        }

        footer {
            background-color: #3E2723;
        }
    </style>
</head>

<body>

    <!-- Navbar Utama -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top shadow-sm">
        <div class="container">

            <a class="navbar-brand fw-bold" href="/">
                Coffe <span>Shop</span>
            </a>

            <div class="collapse navbar-collapse">

                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                    <li class="nav-item">
                        <a class="nav-link active" href="/">
                            Menu
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="/cart">
                            Keranjang
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="/order/status">
                            Status Pesanan
                        </a>
                    </li>

                </ul>

                <div class="d-flex align-items-center">
                    <a
                        href="/login"
                        class="btn btn-coffee btn-sm px-3 rounded-pill"
                    >
                        Login Kasir
                    </a>
                </div>

            </div>
        </div>
    </nav>

    <!-- Konten Dinamis -->
    <main class="container my-4">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="text-white-50 text-center py-3 border-top mt-5 small">
        © 2026 Coffe Shop Order System • UKK RPL SMK Budi Bakti Ciwidey
    </footer>

</body>
</html>
