<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcedPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private const TEMPORARY_PASSWORD = 'Temporary-Password-123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_are_redirected_to_login_from_password_change_actions(): void
    {
        $this->get(route('password.change.edit'))->assertRedirect(route('login'));
        $this->put(route('password.change.update'), [])->assertRedirect(route('login'));
    }

    public function test_flagged_employee_is_sent_to_password_change_after_login(): void
    {
        $user = $this->createUser(mustChangePassword: true);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => self::TEMPORARY_PASSWORD,
        ])->assertRedirect(route('password.change.edit'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('password.change.edit'))->assertOk()->assertSee('Change your password');
    }

    public function test_flagged_users_are_blocked_from_dashboards_department_and_employee_routes(): void
    {
        $user = $this->createUser(mustChangePassword: true);

        foreach ([
            route('dashboard'),
            route('dashboards.employee'),
            route('hr-admin.departments.index'),
            route('hr-admin.employees.index'),
        ] as $url) {
            $this->actingAs($user)->get($url)->assertRedirect(route('password.change.edit'));
        }
    }

    public function test_logout_remains_available_while_password_change_is_required(): void
    {
        $user = $this->createUser(mustChangePassword: true);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_incorrect_current_password_is_rejected_without_clearing_the_flag(): void
    {
        $user = $this->createUser(mustChangePassword: true);

        $this->actingAs($user)
            ->put(route('password.change.update'), [
                'current_password' => 'Incorrect-Password-123',
                'password' => 'New-Secure-Password-456',
                'password_confirmation' => 'New-Secure-Password-456',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check(self::TEMPORARY_PASSWORD, $user->fresh()->password));
    }

    public function test_password_confirmation_and_minimum_length_are_enforced(): void
    {
        $user = $this->createUser(mustChangePassword: true);

        $this->actingAs($user)
            ->put(route('password.change.update'), [
                'current_password' => self::TEMPORARY_PASSWORD,
                'password' => 'Too-Short',
                'password_confirmation' => 'Too-Short',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($user)
            ->put(route('password.change.update'), [
                'current_password' => self::TEMPORARY_PASSWORD,
                'password' => 'New-Secure-Password-456',
                'password_confirmation' => 'Different-Secure-Password-456',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check(self::TEMPORARY_PASSWORD, $user->fresh()->password));
    }

    public function test_successful_change_hashes_password_clears_flag_and_regenerates_session(): void
    {
        $user = $this->createUser(mustChangePassword: true);
        $this->actingAs($user)->withSession(['session_probe' => 'before-change']);
        $previousSessionId = $this->app['session.store']->getId();

        $this->put(route('password.change.update'), [
            'current_password' => self::TEMPORARY_PASSWORD,
            'password' => 'New-Secure-Password-456',
            'password_confirmation' => 'New-Secure-Password-456',
            'must_change_password' => false,
        ])->assertRedirect(route('dashboards.employee'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('New-Secure-Password-456', $user->password));
        $this->assertFalse(Hash::check(self::TEMPORARY_PASSWORD, $user->password));
        $this->assertNotSame('New-Secure-Password-456', $user->password);
        $this->assertNotSame($previousSessionId, $this->app['session.store']->getId());
        $this->get(route('dashboards.employee'))->assertOk();
    }

    public function test_failed_validation_does_not_clear_flag_even_if_request_submits_false(): void
    {
        $user = $this->createUser(mustChangePassword: true);

        $this->actingAs($user)
            ->put(route('password.change.update'), [
                'current_password' => 'wrong',
                'password' => 'New-Secure-Password-456',
                'password_confirmation' => 'New-Secure-Password-456',
                'must_change_password' => false,
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_unflagged_hr_admin_login_and_dashboard_are_unchanged(): void
    {
        $user = $this->createUser(User::ROLE_HR_ADMIN, mustChangePassword: false);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => self::TEMPORARY_PASSWORD,
        ])->assertRedirect(route('dashboards.hr-admin'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboards.hr-admin'))->assertOk();
    }

    private function createUser(string $role = User::ROLE_EMPLOYEE, bool $mustChangePassword = false): User
    {
        return User::factory()->create([
            'role' => $role,
            'password' => Hash::make(self::TEMPORARY_PASSWORD),
            'must_change_password' => $mustChangePassword,
        ]);
    }
}
