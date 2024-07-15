<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\QueryExecuted;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DB::listen(function (QueryExecuted $query) {
            $sql      = $query->sql;
            $bindings = $query->bindings;
            $time     = $query->time;

            // 将 SQL 查询记录到日志文件中
            Log::info('Query: ' . $sql . ' | Bindings: ' . json_encode($bindings) . ' | Time: ' . $time . 'ms');
        });
    }
}
