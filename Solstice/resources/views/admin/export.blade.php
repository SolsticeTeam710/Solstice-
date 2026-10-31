@extends('layouts.panel')
@section('title', 'Pratinjau Laporan')
@section('content')
<style>
    @media print {
        @page { size: A4; margin: 15mm; }
        .panel-sidebar, .panel-topbar, .no-print { display: none !important; }
        .panel-shell { display: block !important; min-height: 0 !important; }
        .panel-content { padding: 0 !important; }
        body { background: #fff !important; color: #1f2937 !important; }
        .report-card { border: 0 !important; box-shadow: none !important; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th, .report-table td { border-bottom: 1px solid #d1d5db; padding: 8px; text-align: left; }
    }
</style>
<div class="report-main mx-auto max-w-5xl">
    <form method="GET" action="{{ route('admin.reports.export') }}" class="no-print mb-5 flex flex-wrap items-end gap-3 rounded-xl border border-line bg-white p-4 text-xs">
        <label class="grid gap-1">Dari tanggal<input type="date" name="dari" value="{{ $start }}" required class="rounded border border-line px-3 py-2"></label>
        <label class="grid gap-1">Sampai tanggal<input type="date" name="sampai" value="{{ $end }}" required class="rounded border border-line px-3 py-2"></label>
        <button type="submit" class="rounded bg-cream px-4 py-2 font-semibold text-brand">Tampilkan Periode</button>
        <button type="button" onclick="window.print()" class="rounded bg-brand px-4 py-2 font-semibold text-white">Cetak / Simpan PDF</button>
        <a href="{{ route('admin.reports.download', ['dari' => $start, 'sampai' => $end]) }}" class="rounded border border-brand px-4 py-2 font-semibold text-brand">Unduh CSV</a>
        <p class="w-full text-muted">Saat dialog cetak terbuka, pilih tujuan <strong>Simpan sebagai PDF</strong>.</p>
    </form>

    <section class="report-card rounded-xl border border-line bg-white p-6 shadow-sm">
        <div class="mb-6 border-b border-line pb-4 text-center">
            <img src="/images/solstice-logo.jpeg" alt="Logo Solstice Coffee" class="mx-auto mb-3 h-20 w-20 object-contain" width="80" height="80">
            <p class="text-xs font-bold uppercase tracking-widest text-muted">Solstice Coffee</p>
            <p class="mt-1 text-xs text-muted">Jalan Raya Kopo No. 362B, Bojongloa Kaler, Kopo, Bandung, Jawa Barat, 40233</p>
            <h2 class="mt-1 font-display text-2xl font-bold text-brand">Laporan Penjualan</h2>
            <p class="mt-1 text-sm text-muted">Periode {{ \Carbon\Carbon::parse($start)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($end)->format('d/m/Y') }}</p>
            <p class="mt-1 text-xs text-muted">Dicetak pada {{ now()->format('d/m/Y H:i') }}</p>
        </div>

        <div class="mb-6 grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg bg-[#FAF8F5] p-4"><p class="text-xs text-muted">Total Pendapatan</p><strong class="mt-1 block text-lg text-brand">Rp {{ number_format($revenue, 0, ',', '.') }}</strong></div>
            <div class="rounded-lg bg-[#FAF8F5] p-4"><p class="text-xs text-muted">Transaksi</p><strong class="mt-1 block text-lg text-brand">{{ number_format($transactions) }}</strong></div>
            <div class="rounded-lg bg-[#FAF8F5] p-4"><p class="text-xs text-muted">Item Terjual</p><strong class="mt-1 block text-lg text-brand">{{ number_format($cups) }}</strong></div>
        </div>

        <h3 class="mb-3 font-display text-lg font-bold text-brand">Rincian Transaksi</h3>
        <div class="overflow-x-auto">
            <table class="report-table w-full text-left text-xs">
                <thead><tr class="border-b border-line text-muted"><th class="py-2 pr-3">No. Pesanan</th><th class="py-2 pr-3">Tanggal</th><th class="py-2 pr-3">Pembayaran</th><th class="py-2 text-right">Total</th></tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="border-b border-line"><td class="py-2 pr-3 font-semibold">{{ $row->order_id }}</td><td class="py-2 pr-3">{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y H:i') }}</td><td class="py-2 pr-3">{{ $row->metode_pembayaran }}</td><td class="py-2 text-right">Rp {{ number_format($row->total, 0, ',', '.') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-muted">Tidak ada transaksi pada periode ini.</td></tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())<tfoot><tr class="font-bold"><td colspan="3" class="py-3 text-right">Total pendapatan</td><td class="py-3 text-right">Rp {{ number_format($revenue, 0, ',', '.') }}</td></tr></tfoot>@endif
            </table>
        </div>
    </section>
</div>
@endsection
