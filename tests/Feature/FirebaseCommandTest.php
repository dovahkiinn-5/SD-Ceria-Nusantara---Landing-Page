<?php

namespace Tests\Feature;

use App\Services\SqliteDocumentStore;
use App\Services\FirestoreDocumentStore;
use App\Services\FirestoreFileStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeFirestoreFileStorage;
use Tests\TestCase;

class FirebaseCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'school.store' => 'firestore',
            'school.firebase_project' => 'demo-school',
            'school.firebase_database' => '(default)',
        ]);
        $this->app->instance(FirestoreDocumentStore::class, new class extends FirestoreDocumentStore {
            protected function accessToken(): string
            {
                return 'test-access-token';
            }
        });
        Http::preventStrayRequests();
    }

    public function test_connection_check_accepts_an_empty_collection_without_writing(): void
    {
        Http::fake(['*' => Http::response([])]);

        $this->artisan('school:firebase-check')
            ->expectsOutputToContain('Koneksi dan autentikasi Firestore berhasil.')
            ->assertSuccessful();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_contains($request->url(), '/documents/content?')
            && $request['pageSize'] === 1);
    }

    public function test_connection_check_rejects_a_missing_database_without_printing_response_details(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'private diagnostic data']], 404)]);

        $this->artisan('school:firebase-check')
            ->expectsOutputToContain('Database Firestore tidak ditemukan.')
            ->doesntExpectOutputToContain('Koneksi dan autentikasi Firestore berhasil.')
            ->doesntExpectOutputToContain('private diagnostic data')
            ->assertFailed();
    }

    public function test_local_upload_migration_copies_to_firestore_without_deleting_sources(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('local')->put('applications/CN-OLD/akta.pdf', 'application-bytes');
        Storage::disk('public')->put('media/school.png', 'image-bytes');
        $storage = new FakeFirestoreFileStorage;
        $this->app->instance(FirestoreFileStorage::class, $storage);

        $this->artisan('school:migrate-local-uploads')
            ->expectsOutputToContain('2 berkas lokal tersalin ke Firestore.')
            ->assertSuccessful();

        $this->assertSame('application-bytes', $storage->objects['applications/CN-OLD/akta.pdf']);
        $this->assertSame('image-bytes', $storage->objects['media/school.png']);
        Storage::disk('local')->assertExists('applications/CN-OLD/akta.pdf');
        Storage::disk('public')->assertExists('media/school.png');
    }

    public function test_import_clears_firestore_content_cache_even_when_local_driver_is_selected(): void
    {
        config(['school.store' => 'sqlite']);
        (new SqliteDocumentStore)->create('content', 'settings', ['name' => 'Sekolah']);
        Cache::put('school-content-firestore', ['settings' => ['name' => 'Konten lama']], 300);
        Http::fake(['*' => Http::response([], 200)]);

        $this->artisan('school:import-firestore')
            ->expectsOutputToContain('1 dokumen baru disalin ke Firestore.')
            ->assertSuccessful();

        $this->assertFalse(Cache::has('school-content-firestore'));
        $this->assertSame('Sekolah', (new SqliteDocumentStore)->get('content', 'settings')['name']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), '/documents/content?documentId=settings'));
    }
}
