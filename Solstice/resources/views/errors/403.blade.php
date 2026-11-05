@include('errors.page', [
    'status' => 403,
    'title' => 'Akses Ditolak',
    'message' => 'Akun ini tidak memiliki izin untuk membuka halaman tersebut.',
])