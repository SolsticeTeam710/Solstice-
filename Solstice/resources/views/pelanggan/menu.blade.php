<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pesan di Meja {{ request('meja', '04') }} — Solstice Coffe</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/pelanggan.css', 'resources/js/pelanggan.js'])
</head>
<body>
    <div class="app">
        <header class="top">
            <div class="brand">
                <img class="logo" src="{{ asset('images/solstice-logo.jpeg') }}" alt="Solstice Coffe">
                <div>
                    <div class="brand-name">Solstice Coffe</div>
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

                <button id="track-last-order" type="button" hidden>
                    Lacak Pesanan Terakhir
                </button>

                <label class="search">
                    <span aria-hidden="true">⌕</span>
                    <input id="menu-search" type="search" placeholder="Cari kopi atau makanan..." aria-label="Cari menu">
                </label>

                <div class="filters" id="category-filters" aria-label="Filter kategori menu">
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
                            <div class="payment-options">
                                <button type="button" class="payment-option active" data-payment="CASH">
                                    <strong>💵 &nbsp; Tunai</strong>
                                    <small>Bayar di kasir</small>
                                </button>
                                <button type="button" class="payment-option" data-payment="QRIS">
                                    <strong>▦ &nbsp; QRIS</strong>
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
                    <div class="eyebrow">Pratinjau pesanan</div>
                    <h1 class="success-title">Terima kasih, <span id="success-name">Teman</span>!</h1>
                    <p class="page-desc">Ringkasan pesanan Anda siap ditunjukkan kepada kasir.</p>
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
                <span class="cart-icon" aria-hidden="true">▣</span>
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
