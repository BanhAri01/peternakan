<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Pengguna yang sudah masuk diarahkan ke halaman depan sesuai perannya
        $middleware->redirectUsersTo('/');

        // Peternakan aktif diambil dari user yang login (dipakai semua model data peternakan)
        $middleware->web(append: [
            \App\Tenancy\SetFarmContext::class,
        ]);
        // Harus berjalan sebelum route model binding, agar /coops/{coop} hanya menemukan data peternakan sendiri
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Tenancy\SetFarmContext::class,
        );

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'farm' => \App\Tenancy\EnsureActiveFarm::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
