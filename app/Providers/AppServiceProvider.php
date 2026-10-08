<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\MidtransGateway;
use App\Services\Payments\PaymentGateway;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Satu konteks peternakan per request
        $this->app->scoped(\App\Tenancy\FarmContext::class);

        $this->app->bind(PaymentGateway::class, fn () => match (config('services.payment.driver')) {
            'midtrans' => new MidtransGateway(config('services.midtrans')),
            default    => new FakeGateway(),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');
        Paginator::useBootstrapFive();
        Setting::flushMemo();

        // Pintasan format di Blade: @rupiah($x), @angka($x, 1), @tanggal($d)
        Blade::directive('rupiah', fn ($e) => "<?php echo e(\\App\\Support\\Format::rupiah($e)); ?>");
        Blade::directive('angka', fn ($e) => "<?php echo e(\\App\\Support\\Format::number($e)); ?>");
        Blade::directive('tanggal', fn ($e) => "<?php echo e(\\App\\Support\\Format::date($e)); ?>");

        // Nama peternakan tersedia di semua halaman
        View::composer(['layouts.*', 'auth.*', 'pdf.*'], function ($view) {
            $view->with('farmName', Setting::get('farm_name'));
        });
    }
}
