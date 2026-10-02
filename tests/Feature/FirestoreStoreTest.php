<?php

namespace Tests\Feature;

use App\Services\FirestoreDocumentStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FirestoreStoreTest extends TestCase
{
    private string $credentials;
    protected function setUp(): void
    {
        parent::setUp();
        $this->credentials=tempnam(sys_get_temp_dir(),'firebase-test-');
        file_put_contents($this->credentials,'{}');
        config(['school.firebase_project'=>'demo-school','school.firebase_database'=>'(default)','school.firebase_credentials'=>$this->credentials]);
        Cache::put('firebase-access-'.hash('sha256','demo-school'.$this->credentials),'test-access-token',300);
        Http::preventStrayRequests();
    }
    protected function tearDown(): void { unlink($this->credentials);parent::tearDown(); }

    public function test_types_round_trip_without_losing_nested_data(): void
    {
        $value=['name'=>'Ceria','active'=>true,'total'=>42,'nullable'=>null,'ratio'=>1.5,'files'=>[['name'=>'Akta','path'=>'private/file.pdf']],'empty'=>[]];
        $this->assertSame($value,FirestoreDocumentStore::decode(FirestoreDocumentStore::encode($value)));
    }
    public function test_document_rest_endpoints_pagination_auth_and_aggregate(): void
    {
        $base='https://firestore.googleapis.com/v1/projects/demo-school/databases/%28default%29/documents';
        Http::fake([
            $base.'/applications/missing'=>Http::response([],404),
            $base.'/applications/existing'=>Http::response(['name'=>'projects/demo-school/databases/(default)/documents/applications/existing','fields'=>['status'=>['stringValue'=>'baru']]]),
            $base.'/applications?*'=>Http::response(['documents'=>[['name'=>'projects/demo-school/databases/(default)/documents/applications/existing','fields'=>[]]],'nextPageToken'=>'next-token']),
            $base.':runAggregationQuery'=>Http::response([['result'=>['aggregateFields'=>['total'=>['integerValue'=>'123']]]]]),
        ]);
        $store=new FirestoreDocumentStore;
        $this->assertNull($store->get('applications','missing'));
        $this->assertSame('baru',$store->get('applications','existing')['status']);
        $page=$store->page('applications','cursor-token');
        $this->assertSame('next-token',$page['next']);
        $this->assertSame(123,$store->count('applications'));
        Http::assertSent(fn($request)=>$request->url()===$base.':runAggregationQuery' && $request->hasHeader('Authorization','Bearer test-access-token'));
        Http::assertSent(fn($request)=>str_contains($request->url(),'pageToken=cursor-token'));
        Http::assertSent(fn($request)=>($request['orderBy'] ?? null) === 'created_at desc');
    }
    public function test_create_conflict_is_not_overwritten_and_cloud_failures_do_not_fallback(): void
    {
        Http::fake(['*'=>Http::response([],409)]);
        $this->assertFalse((new FirestoreDocumentStore)->create('admins','duplicate',['name'=>'other']));
        Http::fake(['*'=>Http::response(['error'=>['message'=>'unavailable']],503)]);
        $this->expectException(\Illuminate\Http\Client\RequestException::class);
        (new FirestoreDocumentStore)->get('content','home');
    }

    public function test_custom_ca_bundle_is_used_for_firestore_requests(): void
    {
        config(['school.firebase_ca_bundle' => $this->credentials]);
        Http::fake(function ($request, $options) {
            $this->assertSame($this->credentials, $options['verify']);
            $this->assertSame('__name__ asc', $request['orderBy']);
            return Http::response([]);
        });
        $this->assertSame([], (new FirestoreDocumentStore)->page('content')['items']);
    }

    public function test_ca_setting_cannot_disable_tls_verification(): void
    {
        config(['school.firebase_ca_bundle' => false]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('FIREBASE_CA_BUNDLE');
        (new FirestoreDocumentStore)->page('content');
    }
}
