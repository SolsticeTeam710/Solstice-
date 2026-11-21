<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (
            Response $response,
            Throwable $exception,
            Request $request
        ): Response {
            $status = $response->getStatusCode();

            // Koneksi/query database gagal: tampilkan pesan aman dengan HTTP 503.
            if ($exception instanceof QueryException || $exception instanceof \PDOException) {
                $status = 503;
            }

            // Biarkan halaman debug Laravel aktif hanya jika APP_DEBUG=true.
            if ($status === 500 && config('app.debug')) {
                return $response;
            }

            $messages = [
                403 => [
                    'title' => 'Akses Ditolak',
                    'message' => 'Akun ini tidak memiliki izin untuk membuka halaman tersebut.',
                ],
                404 => [
                    'title' => 'Halaman Tidak Ditemukan',
                    'message' => 'Halaman yang Anda cari tidak tersedia atau alamatnya sudah berubah.',
                ],
                500 => [
                    'title' => 'Terjadi Kendala',
                    'message' => 'Sistem sedang mengalami kendala. Silakan coba lagi beberapa saat.',
                ],
                503 => [
                    'title' => 'Layanan Sementara Tidak Tersedia',
                    'message' => 'Koneksi layanan sedang bermasalah. Silakan coba lagi beberapa saat.',
                ],
            ];

            if (! isset($messages[$status])) {
                return $response;
            }

            $error = $messages[$status];

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $error['message'],
                ], $status);
            }

            return response()->view('errors.'.$status, [
                'status' => $status,
                'title' => $error['title'],
                'message' => $error['message'],
            ], $status);
        });
    })
    ->create();