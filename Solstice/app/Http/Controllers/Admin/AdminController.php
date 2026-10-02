<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    private array $defaultCategories = ['Coffee', 'Non-Coffee', 'Makanan'];

    public function dashboard()
    {
        $labels = ['Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab','Sun'=>'Min'];
        $firstDay = Carbon::today()->subDays(6);
        $dailySales = DB::table('pembayaran')->select(DB::raw('DATE(tanggal) as hari'),DB::raw('SUM(total) as total'))
            ->whereBetween(DB::raw('DATE(tanggal)'),[$firstDay->toDateString(),Carbon::today()->toDateString()])
            ->groupBy(DB::raw('DATE(tanggal)'))->pluck('total','hari');
        $weekly = [];
        for ($day = $firstDay->copy(); $day->lte(Carbon::today()); $day->addDay()) {
            $weekly[] = [$labels[$day->format('D')], ((float)($dailySales[$day->toDateString()] ?? 0)) / 1000000];
        }
        $todayRevenue = (float) DB::table('pembayaran')->whereDate('tanggal',Carbon::today())->sum('total');
        $menuTotal = DB::table('menu')->count();
        $activeMenus = DB::table('menu')->where('status','tersedia')->count();
        $userTotal = DB::table('users')->count();
        $adminTotal = DB::table('users')->where('role','admin')->count();
        $cashierTotal = DB::table('users')->where('role','kasir')->count();
        $lowStock = Schema::hasTable('bahan_baku') ? DB::table('bahan_baku')->whereColumn('stok','<=','stok_minimum')->count() : 0;
        $stats = [
            ['Total Varian Menu', $menuTotal.' Menu', 'text-brand', $activeMenus.' menu tersedia', 'text-ok', false],
            ['Total Pengguna (Staf)', $userTotal.' User', 'text-brand', $adminTotal.' Admin · '.$cashierTotal.' Kasir', 'text-muted', false],
            ['Penjualan Hari Ini', 'Rp '.number_format($todayRevenue,0,',','.'), 'text-brand', 'Total pembayaran hari ini', 'text-ok', false],
            ['Stok Menipis', $lowStock.' Bahan', 'text-warn', $lowStock ? 'Perlu diperiksa' : 'Semua stok aman', 'text-muted', true],
        ];
        $logs = [
            ['Menu dan kategori dikelola', 'Hari ini', 'Data menu sekarang tersimpan ke database'],
            ['Pembaruan stok', 'Hari ini', 'Periksa halaman Kelola Stok untuk melihat bahan kritis'],
        ];
        return view('admin.dashboard', compact('weekly', 'logs', 'stats'));
    }

    public function dashboardData()
    {
        return response()->json([
            'menu' => DB::table('menu')->count(),
            'pengguna' => DB::table('users')->count(),
            'stok_menipis' => Schema::hasTable('bahan_baku') ? DB::table('bahan_baku')->whereColumn('stok', '<=', 'stok_minimum')->count() : 0,
        ]);
    }

    public function menus(Request $request)
    {
        $this->ensureDefaultCategories();
        $categories = DB::table('kategori')->orderBy('nama_kategori')->get()->map(function ($category) {
            $category->display_name = $this->categoryLabel($category->nama_kategori);
            return $category;
        });
        $query = DB::table('menu')->join('kategori', 'menu.id_kategori', '=', 'kategori.id_kategori')
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
            'kategori' => $this->categoryLabel($item->nama_kategori), 'harga' => (float) $item->harga, 'stok' => $item->stok,
            'minimum' => $item->batas_minimum, 'deskripsi' => $item->deskripsi,
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
        $data['batas_minimum'] = $data['batas_minimum'] ?? 0;
        DB::table('menu')->where('id_menu', $id)->update($data + ['status' => $data['stok'] > 0 ? 'tersedia' : 'habis']);
        return back()->with('success', 'Menu berhasil diperbarui.');
    }

    public function deleteMenu(int $id)
    {
        if (Schema::hasTable('detail_pesanan') && DB::table('detail_pesanan')->where('id_menu', $id)->exists()) {
            return back()->with('error', 'Menu sudah tercatat di pesanan, jadi tidak dapat dihapus.');
        }
        DB::table('menu')->where('id_menu', $id)->delete();
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
        $columns = Schema::getColumnListing('users');
        $users = DB::table('users')->whereIn('role',['admin','kasir'])->orderBy('username')->get()->map(function ($user) use ($columns) {
            $active = in_array('is_active', $columns)
                ? (bool) $user->is_active
                : in_array(mb_strtolower((string) ($user->status ?? 'aktif')), ['aktif', 'active'], true);
            return ['id'=>$user->id_users, 'nama'=>in_array('name',$columns) && $user->name ? $user->name : $user->username,
                'username'=>$user->username, 'email'=>in_array('email',$columns) ? ($user->email ?? '') : '',
                'jabatan'=>ucfirst($user->role), 'role'=>$user->role, 'aktif'=>$active];
        })->all();
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
        return back()->with('success', 'Akun staf berhasil diperbarui.');
    }

    public function deleteUser(int $id)
    {
        $target = DB::table('users')->where('id_users', $id)->first();
        if (! $target) return back()->with('error', 'Akun tidak ditemukan.');
        if ((int) auth()->id() === $id) return back()->with('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        if ($target->role === 'admin' && DB::table('users')->where('role','admin')->count() <= 1) return back()->with('error', 'Tidak dapat menghapus satu-satunya admin.');
        if (Schema::hasTable('pesanan') && DB::table('pesanan')->where('id_users',$id)->exists()) return back()->with('error', 'Akun ini memiliki riwayat pesanan, jadi tidak dapat dihapus. Nonaktifkan akunnya saja.');
        DB::table('users')->where('id_users', $id)->delete();
        return back()->with('success', 'Akun staf berhasil dihapus.');
    }

    public function toggleUserStatus(int $id)
    {
        $user = DB::table('users')->where('id_users',$id)->first();
        if (! $user) return back()->with('error','Akun tidak ditemukan.');
        if ((int) auth()->id() === $id) return back()->with('error','Status akun yang sedang digunakan tidak dapat diubah.');
        $columns = Schema::getColumnListing('users');
        $isActive = in_array('is_active',$columns)
            ? (bool) $user->is_active
            : in_array(mb_strtolower((string) ($user->status ?? 'aktif')), ['aktif', 'active'], true);
        $values = [];
        if (in_array('is_active',$columns)) $values['is_active'] = ! $isActive;
        if (in_array('status',$columns)) $values['status'] = $isActive ? 'nonaktif' : 'aktif';
        if (in_array('updated_at',$columns)) $values['updated_at'] = now();
        DB::table('users')->where('id_users',$id)->update($values);
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
        $this->requireStockTable();
        $query = DB::table('bahan_baku');
        if ($request->filled('kategori')) $query->where('kategori', $request->query('kategori'));
        $stockCategories = DB::table('bahan_baku')->distinct()->orderBy('kategori')->pluck('kategori');
        $stocks = $query->orderBy('nama_bahan')->get()->map(fn ($item) => [
            'id'=>$item->id_bahan,'nama'=>$item->nama_bahan,'jumlah'=>$item->stok.' '.$item->satuan,
            'stok'=>$item->stok,'satuan'=>$item->satuan,'minimum'=>$item->stok_minimum.' '.$item->satuan,
            'stok_minimum'=>$item->stok_minimum,'kategori'=>$item->kategori,
            'status'=>$item->stok <= 0 ? 'Habis' : ($item->stok <= $item->stok_minimum ? 'Menipis' : 'Aman'),
        ])->all();
        return view('admin.stock', compact('stocks','stockCategories'));
    }

    public function storeStock(Request $request)
    {
        $this->requireStockTable();
        $data = $request->validate(['nama_bahan'=>['required','string','max:150','unique:bahan_baku,nama_bahan'],'kategori'=>['required','string','max:80'],'satuan'=>['required','string','max:30'],'stok'=>['required','numeric','min:0'],'stok_minimum'=>['required','numeric','min:0']]);
        DB::table('bahan_baku')->insert($data + ['created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success','Bahan baku berhasil ditambahkan.');
    }

    public function updateStock(Request $request, int $id)
    {
        $this->requireStockTable();
        $data = $request->validate(['stok'=>['required','numeric','min:0'],'stok_minimum'=>['required','numeric','min:0']]);
        DB::table('bahan_baku')->where('id_bahan',$id)->update($data + ['updated_at'=>now()]);
        return back()->with('success','Stok berhasil diperbarui.');
    }

    private function requireStockTable(): void
    {
        abort_unless(Schema::hasTable('bahan_baku'), 503, 'Jalankan php artisan migrate untuk mengaktifkan tabel bahan baku.');
    }

    public function criticalStockPage()
    {
        $this->requireStockTable();
        $critical = DB::table('bahan_baku')->whereColumn('stok','<=','stok_minimum')->orderBy('stok')->get()->map(fn ($item) => [
            'id'=>$item->id_bahan,'nama'=>$item->nama_bahan,'jumlah'=>$item->stok.' '.$item->satuan,
            'minimum'=>$item->stok_minimum.' '.$item->satuan,'status'=>$item->stok <= 0 ? 'Habis' : 'Menipis','menu'=>'Cek resep menu terkait',
        ])->all();
        return view('admin.critical-stock', compact('critical'));
    }

    public function reportsPage(Request $request)
    {
        $request->validate(['dari'=>['nullable','date'],'sampai'=>['nullable','date','after_or_equal:dari']]);
        $start = $request->query('dari', now()->startOfWeek()->toDateString());
        $end = $request->query('sampai', now()->toDateString());
        $sales = DB::table('pembayaran')->join('pesanan','pembayaran.id_pesanan','=','pesanan.id_pesanan')
            ->whereBetween(DB::raw('DATE(pembayaran.tanggal)'), [$start,$end]);
        $revenue = (clone $sales)->sum('pembayaran.total');
        $transactions = (clone $sales)->distinct('pesanan.id_pesanan')->count('pesanan.id_pesanan');
        $dailyAverage = $transactions ? $revenue / max(1, Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1) : 0;
        $cups = DB::table('detail_pesanan')->join('pesanan','detail_pesanan.id_pesanan','=','pesanan.id_pesanan')
            ->whereBetween(DB::raw('DATE(pesanan.tanggal)'),[$start,$end])->sum('detail_pesanan.jumlah_pesanan');
        $top = DB::table('detail_pesanan')->join('pesanan','detail_pesanan.id_pesanan','=','pesanan.id_pesanan')
            ->join('menu','detail_pesanan.id_menu','=','menu.id_menu')->join('kategori','menu.id_kategori','=','kategori.id_kategori')
            ->whereBetween(DB::raw('DATE(pesanan.tanggal)'),[$start,$end])
            ->select('menu.nama_menu','kategori.nama_kategori',DB::raw('SUM(detail_pesanan.jumlah_pesanan) as terjual'),DB::raw('SUM(detail_pesanan.subtotal) as omzet'))
            ->groupBy('menu.nama_menu','kategori.nama_kategori')->orderByDesc('terjual')->limit(5)->get()
            ->map(fn($row)=>['nama'=>$row->nama_menu,'kategori'=>$row->nama_kategori,'terjual'=>$row->terjual,'omzet'=>$row->omzet])->all();
        $daily = DB::table('pembayaran')->select(DB::raw('DATE(tanggal) as hari'),DB::raw('SUM(total) as total'))
            ->whereBetween(DB::raw('DATE(tanggal)'),[$start,$end])->groupBy(DB::raw('DATE(tanggal)'))->orderBy('hari')->get();
        $weekly = $daily->map(fn($row)=>[date('D',strtotime($row->hari)),(float)$row->total])->all();
        if (! $weekly) $weekly = [['- ',0]];
        $popular = $top;
        return view('admin.reports', compact('weekly','popular','revenue','transactions','cups','start','end','dailyAverage'));
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

    public function exportPage() { return view('admin.export'); }

    public function users() { $columns = array_values(array_diff(Schema::getColumnListing('users'),['password','remember_token'])); return response()->json(DB::table('users')->select($columns)->get()); }
    public function menu() { return response()->json(DB::table('menu')->join('kategori','menu.id_kategori','=','kategori.id_kategori')->get()); }
    public function kategori() { $this->ensureDefaultCategories(); return response()->json(DB::table('kategori')->orderBy('nama_kategori')->get()); }
    public function stok() { $this->requireStockTable(); return response()->json(DB::table('bahan_baku')->get()); }
    public function stokMenipis() { $this->requireStockTable(); return response()->json(DB::table('bahan_baku')->whereColumn('stok','<=','stok_minimum')->get()); }
    public function laporan() { return response()->json(['pendapatan'=>DB::table('pembayaran')->sum('total'),'transaksi'=>DB::table('pembayaran')->distinct('id_pesanan')->count('id_pesanan')]); }

    private function ensureDefaultCategories(): void
    {
        $aliases = [
            'Coffee' => ['coffee', 'kopi'],
            'Non-Coffee' => ['non-coffee', 'non coffee', 'non-kopi'],
            'Makanan' => ['makanan'],
        ];

        foreach ($this->defaultCategories as $name) {
            if (! DB::table('kategori')->whereIn(DB::raw('LOWER(nama_kategori)'), $aliases[$name])->exists()) {
                DB::table('kategori')->insert(['nama_kategori' => $name]);
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
