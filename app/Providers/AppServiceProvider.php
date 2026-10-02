<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\DocumentStore;
use App\Services\SqliteDocumentStore;
use App\Services\FirestoreDocumentStore;
use App\Auth\DocumentUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DocumentStore::class, fn () => match (config('school.store')) {
            'sqlite' => new SqliteDocumentStore,
            'firestore' => new FirestoreDocumentStore,
            default => throw new \RuntimeException('SCHOOL_STORE harus sqlite atau firestore.'),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('documents', fn ($app) => new DocumentUserProvider($app->make(DocumentStore::class)));
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);
        RateLimiter::for('submissions', fn (Request $request) => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('visits', fn (Request $request) => Limit::perHour(5)->by($request->ip()));
    }
}
