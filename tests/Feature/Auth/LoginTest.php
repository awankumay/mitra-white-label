<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'panel_user']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function panelUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('panel_user');

        return $user;
    }

    public function test_login_page_is_accessible(): void
    {
        $this->get(route('filament.admin.auth.login'))
            ->assertSuccessful();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->panelUser([
            'email' => 'user@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        Livewire::test(\Filament\Auth\Pages\Login::class)
            ->set('data.email', 'user@example.com')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $this->panelUser([
            'email' => 'user@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        Livewire::test(\Filament\Auth\Pages\Login::class)
            ->set('data.email', 'user@example.com')
            ->set('data.password', 'wrong-password')
            ->call('authenticate')
            ->assertHasFormErrors();

        $this->assertGuest();
    }

    public function test_logout_logs_user_out(): void
    {
        $user = $this->panelUser([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)
            ->post(route('filament.admin.auth.logout'))
            ->assertRedirect(route('filament.admin.auth.login'));

        $this->assertGuest();
    }
}
