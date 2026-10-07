<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pesan di Meja {{ request('meja', '04') }} — Solstice Coffee</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/pelanggan.css', 'resources/js/pelanggan.js'])
</head>
<body>
    <div class="app">
        <header class="top">
            <div class="brand">
                <img class="logo" src="{{ asset('images/solstice-logo.jpeg') }}" alt="Solstice Coffee">
                <div>
                    <div class="brand-name">Solstice Coffee</div>
                    <div class="brand-sub">Pesan langsung dari meja Anda</div>
                </div>
            </div>
            <span class="table-chip">Meja No. {{ request('meja', '04') }}</span>
        </header>

        <main class="main">
            {{-- Katalog menu --}}
            <section class="page active" id="page-menu">
                <div class="page-heading menu-page-heading">
                    <div>
                        <div class="eyebrow">Menu Solstice</div>
                        <h1>Temukan favoritmu</h1>
                        <p class="page-desc">Pilih kopi dan makanan untuk menemani harimu.</p>
                    </div>
                </div>

                <button id="track-last-order" class="track-last-order" type="button" hidden>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v15H4z"/><path d="M8 3v4M16 3v4M8 11h8M8 15h5"/></svg>
                    Lacak Pesanan Terakhir
                </button>

                <label class="search">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <input id="menu-search" type="search" placeholder="Cari kopi atau makanan..." aria-label="Cari menu">
                </label>

                <div class="filters" id="category-filters" role="group" aria-label="Filter kategori menu">
                    <button class="filter active" data-category="all" aria-pressed="true">Semua</button>
                    <button class="filter" data-category="coffee" aria-pressed="false">Kopi</button>
                    <button class="filter" data-category="non-coffee" aria-pressed="false">Non-Kopi</button>
                    <button class="filter" data-category="makanan" aria-pressed="false">Makanan</button>
                </div>

                <div class="products" id="products" aria-live="polite">
                    <div class="empty">Memuat menu…</div>
                </div>
            </section>

            {{-- Keranjang dan catatan pesanan --}}
            <section class="page" id="page-cart">
                <div class="page-heading">
                    <div>
                        <div class="eyebrow">Pesanan Anda</div>
                        <h1>Keranjang</h1>
                        <p class="page-desc">Tinjau pesanan sebelum melanjutkan.</p>
                    </div>
                    <button class="secondary" data-page="menu">← Kembali ke menu</button>
                </div>

                <div class="panel">
                    <div class="line-items" id="cart-items"></div>
                    <div class="field">
                        <label for="order-note">Catatan pesanan</label>
                        <textarea id="order-note" rows="3" placeholder="Contoh: Kurangi manis, es batu sedikit saja..."></textarea>
                    </div>
                    <div class="totals" id="cart-totals"></div>
                    <button class="primary full" id="go-checkout">Lanjut ke Pembayaran</button>
                </div>
            </section>

            {{-- Informasi pelanggan, pembayaran, dan ringkasan --}}
            <section class="page" id="page-checkout">
                <div class="page-heading">
                    <div>
                        <div class="eyebrow">Satu langkah lagi</div>
                        <h1>Konfirmasi</h1>
                        <p class="page-desc">Lengkapi informasi pembayaran Anda.</p>
                    </div>
                    <button class="secondary" data-page="cart">← Keranjang</button>
                </div>

                <div class="checkout-grid">
                    <div>
                        <section class="panel">
                            <h2>Informasi pelanggan</h2>
                            <div class="field">
                                <label for="customer-name">Nama pelanggan <span class="required">*</span></label>
                                <input id="customer-name" placeholder="Masukkan nama Anda" maxlength="100" required>
                            </div>
                        </section>

                        <section class="panel">
                            <h2>Pilih metode pembayaran</h2>
                            <div class="payment-options" role="group" aria-label="Metode pembayaran">
                                <button type="button" class="payment-option active" data-payment="CASH" aria-pressed="true">
                                    <strong><svg class="payment-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M7 9h.01M17 15h.01"/></svg> Tunai</strong>
                                    <small>Bayar di kasir</small>
                                </button>
                                <button type="button" class="payment-option" data-payment="QRIS" aria-pressed="false">
                                    <strong><svg class="payment-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 9V4h5M15 4h5v5M20 15v5h-5M9 20H4v-5"/><path d="M9 9h2v2H9zM14 9h2v2h-2zM9 14h2v2H9zM14 14h2v2h-2z"/></svg> QRIS</strong>
                                    <small>Pindai kode di kasir</small>
                                </button>
                            </div>
                            <div class="notice">Pembayaran dilakukan kepada kasir setelah pesanan Anda dikonfirmasi.</div>
                        </section>
                    </div>

                    <aside class="panel">
                        <h2>Ringkasan pesanan</h2>
                        <div class="line-items" id="checkout-items"></div>
                        <div class="totals" id="checkout-totals"></div>
                        <button class="primary full" id="confirm-order">Konfirmasi Pesanan</button>
                    </aside>
                </div>
            </section>

            {{-- Konfirmasi lokal --}}
            <section class="page" id="page-success">
                <div class="panel center">
                    <div class="success-icon" aria-hidden="true">✓</div>
                    <div class="eyebrow">Pesanan berhasil dibuat</div>
                    <h1 class="success-title">Terima kasih, <span id="success-name">Teman</span>!</h1>
                    <p class="page-desc">Pesanan Anda sudah masuk ke sistem. Simpan Order ID untuk mengecek statusnya.</p>
                    <div class="order-code" id="success-order">#SC-000</div>

                    <div class="queue">
                        <div>
                            <small>Meja</small>
                            <strong>{{ request('meja', '04') }}</strong>
                        </div>
                        <div>
                            <small>Total</small>
                            <strong id="success-total">Rp 0</strong>
                        </div>
                    </div>

                    <div class="notice text-left">
                        Pesanan berhasil disimpan. Tunjukkan Order ID ini kepada kasir untuk konfirmasi pembayaran.
                    </div>
                    <p class="page-desc">Estimasi penyajian 10–15 menit setelah pesanan dikonfirmasi oleh kasir.</p>
                    <button class="primary success-action" data-page="tracking">Lihat Status Pesanan</button>
                </div>
            </section>

            {{-- Status pesanan --}}
            <section class="page" id="page-tracking">
                <div class="page-heading">
                    <div>
                        <div class="eyebrow">Pembaruan pesanan</div>
                        <h1>Status Pesanan</h1>
                        <p class="page-desc">Pantau tahapan pesanan Anda.</p>
                    </div>
                </div>

                <div class="panel">
                    <div class="queue">
                        <div>
                            <small>Order ID</small>
                            <strong id="track-order">#SC-000</strong>
                        </div>
                        <div>
                            <small>No. Meja</small>
                            <strong>{{ request('meja', '04') }}</strong>
                        </div>
                    </div>

                    <div class="steps">
                        <div class="step" data-track-step="1">
                            <span class="step-mark">✓</span>
                            <div>
                                <strong>Pesanan disiapkan</strong>
                                <p>Ringkasan pesanan siap ditunjukkan kepada kasir.</p>
                            </div>
                        </div>
                        <div class="step" data-track-step="2">
                            <span class="step-mark">2</span>
                            <div>
                                <strong>Menunggu konfirmasi kasir</strong>
                                <p>Kasir akan mengonfirmasi pesanan dan pembayaran Anda.</p>
                            </div>
                        </div>
                        <div class="step" data-track-step="3">
                            <span class="step-mark">3</span>
                            <div>
                                <strong>Siap diambil</strong>
                                <p>Silakan ambil pesanan di meja kasir saat sudah siap.</p>
                            </div>
                        </div>
                    </div>

                    <div class="notice" id="tracking-message" role="status" aria-live="polite">
                        Memuat status pesanan...
                    </div>
                    <div class="center">
                        <button class="secondary" data-page="menu">Kembali ke Menu</button>
                    </div>
                </div>
            </section>
        </main>

        <aside class="cartbar" id="cartbar" aria-label="Ringkasan keranjang">
            <div class="cart-info">
                <svg class="cart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2l2.2 11.2a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 1.9-1.4L22 9H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                <div>
                    <small id="cart-count">0 item dipilih</small>
                    <strong id="cart-sum">Rp 0</strong>
                </div>
            </div>
            <button class="cart-link" data-page="cart">Keranjang &nbsp;›</button>
        </aside>

        <div id="toast" class="toast" role="status" aria-live="polite"></div>
    </div>
</body>
</html>
