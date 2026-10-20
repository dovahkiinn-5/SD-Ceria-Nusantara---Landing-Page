<?php

namespace Tests\Feature;

use App\Contracts\DocumentStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private const GROUP_URL = 'https://chat.whatsapp.com/CxsxIG5MnIYErggxZ8VkdZ?mode=gi_t';

    protected function setUp(): void
    {
        parent::setUp();
        config(['school.whatsapp_group_url' => self::GROUP_URL]);
    }

    public function test_accepted_applicant_sees_passing_result_and_whatsapp_group_link(): void
    {
        $this->storeApplication('CN-2026-ACCEPTED01', 'diterima', 'Anak Diterima');

        $this->post('/pengumuman', [
            'reference' => 'CN-2026-ACCEPTED01',
        ])->assertOk()
            ->assertSee('LULUS')
            ->assertSee('Anak Diterima')
            ->assertSee(self::GROUP_URL, false);
    }

    public function test_rejected_applicant_does_not_see_whatsapp_group_link(): void
    {
        $this->storeApplication('CN-2026-REJECTED01', 'ditolak', 'Anak Ditolak');

        $this->post('/pengumuman', [
            'reference' => 'CN-2026-REJECTED01',
        ])->assertOk()
            ->assertSee('TIDAK LULUS')
            ->assertSee('Anak Ditolak')
            ->assertDontSee(self::GROUP_URL, false);
    }

    public function test_new_and_reviewed_applications_are_still_pending(): void
    {
        foreach (['baru' => 'NEW01', 'ditinjau' => 'REVIEWED01'] as $status => $suffix) {
            $reference = 'CN-2026-'.$suffix;
            $name = 'Anak '.ucfirst($status);
            $this->storeApplication($reference, $status, $name);

            $this->post('/pengumuman', [
                'reference' => $reference,
            ])->assertOk()
                ->assertSee('MASIH DIPROSES')
                ->assertSee($name)
                ->assertDontSee(self::GROUP_URL, false);
        }
    }

    public function test_unknown_reference_returns_a_clear_error_without_leaking_data(): void
    {
        $reference = 'CN-2026-PRIVATE01';
        $privateName = 'Nama Anak Rahasia';
        $genericError = 'Nomor pendaftaran tidak ditemukan.';
        $this->storeApplication($reference, 'diterima', $privateName);

        $wrongReference = $this->followingRedirects()->post('/pengumuman', [
            'reference' => 'CN-2026-UNKNOWN01',
        ]);

        $wrongReference->assertOk()
            ->assertSee($genericError)
            ->assertDontSee($privateName)
            ->assertDontSee(self::GROUP_URL, false);
    }

    public function test_lookup_form_requires_only_registration_number(): void
    {
        $this->get('/pengumuman')->assertOk()
            ->assertSee('name="reference"', false)
            ->assertDontSee('name="birth_date"', false)
            ->assertDontSee('tanggal lahir anak');

        $this->post('/pengumuman', [])->assertSessionHasErrors('reference')
            ->assertSessionDoesntHaveErrors('birth_date');
    }

    public function test_repeated_lookup_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/pengumuman', ['reference' => 'CN-2026-RATELIMIT'])->assertRedirect();
        }

        $this->post('/pengumuman', ['reference' => 'CN-2026-RATELIMIT'])->assertStatus(429);
    }

    private function storeApplication(string $reference, string $status, string $childName): void
    {
        app(DocumentStore::class)->put('applications', $reference, [
            'reference' => $reference,
            'child' => [
                'child_name' => $childName,
                'birth_date' => '2019-04-10',
            ],
            'parent' => [
                'parent_name' => 'Wali Uji',
                'phone' => '+6281234567890',
                'email' => 'wali@example.test',
            ],
            'status' => $status,
            'created_at' => now()->toIso8601String(),
        ]);
    }
}
