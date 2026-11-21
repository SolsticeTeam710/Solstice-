<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function dashboard()
    {
        $firstDay = Carbon::today()->subMonths(2);
        $today = Carbon::today();
        $version = Cache::get('admin.metrics.version', '0');
        $metrics = Cache::remember("admin.dashboard.metrics.{$version}", now()->addSeconds(15), function () use ($firstDay, $today) {
            $dailySales = DB::table('pembayaran')->select(DB::raw('DATE(tanggal) as hari'),DB::raw('SUM(total) as total'))
                ->where('tanggal', '>=', $firstDay->copy()->startOfDay())
                ->where('tanggal', '<', $today->copy()->addDay()->startOfDay())
                ->groupBy(DB::raw('DATE(tanggal)'))->pluck('total','hari')->all();
            $menuStats = [
                'total' => DB::table('menu')->count(),
                'active' => DB::table('menu')->where('is_active', true)->count(),
                'low_stock' => DB::table('menu')
                    ->whereRaw('COALESCE(stok, 0) <= COALESCE(batas_minimum, 0)')
                    ->count(),
            ];
            $userStats = DB::table('users')
                ->selectRaw(
                    "COUNT(*) AS total, SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS admins, "
                    . "SUM(CASE WHEN role = 'kasir' THEN 1 ELSE 0 END) AS cashiers"
                )
                ->first();

            return [
                'daily_sales' => $dailySales,
                'menu_stats' => (array) $menuStats,
                'user_stats' => (array) $userStats,
            ];
        });
        $dailySales = $metrics['daily_sales'];
        $weekly = [];
        for ($weekStart = $firstDay->copy(); $weekStart->lte($today); $weekStart->addDays(7)) {
            $weekEnd = $weekStart->copy()->addDays(6);
            if ($weekEnd->gt($today)) {
                $weekEnd = $today->copy();
            }

            $weekTotal = 0.0;
            for ($day = $weekStart->copy(); $day->lte($weekEnd); $day->addDay()) {
                $weekTotal += (float) ($dailySales[$day->toDateString()] ?? 0);
            }

            $weekly[] = [
                $weekStart->format('d M') . '–' . $weekEnd->format('d M'),
                $weekTotal / 1000000,
            ];
        }
        $todayRevenue = (float) ($dailySales[$today->toDateString()] ?? 0);
        $menuStats = $metrics['menu_stats'];
        $userStats = $metrics['user_stats'];
        $menuTotal = (int) ($menuStats['total'] ?? 0);
        $activeMenus = (int) ($menuStats['active'] ?? 0);
        $lowStock = (int) ($menuStats['low_stock'] ?? 0);
        $userTotal = (int) ($userStats['total'] ?? 0);
        $adminTotal = (int) ($userStats['admins'] ?? 0);
        $cashierTotal = (int) ($userStats['cashiers'] ?? 0);
        $stats = [
            ['Total Varian Menu', $menuTotal.' Menu', 'text-brand', $activeMenus.' menu aktif', 'text-ok', false],
            ['Total Pengguna (Staf)', $userTotal.' User', 'text-brand', $adminTotal.' Admin · '.$cashierTotal.' Kasir', 'text-muted', false],
            ['Penjualan Hari Ini', 'Rp '.number_format($todayRevenue,0,',','.'), 'text-brand', 'Total pembayaran hari ini', 'text-ok', false],
            ['Stok Menipis', $lowStock.' Menu', 'text-warn', $lowStock ? 'Perlu diperiksa' : 'Semua stok aman', 'text-muted', true],
        ];
        $logs = [
            ['Menu dan kategori dikelola', 'Hari ini', 'Data menu sekarang tersimpan ke database'],
            ['Pembaruan stok', 'Hari ini', 'Periksa halaman Kelola Stok untuk melihat menu dengan stok kritis'],
        ];
        return view('admin.dashboard', compact('weekly', 'logs', 'stats'));
    }

    public function dashboardData()
    {
        return response()->json([
            'menu' => DB::table('menu')->count(),
            'pengguna' => DB::table('users')->count(),
            'stok_menipis' => DB::table('menu')->whereRaw('COALESCE(stok, 0) <= COALESCE(batas_minimum, 0)')->count(),
        ]);
    }

    public function menus(Request $request)
    {
        $categories = DB::table('kategori')->orderBy('nama_kategori')->get()->map(function ($category) {
            $category->display_name = $this->categoryLabel($category->nama_kategori);
            return $category;
        });
        $query = DB::table('menu')->leftJoin('kategori', 'menu.id_kategori', '=', 'kategori.id_kategori')
            ->select('menu.*', 'kategori.nama_kategori');
        if ($request->filled('kategori')) {
            $category = mb_strtolower($request->query('kategori'));
            $aliases = match ($category) {
                'kopi', 'coffee' => ['kopi', 'coffee'],
                'non-kopi', 'non-coffee', 'non coffee' => ['non-kopi', 'non-coffee', 'non coffee'],
                default => [$category],
            };
            $query->whereIn(DB::raw('LOWER(kategori.nama_kategori)'), $aliases);
        }
        $menus = $query->orderBy('menu.nama_menu')->get()->map(fn ($item) => [
            'id' => $item->id_menu, 'nama' => $item->nama_menu, 'kategori_id' => $item->id_kategori,
            'foto' => $item->foto,
            'foto_url' => $this->menuPhotoUrl($item->foto, $item->nama_menu),
            'kategori' => $item->nama_kategori
                ? $this->categoryLabel($item->nama_kategori)
                : 'Tanpa kategori',
            'harga' => (float) $item->harga,
            'stok' => $item->stok,
            'minimum' => $item->batas_minimum,
            'deskripsi' => $item->deskripsi,
            'aktif' => (bool) $item->is_active,
            'status_tampilan' => ! (bool) $item->is_active
                ? 'Tidak tersedia'
                : (((int) $item->stok <= 0 || $item->status === 'habis') ? 'Habis' : 'Tersedia'),
        ])->all();
        return view('admin.menus', compact('menus', 'categories'));
    }

    public function storeMenu(Request $request)
    {
        $data = $request->validate([
            'nama_menu' => ['required','string','max:150'],
            'id_kategori' => ['required','integer',Rule::exists('kategori','id_kategori')],
            'harga' => ['required','numeric','min:0'], 'stok' => ['required','integer','min:0'],
            'batas_minimum' => ['nullable','integer','min:0'], 'deskripsi' => ['nullable','string','max:2000'],
            'foto' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ]);
        $data['batas_minimum'] = $data['batas_minimum'] ?? 0;
        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('menu', 'public');
        } else {
            unset($data['foto']);
        }
        DB::table('menu')->insert($data + [
            'status' => $data['stok'] > 0 ? 'tersedia' : 'habis',
            'is_active' => true,
        ]);
        $this->invalidateAdminMetrics();
        return back()->with('success', 'Menu berhasil ditambahkan.');
    }

    public function updateMenu(Request $request, int $id)
    {
        $data = $request->validate([
            'nama_menu' => ['required','string','max:150'],
            'id_kategori' => ['required','integer',Rule::exists('kategori','id_kategori')],
            'harga' => ['required','numeric','min:0'], 'stok' => ['required','integer','min:0'],
            'batas_minimum' => ['nullable','integer','min:0'], 'deskripsi' => ['nullable','string','max:2000'],
            'foto' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ]);

        $existingMenu = DB::table('menu')->where('id_menu', $id)->first();
        if (! $existingMenu) {
            return back()->with('error', 'Menu tidak ditemukan.');
        }

        $data['batas_minimum'] = $data['batas_minimum'] ?? 0;
        if ($request->hasFile('foto')) {
            $newPhotoPath = $request->file('foto')->store('menu', 'public');
            if ($newPhotoPath) {
                $data['foto'] = $newPhotoPath;
            }
        } else {
            unset($data['foto']);
        }
        DB::table('menu')->where('id_menu', $id)->update($data + ['status' => $data['stok'] > 0 ? 'tersedia' : 'habis']);
        if (! empty($newPhotoPath) && is_string($existingMenu->foto) && str_starts_with($existingMenu->foto, 'menu/')) {
            Storage::disk('public')->delete($existingMenu->foto);
        }
        $this->invalidateAdminMetrics();

        return back()->with('success', 'Menu berhasil diperbarui.');
    }

    public function toggleMenuStatus(int $id)
    {
        $menu = DB::table('menu')->where('id_menu', $id)->first();
        if (! $menu) {
            return back()->with('error', 'Menu tidak ditemukan.');
        }

        $isActive = ! (bool) $menu->is_active;
        DB::table('menu')->where('id_menu', $id)->update(['is_active' => $isActive]);
        $this->invalidateAdminMetrics();

        return back()->with('success', $isActive
            ? 'Menu berhasil diaktifkan.'
            : 'Menu berhasil dinonaktifkan.');
    }

    public function deleteMenu(int $id)
    {
        if (Schema::hasTable('detail_pesanan') && DB::table('detail_pesanan')->where('id_menu', $id)->exists()) {
            return back()->with('error', 'Menu sudah tercatat di pesanan, jadi tidak dapat dihapus.');
        }
        DB::table('menu')->where('id_menu', $id)->delete();
        $this->invalidateAdminMetrics();
        return back()->with('success', 'Menu berhasil dihapus.');
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['nama_kategori' => ['required','string','max:100']]);
        if (! DB::table('kategori')->whereRaw('LOWER(nama_kategori) = ?', [mb_strtolower($data['nama_kategori'])])->exists()) {
            DB::table('kategori')->insert($data);
        }
        return back()->with('success', 'Kategori siap digunakan.');
    }

    public function deleteCategory(int $id)
    {
        $category = DB::table('kategori')->where('id_kategori', $id)->first();
        if (! $category) {
            return back()->with('error', 'Kategori tidak ditemukan.');
        }

        if (DB::table('menu')->where('id_kategori', $id)->exists()) {
            return back()->with('error', 'Kategori masih digunakan oleh menu. Pindahkan menu ke kategori lain terlebih dahulu.');
        }

        DB::table('kategori')->where('id_kategori', $id)->delete();

        return redirect()->route('admin.menus')->with('success', 'Kategori berhasil dihapus.');
    }

    public function usersPage()
    {
        // Use the selected user rows to detect optional fields instead of making
        // a separate database schema query on every page visit.
        $users = DB::table('users')
            ->whereIn('role', ['admin', 'kasir'])
            ->orderBy('username')
            ->get()
            ->map(function ($user) {
                $active = property_exists($user, 'is_active')
                    ? (bool) $user->is_active
                    : in_array(mb_strtolower((string) ($user->status ?? 'aktif')), ['aktif', 'active'], true);

                return [
                    'id' => $user->id_users,
                    'nama' => property_exists($user, 'name') && $user->name ? $user->name : $user->username,
                    'username' => $user->username,
                    'email' => property_exists($user, 'email') ? ($user->email ?? '') : '',
                    'jabatan' => ucfirst($user->role),
                    'role' => $user->role,
                    'aktif' => $active,
                ];
            })
            ->all();

        return view('admin.users', compact('users'));
    }

    public function storeUser(Request $request)
    {
        $emailRules = Schema::hasColumn('users','email') ? ['nullable','email','max:190','unique:users,email'] : ['nullable','email','max:190'];
        $data = $request->validate([
            'name'=>['nullable','string','max:150'], 'username'=>['required','string','max:80','unique:users,username'],
            'email'=>$emailRules, 'role'=>['required',Rule::in(['admin','kasir'])], 'password'=>['required','string','min:6'],
        ]);
        $data['password'] = Hash::make($data['password']);
        if (Schema::hasColumn('users','status')) $data['status'] = 'aktif';
        if (Schema::hasColumn('users','is_active')) $data['is_active'] = true;
        $this->insertSupportedUserColumns($data);
        $this->invalidateAdminMetrics();
        return back()->with('success', 'Akun staf berhasil ditambahkan.');
    }

    public function updateUser(Request $request, int $id)
    {
        $emailRules = Schema::hasColumn('users','email') ? ['nullable','email','max:190',Rule::unique('users','email')->ignore($id,'id_users')] : ['nullable','email','max:190'];
        $data = $request->validate([
            'name'=>['nullable','string','max:150'], 'username'=>['required','string','max:80',Rule::unique('users','username')->ignore($id,'id_users')],
            'email'=>$emailRules, 'role'=>['required',Rule::in(['admin','kasir'])], 'password'=>['nullable','string','min:6'],
        ]);
        if (blank($data['password'] ?? null)) unset($data['password']); else $data['password'] = Hash::make($data['password']);
        $this->updateSupportedUserColumns($id, $data);
        $this->invalidateAdminMetrics();
        return back()->with('success', 'Akun staf berhasil diperbarui.');
    }

    public function deleteUser(int $id)
    {
        $target = DB::table('users')->where('id_users', $id)->first();
        if (! $target) return back()->with('error', 'Akun tidak ditemukan.');
        if ((int) Auth::id() === $id) return back()->with('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        if ($target->role === 'admin' && DB::table('users')->where('role','admin')->count() <= 1) return back()->with('error', 'Tidak dapat menghapus satu-satunya admin.');
        if (Schema::hasTable('pesanan') && DB::table('pesanan')->where('id_users',$id)->exists()) return back()->with('error', 'Akun ini memiliki riwayat pesanan, jadi tidak dapat dihapus. Nonaktifkan akunnya saja.');
        DB::table('users')->where('id_users', $id)->delete();
        $this->invalidateAdminMetrics();
        return back()->with('success', 'Akun staf berhasil dihapus.');
    }

    public function toggleUserStatus(int $id)
    {
        $user = DB::table('users')->where('id_users',$id)->first();
        if (! $user) return back()->with('error','Akun tidak ditemukan.');
        if ((int) Auth::id() === $id) return back()->with('error', 'Status akun yang sedang digunakan tidak dapat diubah.');
        $columns = Schema::getColumnListing('users');
        $isActive = in_array('is_active',$columns)
            ? (bool) $user->is_active
            : in_array(mb_strtolower((string) ($user->status ?? 'aktif')), ['aktif', 'active'], true);
        $values = [];
        if (in_array('is_active',$columns)) $values['is_active'] = ! $isActive;
        if (in_array('status',$columns)) $values['status'] = $isActive ? 'nonaktif' : 'aktif';
        if (in_array('updated_at',$columns)) $values['updated_at'] = now();
        DB::table('users')->where('id_users',$id)->update($values);
        $this->invalidateAdminMetrics();
        return back()->with('success','Status pengguna berhasil diubah.');
    }

    private function insertSupportedUserColumns(array $data): void
    {
        DB::table('users')->insert(array_intersect_key($data, array_flip(Schema::getColumnListing('users'))) + ['created_at'=>now()]);
    }

    private function updateSupportedUserColumns(int $id, array $data): void
    {
        $row = array_intersect_key($data, array_flip(Schema::getColumnListing('users')));
        if (Schema::hasColumn('users','updated_at')) $row['updated_at'] = now();
        DB::table('users')->where('id_users',$id)->update($row);
    }

    public function stockPage(Request $request)
    {
        $stockCategories = DB::table('kategori')
            ->orderBy('nama_kategori')
            ->pluck('nama_kategori');

        $query = DB::table('menu')
            ->leftJoin('kategori', 'menu.id_kategori', '=', 'kategori.id_kategori')
            ->select('menu.*', 'kategori.nama_kategori');

        if ($request->filled('kategori')) {
            $query->where('kategori.nama_kategori', $request->query('kategori'));
        }

        $stocks = $query->orderBy('menu.nama_menu')->get()->map(fn ($item) => [
            'id' => $item->id_menu,
            'nama' => $item->nama_menu,
            'kategori' => $item->nama_kategori ?? 'Tanpa kategori',
            'jumlah' => ($item->stok ?? 0) . ' item',
            'stok' => $item->stok ?? 0,
            'satuan' => 'item',
            'minimum' => ($item->batas_minimum ?? 0) . ' item',
            'stok_minimum' => $item->batas_minimum ?? 0,
            'status' => ($item->stok ?? 0) <= 0
                ? 'Habis'
                : (($item->stok ?? 0) <= ($item->batas_minimum ?? 0) ? 'Menipis' : 'Aman'),
        ])->all();

        return view('admin.stock', compact('stocks', 'stockCategories'));
    }

    public function updateStock(Request $request, int $id)
    {
        $data = $request->validate([
            'stok' => ['required', 'integer', 'min:0'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
        ]);

        $menu = DB::table('menu')->where('id_menu', $id)->first();
        if (! $menu) {
            return back()->with('error', 'Menu tidak ditemukan.');
        }

        DB::table('menu')->where('id_menu', $id)->update([
            'stok' => $data['stok'],
            'batas_minimum' => $data['stok_minimum'],
            'status' => $data['stok'] > 0 ? 'tersedia' : 'habis',
        ]);
        $this->invalidateAdminMetrics();

        return back()->with('success', 'Stok menu berhasil diperbarui.');
    }

    public function criticalStockPage()
    {
        $critical = DB::table('menu')
            ->leftJoin('kategori', 'menu.id_kategori', '=', 'kategori.id_kategori')
            ->select('menu.*', 'kategori.nama_kategori')
            ->whereColumn('menu.stok', '<=', 'menu.batas_minimum')
            ->orderBy('menu.stok')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id_menu,
                'nama' => $item->nama_menu,
                'jumlah' => ($item->stok ?? 0) . ' item',
                'minimum' => ($item->batas_minimum ?? 0) . ' item',
                'status' => ($item->stok ?? 0) <= 0 ? 'Habis' : 'Menipis',
                'kategori' => $item->nama_kategori ?? 'Tanpa kategori',
            ])->all();

        return view('admin.critical-stock', compact('critical'));
    }

    public function reportsPage(Request $request)
    {
        $data = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'metode_pembayaran' => ['nullable', Rule::in(['CASH', 'QRIS'])],
        ]);

        $start = $data['dari'] ?? now()->subMonths(2)->toDateString();
        $end = $data['sampai'] ?? now()->toDateString();
        $method = $data['metode_pembayaran'] ?? null;
        $startAt = Carbon::parse($start)->startOfDay();
        $endExclusive = Carbon::parse($end)->addDay()->startOfDay();

        $version = Cache::get('admin.metrics.version', '0');
        $cacheKey = 'admin.reports.' . $version . '.' . md5($start . '|' . $end . '|' . ($method ?? 'ALL'));
        $report = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($startAt, $endExclusive, $method) {
            $paymentQuery = DB::table('pembayaran')
                ->where('tanggal', '>=', $startAt)
                ->where('tanggal', '<', $endExclusive);
            if ($method) {
                $paymentQuery->where('metode_pembayaran', $method);
            }

            $summary = (clone $paymentQuery)
                ->selectRaw('COALESCE(SUM(total), 0) AS revenue, COUNT(DISTINCT id_pesanan) AS transactions')
                ->first();

            $cupsQuery = DB::table('detail_pesanan')
                ->join('pesanan', 'detail_pesanan.id_pesanan', '=', 'pesanan.id_pesanan')
                ->join('pembayaran', 'pembayaran.id_pesanan', '=', 'pesanan.id_pesanan')
                ->where('pembayaran.tanggal', '>=', $startAt)
                ->where('pembayaran.tanggal', '<', $endExclusive);
            if ($method) {
                $cupsQuery->where('pembayaran.metode_pembayaran', $method);
            }
            $cups = (int) $cupsQuery->sum('detail_pesanan.jumlah_pesanan');

            $topQuery = DB::table('detail_pesanan')
                ->join('pesanan', 'detail_pesanan.id_pesanan', '=', 'pesanan.id_pesanan')
                ->join('pembayaran', 'pembayaran.id_pesanan', '=', 'pesanan.id_pesanan')
                ->join('menu', 'detail_pesanan.id_menu', '=', 'menu.id_menu')
                ->leftJoin('kategori', 'menu.id_kategori', '=', 'kategori.id_kategori')
                ->where('pembayaran.tanggal', '>=', $startAt)
                ->where('pembayaran.tanggal', '<', $endExclusive);
            if ($method) {
                $topQuery->where('pembayaran.metode_pembayaran', $method);
            }
            $top = $topQuery
                ->select(
                    'menu.nama_menu',
                    'kategori.nama_kategori',
                    DB::raw('SUM(detail_pesanan.jumlah_pesanan) as terjual'),
                    DB::raw('SUM(detail_pesanan.subtotal) as omzet')
                )
                ->groupBy('menu.nama_menu', 'kategori.nama_kategori')
                ->orderByDesc('terjual')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'nama' => $row->nama_menu,
                    'kategori' => $row->nama_kategori ?? 'Tanpa kategori',
                    'terjual' => (int) $row->terjual,
                    'omzet' => (float) $row->omzet,
                ])
                ->all();

            $dailyRows = (clone $paymentQuery)
                ->select(DB::raw('DATE(tanggal) as hari'), DB::raw('SUM(total) as total'))
                ->groupBy(DB::raw('DATE(tanggal)'))
                ->orderBy('hari')
                ->get();
            $dailyTotals = [];
            foreach ($dailyRows as $row) {
                $dailyTotals[(string) $row->hari] = (float) $row->total;
            }

            $lastDate = $endExclusive->copy()->subDay();
            $weekly = [];
            for ($bucketStart = $startAt->copy(); $bucketStart->lte($lastDate); $bucketStart->addDays(7)) {
                $bucketEnd = $bucketStart->copy()->addDays(6);
                if ($bucketEnd->gt($lastDate)) {
                    $bucketEnd = $lastDate->copy();
                }

                $bucketTotal = 0.0;
                for ($day = $bucketStart->copy(); $day->lte($bucketEnd); $day->addDay()) {
                    $bucketTotal += $dailyTotals[$day->toDateString()] ?? 0;
                }

                $weekly[] = [
                    $bucketStart->format('d/m') . '–' . $bucketEnd->format('d/m'),
                    $bucketTotal,
                ];
            }

            $methodRows = DB::table('pembayaran')
                ->where('tanggal', '>=', $startAt)
                ->where('tanggal', '<', $endExclusive)
                ->select(
                    'metode_pembayaran',
                    DB::raw('COUNT(*) as transactions'),
                    DB::raw('COALESCE(SUM(total), 0) as revenue')
                )
                ->groupBy('metode_pembayaran')
                ->get();
            $paymentSummary = [
                'CASH' => ['transactions' => 0, 'revenue' => 0.0],
                'QRIS' => ['transactions' => 0, 'revenue' => 0.0],
            ];
            foreach ($methodRows as $row) {
                $key = strtoupper((string) $row->metode_pembayaran);
                $paymentSummary[$key] = [
                    'transactions' => (int) $row->transactions,
                    'revenue' => (float) $row->revenue,
                ];
            }

            return [
                'revenue' => (float) ($summary->revenue ?? 0),
                'transactions' => (int) ($summary->transactions ?? 0),
                'cups' => $cups,
                'top' => $top,
                'weekly' => $weekly,
                'payment_summary' => $paymentSummary,
            ];
        });

        $revenue = $report['revenue'];
        $transactions = $report['transactions'];
        $cups = $report['cups'];
        $popular = $report['top'];
        $weekly = $report['weekly'];
        $paymentSummary = $report['payment_summary'];
        $dailyAverage = $transactions
            ? $revenue / max(1, Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1)
            : 0;

        return view('admin.reports', compact(
            'weekly',
            'popular',
            'revenue',
            'transactions',
            'cups',
            'start',
            'end',
            'dailyAverage',
            'method',
            'paymentSummary'
        ));
    }

    private function invalidateAdminMetrics(): void
    {
        Cache::forever('admin.metrics.version', (string) Str::uuid());
    }

    private function reportOrderNumber(string $orderId): string
    {
        if (! str_starts_with($orderId, 'DEMO-TREND-')) {
            return $orderId;
        }

        return 'ORD-' . strtoupper(substr(hash('sha256', $orderId), 0, 12));
    }
    private function paymentSummaryForPeriod(string $start, string $end): array
    {
        $summary = [
            'CASH' => ['transactions' => 0, 'revenue' => 0.0],
            'QRIS' => ['transactions' => 0, 'revenue' => 0.0],
        ];

        $rows = DB::table('pembayaran')
            ->whereBetween(DB::raw('DATE(tanggal)'), [$start, $end])
            ->select(
                'metode_pembayaran',
                DB::raw('COUNT(*) as transactions'),
                DB::raw('COALESCE(SUM(total), 0) as revenue')
            )
            ->groupBy('metode_pembayaran')
            ->get();

        foreach ($rows as $row) {
            $key = strtoupper(trim((string) $row->metode_pembayaran));
            if (! array_key_exists($key, $summary)) {
                continue;
            }

            $summary[$key] = [
                'transactions' => (int) $row->transactions,
                'revenue' => (float) $row->revenue,
            ];
        }

        return $summary;
    }
    public function downloadReport(Request $request)
    {
        $data = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'metode_pembayaran' => ['nullable', Rule::in(['CASH', 'QRIS'])],
        ]);
        $start = $data['dari'] ?? now()->subMonths(2)->toDateString();
        $end = $data['sampai'] ?? now()->toDateString();
        $method = $data['metode_pembayaran'] ?? null;

        $query = DB::table('pembayaran')
            ->join('pesanan', 'pembayaran.id_pesanan', '=', 'pesanan.id_pesanan')
            ->whereBetween(DB::raw('DATE(pembayaran.tanggal)'), [$start, $end]);
        if ($method) {
            $query->where('pembayaran.metode_pembayaran', $method);
        }
        $rows = $query
            ->select('pesanan.order_id', 'pesanan.tanggal', 'pembayaran.metode_pembayaran', 'pembayaran.total')
            ->orderBy('pembayaran.tanggal')
            ->get()
            ->map(function ($row) {
                $row->order_id = $this->reportOrderNumber((string) $row->order_id);
                return $row;
            });
        $paymentSummary = $this->paymentSummaryForPeriod($start, $end);
        $revenue = (float) $rows->sum('total');
        $transactions = $rows->count();
        $methodLabel = $method === 'CASH' ? 'Tunai' : ($method ?: 'Semua metode');

        return response()->streamDownload(function () use ($rows, $paymentSummary, $start, $end, $revenue, $transactions, $methodLabel) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Laporan Penjualan Solstice Coffee']);
            fputcsv($out, ['Periode', $start, $end]);
            fputcsv($out, ['Filter metode', $methodLabel]);
            fputcsv($out, []);
            fputcsv($out, ['Ringkasan Metode Pembayaran']);
            fputcsv($out, ['Metode Pembayaran', 'Jumlah Transaksi', 'Total Pendapatan']);
            foreach ($paymentSummary as $key => $summary) {
                $label = $key === 'CASH' ? 'Tunai' : $key;
                fputcsv($out, [$label, $summary['transactions'], $summary['revenue']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Total Pendapatan sesuai filter', $revenue]);
            fputcsv($out, ['Jumlah Transaksi sesuai filter', $transactions]);
            fputcsv($out, []);
            fputcsv($out, ['Rincian Transaksi']);
            fputcsv($out, ['No. Pesanan', 'Tanggal', 'Metode Pembayaran', 'Total']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->order_id, $row->tanggal, $row->metode_pembayaran, $row->total]);
            }
            fclose($out);
        }, 'laporan-penjualan.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPage(Request $request)
    {
        $data = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'metode_pembayaran' => ['nullable', Rule::in(['CASH', 'QRIS'])],
        ]);

        $start = $data['dari'] ?? now()->subMonths(2)->toDateString();
        $end = $data['sampai'] ?? now()->toDateString();
        $method = $data['metode_pembayaran'] ?? null;

        $rowsQuery = DB::table('pembayaran')
            ->join('pesanan', 'pembayaran.id_pesanan', '=', 'pesanan.id_pesanan')
            ->whereBetween(DB::raw('DATE(pembayaran.tanggal)'), [$start, $end]);
        if ($method) {
            $rowsQuery->where('pembayaran.metode_pembayaran', $method);
        }
        $rows = $rowsQuery
            ->select('pesanan.order_id', 'pesanan.tanggal', 'pembayaran.metode_pembayaran', 'pembayaran.total')
            ->orderBy('pembayaran.tanggal')
            ->get()
            ->map(function ($row) {
                $row->order_id = $this->reportOrderNumber((string) $row->order_id);
                return $row;
            });
        $paymentSummary = $this->paymentSummaryForPeriod($start, $end);

        $revenue = (float) $rows->sum('total');
        $transactions = $rows->count();

        $cupsQuery = DB::table('detail_pesanan')
            ->join('pesanan', 'detail_pesanan.id_pesanan', '=', 'pesanan.id_pesanan')
            ->join('pembayaran', 'pembayaran.id_pesanan', '=', 'pesanan.id_pesanan')
            ->whereBetween(DB::raw('DATE(pembayaran.tanggal)'), [$start, $end]);
        if ($method) {
            $cupsQuery->where('pembayaran.metode_pembayaran', $method);
        }
        $cups = (int) $cupsQuery->sum('detail_pesanan.jumlah_pesanan');

        return view('admin.export', compact('start', 'end', 'rows', 'revenue', 'transactions', 'cups', 'method', 'paymentSummary'));
    }

    public function users() { $columns = array_values(array_diff(Schema::getColumnListing('users'),['password','remember_token'])); return response()->json(DB::table('users')->select($columns)->get()); }
    public function menu()
    {
        return response()->json(
            DB::table('menu')
                ->leftJoin('kategori', 'menu.id_kategori', '=', 'kategori.id_kategori')
                ->select('menu.*', 'kategori.nama_kategori')
                ->get()
        );
    }
    public function kategori() { return response()->json(DB::table('kategori')->orderBy('nama_kategori')->get()); }
    public function stok() { return response()->json(DB::table('menu')->select('id_menu', 'nama_menu', 'stok', 'batas_minimum', 'status')->get()); }
    public function stokMenipis() { return response()->json(DB::table('menu')->select('id_menu', 'nama_menu', 'stok', 'batas_minimum', 'status')->whereColumn('stok', '<=', 'batas_minimum')->get()); }
    public function laporan() { return response()->json(['pendapatan'=>DB::table('pembayaran')->sum('total'),'transaksi'=>DB::table('pembayaran')->distinct('id_pesanan')->count('id_pesanan')]); }

    private function menuPhotoUrl(?string $photo, string $name): ?string
    {
        if ($photo) {
            $path = str_replace('\\', '/', $photo);
            if (preg_match('~^https?://~i', $path)) {
                return $path;
            }

            $path = ltrim((string) preg_replace('~^public/~i', '', $path), '/');
            return str_starts_with($path, 'menu/') || str_starts_with($path, 'storage/')
                ? asset('storage/' . ltrim(substr($path, str_starts_with($path, 'storage/') ? 8 : 0), '/'))
                : asset($path);
        }

        $name = mb_strtolower($name);
        $images = [
            ['ice kopi susu aren', 'images/menu/Ice%20Kopi%20Susu%20Aren.jpeg'],
            ['ice caramel macchiato', 'images/menu/Ice%20Caramel%20Macchiato.jpeg'],
            ['iced caramel macchiato', 'images/menu/Ice%20Caramel%20Macchiato.jpeg'],
            ['matcha latte', 'images/menu/matcha-latte.jpg'],
            ['almond croissant', 'images/menu/Almond%20Croissant.jpg'],
            ['nasi goreng', 'images/menu/Nasi%20Goreng.jpeg'],
            ['air dingin', 'images/menu/Air%20Dingin.jpeg'],
            ['ayam goreng', 'images/menu/ayam-goreng.jpg'],
            ['caffe latte', 'images/menu/caffe-latte.jpg'],
            ['cafe latte', 'images/menu/caffe-latte.jpg'],
            ['kwetiaw', 'images/menu/Mie%20Kwetiaw.jpeg'],
            ['kwetiau', 'images/menu/Mie%20Kwetiaw.jpeg'],
        ];

        foreach ($images as [$match, $image]) {
            if (str_contains($name, $match)) {
                return asset($image);
            }
        }

        return null;
    }
    private function categoryLabel(string $name): string
    {
        return match (mb_strtolower($name)) {
            'coffee' => 'Kopi',
            'non-coffee', 'non coffee' => 'Non-Kopi',
            default => $name,
        };
    }
}
