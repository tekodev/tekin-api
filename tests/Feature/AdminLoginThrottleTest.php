<?php

namespace Tests\Feature;

use App\Models\system\SysUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'dogru-sifre-123';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        SysUser::query()->forceCreate([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => Hash::make(self::PASSWORD),
            'status' => 1,
        ]);
    }

    private function attempt(string $password, string $ip = '203.0.113.10', string $username = 'admin', array $headers = [])
    {
        return $this->withHeaders(array_merge(['CF-Connecting-IP' => $ip], $headers))
            ->from(route('admin.login'))
            ->post(route('admin.do_login'), ['login_id' => $username, 'login_pass' => $password]);
    }

    private function isThrottled($response): bool
    {
        return str_contains((string) session('error'), 'Too many login attempts');
    }

    public function test_correct_password_logs_in(): void
    {
        $this->attempt(self::PASSWORD)->assertRedirect(route('admin.home'));
        $this->assertNotNull(session('admin'));
    }

    public function test_locks_after_five_wrong_passwords_even_for_the_right_password(): void
    {
        foreach (range(1, 5) as $i) {
            $response = $this->attempt('yanlis-'.$i);
            $this->assertFalse($this->isThrottled($response), "attempt {$i} should not be throttled yet");
        }

        $response = $this->attempt(self::PASSWORD);

        $response->assertRedirect(route('admin.login'));
        $this->assertTrue($this->isThrottled($response));
        $this->assertNull(session('admin'));
    }

    public function test_spoofed_x_forwarded_for_does_not_reset_the_counter(): void
    {
        foreach (range(1, 5) as $i) {
            $this->attempt('yanlis-'.$i, headers: ['X-Forwarded-For' => "198.51.100.{$i}"]);
        }

        $response = $this->attempt(self::PASSWORD, headers: ['X-Forwarded-For' => '198.51.100.99']);

        $this->assertTrue($this->isThrottled($response));
    }

    public function test_one_ip_cannot_spray_many_usernames(): void
    {
        foreach (range(1, 20) as $i) {
            $this->attempt('yanlis', username: "kullanici{$i}");
        }

        $response = $this->attempt(self::PASSWORD);

        $this->assertTrue($this->isThrottled($response));
    }

    public function test_many_ips_cannot_brute_force_one_account(): void
    {
        foreach (range(1, 30) as $i) {
            $this->attempt('yanlis', ip: "192.0.2.{$i}");
        }

        $response = $this->attempt(self::PASSWORD, ip: '203.0.113.200');

        $this->assertTrue($this->isThrottled($response));
    }

    public function test_other_admins_on_other_ips_are_not_affected(): void
    {
        foreach (range(1, 5) as $i) {
            $this->attempt('yanlis-'.$i);
        }

        $this->attempt(self::PASSWORD, ip: '203.0.113.77')->assertRedirect(route('admin.home'));
    }

    public function test_successful_login_resets_the_counter(): void
    {
        foreach (range(1, 4) as $i) {
            $this->attempt('yanlis-'.$i);
        }

        $this->attempt(self::PASSWORD)->assertRedirect(route('admin.home'));
        $this->flushSession();

        foreach (range(1, 4) as $i) {
            $response = $this->attempt('yanlis-tekrar-'.$i);
            $this->assertFalse($this->isThrottled($response));
        }
    }

    public function test_password_is_not_flashed_back_to_the_session(): void
    {
        $this->attempt('gizli-yanlis-sifre');

        $this->assertNull(session()->getOldInput('login_pass'));
        $this->assertSame('admin', session()->getOldInput('login_id'));
    }
}
