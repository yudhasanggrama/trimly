<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_bisa_mendaftar_dengan_data_valid(): void
    {
        $response = $this->post(route('register'), [
            'name'                  => 'Budi Santoso',
            'email'                 => 'budi@example.com',
            'phone'                 => '08123456789',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'name'  => 'Budi Santoso',
        ]);
        $this->assertAuthenticated();
    }

    /** @test */
    public function user_otomatis_login_setelah_registrasi(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Budi',
            'email'                 => 'budi@example.com',
            'phone'                 => '08123456789',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertAuthenticated();
    }

    /** @test */
    public function role_default_user_saat_registrasi_adalah_user(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Budi',
            'email'                 => 'budi@example.com',
            'phone'                 => '08123456789',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'role'  => 'user',
        ]);
    }

    /** @test */
    public function password_disimpan_dalam_bentuk_hash(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Budi',
            'email'                 => 'budi@example.com',
            'phone'                 => '08123456789',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $user = User::where('email', 'budi@example.com')->first();
        $this->assertNotEquals('secret123', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('secret123', $user->password));
    }

    /** @test */
    public function registrasi_gagal_jika_email_sudah_dipakai(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        $response = $this->post(route('register'), [
            'name'                  => 'Budi 2',
            'email'                 => 'budi@example.com', // duplikat
            'phone'                 => '08199999999',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    /** @test */
    public function registrasi_gagal_jika_nama_kosong(): void
    {
        $this->post(route('register'), [
            'name'                  => '',
            'email'                 => 'budi@example.com',
            'phone'                 => '08123456789',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    /** @test */
    public function registrasi_gagal_jika_email_tidak_valid(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Budi',
            'email'                 => 'bukan-email',
            'phone'                 => '08123456789',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('email');
    }

    /** @test */
    public function registrasi_gagal_jika_phone_bukan_angka(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Budi',
            'email'                 => 'budi@example.com',
            'phone'                 => 'abc-def',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('phone');
    }

    /** @test */
    public function registrasi_gagal_jika_password_kurang_dari_6_karakter(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Budi',
            'email'                 => 'budi@example.com',
            'phone'                 => '08123456789',
            'password'              => '12345',
            'password_confirmation' => '12345',
        ])->assertSessionHasErrors('password');
    }

    /** @test */
    public function registrasi_gagal_jika_konfirmasi_password_tidak_cocok(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Budi',
            'email'                 => 'budi@example.com',
            'phone'                 => '08123456789',
            'password'              => 'secret123',
            'password_confirmation' => 'berbeda123',
        ])->assertSessionHasErrors('password');
    }

    /** @test */
    public function registrasi_gagal_jika_semua_field_kosong(): void
    {
        $this->post(route('register'), [])
            ->assertSessionHasErrors(['name', 'email', 'phone', 'password']);
    }

    /** @test */
    public function user_bisa_login_dengan_kredensial_benar(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertSessionHas('success');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function login_gagal_dengan_password_salah(): void
    {
        $user = User::factory()->create(['password' => bcrypt('benar123')]);

        $response = $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'salah123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /** @test */
    public function login_gagal_dengan_email_tidak_terdaftar(): void
    {
        $response = $this->post(route('login'), [
            'email'    => 'tidakada@example.com',
            'password' => 'apapun',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /** @test */
    public function login_gagal_jika_email_kosong(): void
    {
        $this->post(route('login'), [
            'email'    => '',
            'password' => 'secret123',
        ])->assertSessionHasErrors('email');
    }

    /** @test */
    public function login_gagal_jika_email_tidak_berformat_email(): void
    {
        $this->post(route('login'), [
            'email'    => 'bukan-email',
            'password' => 'secret123',
        ])->assertSessionHasErrors('email');
    }

    /** @test */
    public function login_gagal_jika_password_kosong(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email'    => $user->email,
            'password' => '',
        ])->assertSessionHasErrors('password');
    }

    /** @test */
    public function session_diregenerasi_setelah_login_sukses(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $oldToken = session()->token();

        $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'secret123',
        ]);

        // Session diregenerasi berarti token berubah
        $this->assertNotEquals($oldToken, session()->token());
    }

    /** @test */
    public function user_bisa_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /** @test */
    public function logout_menginvalidasi_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
    }

    /** @test */
    public function logout_menampilkan_pesan_sukses(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertSessionHas('success');
    }

    /** @test */
    public function guest_tidak_bisa_logout(): void
    {
        $response = $this->post(route('logout'));
        $response->assertStatus(302);
    }
}