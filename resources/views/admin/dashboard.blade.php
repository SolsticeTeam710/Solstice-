@extends('layouts.panel')
@section('title', 'Dashboard')
@section('content')
@php($max = max(array_column($weekly, 1)) ?: 1)
<div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($stats as [$label, $value, $valColor, $note, $noteColor, $warn])
        @if ($warn)
            <a href="{{ route('admin.stock.critical') }}" aria-label="Buka peringatan stok menu" class="group block rounded-xl border border-brand bg-orange-50 p-4 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <span class="text-xs text-muted">{{ $label }}</span>
                <div class="my-1 font-display text-2xl font-extrabold {{ $valColor }}">{{ $value }}</div>
                <small class="text-[10px] {{ $noteColor }}">{{ $note }} <span class="font-semibold text-brand group-hover:underline">· Lihat peringatan →</span></small>
            </a>
        @else
            <article class="rounded-xl border border-line bg-white p-4"><span class="text-xs text-muted">{{ $label }}</span><div class="my-1 font-display text-2xl font-extrabold {{ $valColor }}">{{ $value }}</div><small class="text-[10px] {{ $noteColor }}">{{ $note }}</small></article>
        @endif
    @endforeach
</div>
<div class="grid grid-cols-1 items-start gap-3 xl:grid-cols-[3fr_2fr]">
    <article class="rounded-xl border border-line bg-white p-4"><h2 class="mb-4 font-display text-sm font-bold text-brand">Tren Penjualan 2 Bulan Terakhir</h2><div class="flex h-56 items-end justify-between gap-3 px-2">@foreach ($weekly as [$day, $value])<div class="flex h-full flex-1 flex-col items-center justify-end gap-2" title="{{ $day }}: Rp {{ number_format($value * 1000000, 0, ',', '.') }}"><div class="w-full max-w-6 rounded-t bg-brand transition hover:bg-brand-dark" style="height: {{ $value > 0 ? max(3,round($value / $max * 100)) : 0 }}%"></div><span class="text-[9px] text-muted">{{ $day }}</span></div>@endforeach</div></article>
    <article class="rounded-xl border border-line bg-white p-4"><h2 class="mb-3 font-display text-sm font-bold text-brand">Log Aktivitas Terbaru</h2><div class="flex flex-col gap-2">@foreach ($logs as [$title, $time, $desc])<div class="rounded-lg bg-[#FAF8F5] px-3 py-2.5"><strong class="flex items-center justify-between text-[11px]">{{ $title }}<em class="text-[9px] font-normal not-italic text-muted">{{ $time }}</em></strong><span class="text-[10px] text-muted">{{ $desc }}</span></div>@endforeach</div></article>
</div>
@endsection
