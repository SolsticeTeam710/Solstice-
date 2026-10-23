<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    private array $defaultCategories = ['Coffee', 'Non-Coffee', 'Makanan'];

    public function dashboard()
    {
        $labels = ['Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab','Sun'=>'Min'];
        $firstDay = Carbon::today()->subDays(6);
        $today = Carbon::today();
        $version = Cache::get('admin.metrics.version', '0');
        $metrics = Cache::remember("admin.dashboard.metrics.{$version}", now()->addSeconds(15), function () use ($firstDay, $today) {
            $dailySales = DB::table('pembayaran')->select(DB::raw('DATE(tanggal) as hari'),DB::raw('SUM(total) as total'))
                ->where('tanggal', '>=', $firstDay->copy()->startOfDay())
                ->where('tanggal', '<', $today->copy()->addDay()->startOfDay())
                ->groupBy(DB::raw('DATE(tanggal)'))->pluck('total','hari')->all();
            $menuStats = DB::table('menu')
                ->selectRaw(
                    'COUNT(*) AS total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS active, '
                    . 'SUM(CASE WHEN COALESCE(stok, 0) <= COALESCE(batas_minimum, 0) THEN 1 ELSE 0 END) AS low_stock',
                    ['tersedia']
                )
                ->first();
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
        for ($day = $firstDay->copy(); $day->lte($today); $day->addDay()) {
            $weekly[] = [$labels[$day->format('D')], ((float)($dailySales[$day->toDateString()] ?? 0)) / 1000000];
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
            ['Total Varian Menu', $menuTotal.' Menu', 'text-brand', $activeMenus.' menu tersedia', 'text-ok', false],
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
        $this->ensureDefaultCategories();
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
            'kategori' => $item->nama_kategori
                ? $this->categoryLabel($item->nama_kategori)
                : 'Tanpa kategori',
            'harga' => (float) $item->harga,
            'stok' => $item->stok,
            'minimum' => $item->batas_minimum,
            'deskripsi' => $item->deskripsi,
            'aktif' => $item->status === 'tersedia',
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
        ]);
        $data['batas_minimum'] = $data['batas_minimum'] ?? 0;
        DB::table('menu')->insert($data + ['status' => $data['stok'] > 0 ? 'tersedia' : 'habis']);
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
        ]);

        if (! DB::table('menu')->where('id_menu', $id)->exists()) {
            return back()->with('error', 'Menu tidak ditemukan.');
        }

        $data['batas_minimum'] = $data['batas_minimum'] ?? 0;
        DB::table('menu')->where('id_menu', $id)->update($data + ['status' => $data['stok'] > 0 ? 'tersedia' : 'habis']);
        $this->invalidateAdminMetrics();

        return back()->with('success', 'Menu berhasil diperbarui.');
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
        $request->validate(['dari'=>['nullable','date'],'sampai'=>['nullable','date','after_or_equal:dari']]);
        $start = $request->query('dari', now()->startOfWeek()->toDateString());
        $end = $request->query('sampai', now()->toDateString());
        $startAt = Carbon::parse($start)->startOfDay();
        $endExclusive = Carbon::parse($end)->addDay()->startOfDay();

        $version = Cache::get('admin.metrics.version', '0');
        $cacheKey = 'admin.reports.' . $version . '.' . md5($start . '|' . $end);
        $report = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($startAt, $endExclusive) {
            $summary = DB::table('pembayaran')
                ->join('pesanan', 'pembayaran.id_pesanan', '=', 'pesanan.id_pesanan')
                ->where('pembayaran.tanggal', '>=', $startAt)
                ->where('pembayaran.tanggal', '<', $endExclusive)
                ->selectRaw(
                    'COALESCE(SUM(pembayaran.total), 0) AS revenue, COUNT(DISTINCT pesanan.id_pesanan) AS transactions'
                )
                ->selectSub(function ($query) use ($startAt, $endExclusive) {
                    $query->from('detail_pesanan')
                        ->join('pesanan as orders_for_cups', 'detail_pesanan.id_pesanan', '=', 'orders_for_cups.id_pesanan')
                        ->where('orders_for_cups.tanggal', '>=', $startAt)
                        ->where('orders_for_cups.tanggal', '<', $endExclusive)
                        ->selectRaw('COALESCE(SUM(detail_pesanan.jumlah_pesanan), 0)');
                }, 'cups')
                ->first();
            $top = DB::table('detail_pesanan')->join('pesanan','detail_pesanan.id_pesanan','=','pesanan.id_pesanan')
                ->join('menu','detail_pesanan.id_menu','=','menu.id_menu')->join('kategori','menu.id_kategori','=','kategori.id_kategori')
                ->where('pesanan.tanggal', '>=', $startAt)
                ->where('pesanan.tanggal', '<', $endExclusive)
                ->select('menu.nama_menu','kategori.nama_kategori',DB::raw('SUM(detail_pesanan.jumlah_pesanan) as terjual'),DB::raw('SUM(detail_pesanan.subtotal) as omzet'))
                ->groupBy('menu.nama_menu','kategori.nama_kategori')->orderByDesc('terjual')->limit(5)->get()
                ->map(fn($row)=>['nama'=>$row->nama_menu,'kategori'=>$row->nama_kategori,'terjual'=>$row->terjual,'omzet'=>$row->omzet])->all();
            $daily = DB::table('pembayaran')->select(DB::raw('DATE(tanggal) as hari'),DB::raw('SUM(total) as total'))
                ->where('tanggal', '>=', $startAt)
                ->where('tanggal', '<', $endExclusive)
                ->groupBy(DB::raw('DATE(tanggal)'))->orderBy('hari')->get()
                ->map(fn($row)=>[date('D',strtotime($row->hari)),(float)$row->total])->all();

            return [
                'revenue' => (float) ($summary->revenue ?? 0),
                'transactions' => (int) ($summary->transactions ?? 0),
                'cups' => (int) ($summary->cups ?? 0),
                'top' => $top,
                'weekly' => $daily,
            ];
        });

        $revenue = $report['revenue'];
        $transactions = $report['transactions'];
        $cups = $report['cups'];
        $top = $report['top'];
        $weekly = $report['weekly'];
        $dailyAverage = $transactions ? $revenue / max(1, Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1) : 0;
        if (! $weekly) $weekly = [['- ',0]];
        $popular = $top;
        return view('admin.reports', compact('weekly','popular','revenue','transactions','cups','start','end','dailyAverage'));
    }

    private function invalidateAdminMetrics(): void
    {
        Cache::forever('admin.metrics.version', (string) Str::uuid());
    }

    public function downloadReport(Request $request)
    {
        $request->validate(['dari'=>['nullable','date'],'sampai'=>['nullable','date','after_or_equal:dari']]);
        $start = $request->query('dari', now()->startOfWeek()->toDateString());
        $end = $request->query('sampai', now()->toDateString());
        $rows = DB::table('pembayaran')->join('pesanan','pembayaran.id_pesanan','=','pesanan.id_pesanan')
            ->whereBetween(DB::raw('DATE(pembayaran.tanggal)'),[$start,$end])
            ->select('pesanan.id_pesanan','pesanan.tanggal','pembayaran.metode_pembayaran','pembayaran.total')->orderBy('pesanan.tanggal')->get();
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output','w');
            fputcsv($out,['ID Pesanan','Tanggal','Metode Pembayaran','Total']);
            foreach ($rows as $row) fputcsv($out,[$row->id_pesanan,$row->tanggal,$row->metode_pembayaran,$row->total]);
            fclose($out);
        }, 'laporan-penjualan.csv', ['Content-Type'=>'text/csv; charset=UTF-8']);
    }

    public function exportPage(Request $request)
    {
        $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);

        $start = $request->query('dari', now()->startOfWeek()->toDateString());
        $end = $request->query('sampai', now()->toDateString());

        $rows = DB::table('pembayaran')
            ->join('pesanan', 'pembayaran.id_pesanan', '=', 'pesanan.id_pesanan')
            ->whereBetween(DB::raw('DATE(pembayaran.tanggal)'), [$start, $end])
            ->select('pesanan.order_id', 'pesanan.tanggal', 'pembayaran.metode_pembayaran', 'pembayaran.total')
            ->orderBy('pembayaran.tanggal')
            ->get();

        $revenue = (float) $rows->sum('total');
        $transactions = $rows->count();
        $cups = DB::table('detail_pesanan')
            ->join('pesanan', 'detail_pesanan.id_pesanan', '=', 'pesanan.id_pesanan')
            ->whereBetween(DB::raw('DATE(pesanan.tanggal)'), [$start, $end])
            ->sum('detail_pesanan.jumlah_pesanan');

        return view('admin.export', compact('start', 'end', 'rows', 'revenue', 'transactions', 'cups'));
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
    public function kategori() { $this->ensureDefaultCategories(); return response()->json(DB::table('kategori')->orderBy('nama_kategori')->get()); }
    public function stok() { return response()->json(DB::table('menu')->select('id_menu', 'nama_menu', 'stok', 'batas_minimum', 'status')->get()); }
    public function stokMenipis() { return response()->json(DB::table('menu')->select('id_menu', 'nama_menu', 'stok', 'batas_minimum', 'status')->whereColumn('stok', '<=', 'batas_minimum')->get()); }
    public function laporan() { return response()->json(['pendapatan'=>DB::table('pembayaran')->sum('total'),'transaksi'=>DB::table('pembayaran')->distinct('id_pesanan')->count('id_pesanan')]); }

    private function ensureDefaultCategories(): void
    {
        $aliases = [
            'Coffee' => ['coffee', 'kopi'],
            'Non-Coffee' => ['non-coffee', 'non coffee', 'non-kopi'],
            'Makanan' => ['makanan'],
        ];

        // One database round trip is enough to check all defaults; previously
        // this method queried once per category on every menu page visit.
        $existing = DB::table('kategori')
            ->pluck('nama_kategori')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->all();

        foreach ($this->defaultCategories as $name) {
            if (! array_intersect($aliases[$name], $existing)) {
                DB::table('kategori')->insert(['nama_kategori' => $name]);
                $existing[] = mb_strtolower($name);
            }
        }
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
