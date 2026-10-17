<?php

namespace Tests\Feature;

use App\Contracts\DocumentStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminResetPasswordCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $email = 'owner@example.test';
    private string $oldPassword = 'Old-Password-123';

    protected function setUp(): void
    {
        parent::setUp();
        app(DocumentStore::class)->create('admins', hash('sha256', $this->email), [
            'email' => $this->email,
            'name' => 'Pemilik Uji',
            'password' => Hash::make($this->oldPassword),
            'role' => 'owner',
            'active' => true,
        ]);
    }

    public function test_it_resets_an_existing_admin_password_without_changing_account_details(): void
    {
        $newPassword = 'New-Password-123';

        $this->artisan('school:admin-reset-password', ['email' => $this->email])
            ->expectsQuestion('Kata sandi baru (minimal 12 karakter, huruf dan angka)', $newPassword)
            ->expectsQuestion('Ulangi kata sandi baru', $newPassword)
            ->expectsOutputToContain('Kata sandi akun admin berhasil direset.')
            ->assertSuccessful();

        $record = app(DocumentStore::class)->get('admins', hash('sha256', $this->email));
        $this->assertTrue(Hash::check($newPassword, $record['password']));
        $this->assertFalse(Hash::check($this->oldPassword, $record['password']));
        $this->assertSame($this->email, $record['email']);
        $this->assertSame('Pemilik Uji', $record['name']);
        $this->assertSame('owner', $record['role']);
        $this->assertTrue($record['active']);
    }

    public function test_it_does_not_prompt_for_a_password_when_the_admin_does_not_exist(): void
    {
        $this->artisan('school:admin-reset-password', ['email' => 'missing@example.test'])
            ->expectsOutputToContain('Akun admin tidak ditemukan.')
            ->assertFailed();
    }

    public function test_it_rejects_a_mismatched_password_confirmation_without_updating_the_account(): void
    {
        $this->artisan('school:admin-reset-password', ['email' => $this->email])
            ->expectsQuestion('Kata sandi baru (minimal 12 karakter, huruf dan angka)', 'New-Password-123')
            ->expectsQuestion('Ulangi kata sandi baru', 'Different-Password-123')
            ->assertFailed();

        $record = app(DocumentStore::class)->get('admins', hash('sha256', $this->email));
        $this->assertTrue(Hash::check($this->oldPassword, $record['password']));
    }
}
