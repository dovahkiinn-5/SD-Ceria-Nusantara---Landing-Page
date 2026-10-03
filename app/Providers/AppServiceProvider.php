<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\DocumentStore;
use App\Services\FirestoreDocumentStore;
use App\Services\SqliteDocumentStore;
use App\Services\FirestoreCacheStore;
use App\Services\FirestoreSessionHandler;
use App\Auth\DocumentUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
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
        $this->app->singleton(FirestoreDocumentStore::class);
        $this->app->singleton(DocumentStore::class, fn () => match (config('school.store')) {
            'sqlite' => app()->environment('testing')
                ? new SqliteDocumentStore
                : throw new \RuntimeException('SQLite hanya tersedia untuk pengujian. Gunakan SCHOOL_STORE=firestore.'),
            'firestore' => $this->app->make(FirestoreDocumentStore::class),
            default => throw new \RuntimeException('SCHOOL_STORE harus firestore.'),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') && config('school.store') !== 'firestore') {
            throw new \RuntimeException('Produksi wajib menggunakan SCHOOL_STORE=firestore.');
        }
        Cache::extend('firestore', fn ($app) => Cache::repository(new FirestoreCacheStore(
            $app->make(FirestoreDocumentStore::class), (string) config('cache.prefix'),
        )));
        Session::extend('firestore', fn ($app) => new FirestoreSessionHandler(
            $app->make(FirestoreDocumentStore::class), (int) config('session.lifetime'), (string) config('session.cookie'),
        ));
        Auth::provider('documents', fn ($app) => new DocumentUserProvider($app->make(DocumentStore::class)));
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);
        RateLimiter::for('submissions', fn (Request $request) => Limit::perMinute(12)->by($request->ip()));
        RateLimiter::for('visits', fn (Request $request) => Limit::perHour(5)->by($request->ip()));
    }
}
