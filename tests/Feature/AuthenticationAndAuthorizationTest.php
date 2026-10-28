<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_PASSWORD = 'Secure-Admin-Pass-123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_are_redirected_from_all_dashboards_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get(route('dashboards.hr-admin'))->assertRedirect(route('login'));
        $this->get(route('dashboards.supervisor'))->assertRedirect(route('login'));
        $this->get(route('dashboards.employee'))->assertRedirect(route('login'));
    }

    public function test_valid_credentials_log_in_and_redirect_to_the_users_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'employee@example.com',
            'password' => Hash::make('Correct-Horse-Battery-1'),
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Correct-Horse-Battery-1',
        ])->assertRedirect(route('dashboards.employee'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_failed_login_uses_the_same_message_for_unknown_email_and_wrong_password(): void
    {
        $message = 'The provided credentials do not match our records.';

        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'unknown@example.com',
            'password' => 'Wrong-Password-123',
        ])->assertSessionHasErrors(['email' => $message]);

        $user = User::factory()->create(['password' => Hash::make('Correct-Password-123')]);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Wrong-Password-123',
        ])->assertSessionHasErrors(['email' => $message]);

        $this->assertGuest();
    }

    public function test_login_regenerates_the_session_identifier(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Correct-Password-123')]);
        $this->withSession(['session_probe' => 'before-login']);
        $previousSessionId = $this->app['session.store']->getId();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Correct-Password-123',
        ])->assertRedirect(route('dashboards.employee'));

        $this->assertNotSame($previousSessionId, $this->app['session.store']->getId());
    }

    public function test_inactive_accounts_cannot_log_in_or_open_a_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Correct-Password-123'),
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Correct-Password-123',
        ])->assertSessionHasErrors('email');

        $this->actingAs($user)
            ->get(route('dashboards.employee'))
            ->assertForbidden();
    }

    public function test_database_defaults_new_accounts_to_employee_and_active(): void
    {
        $user = new User;
        $user->name = 'Default Role User';
        $user->email = 'default.role@example.com';
        $user->password = Hash::make('Correct-Password-123');
        $user->save();

        $this->assertSame(User::ROLE_EMPLOYEE, $user->fresh()->role);
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_logout_ends_the_session_and_regenerates_the_csrf_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $session = $this->app['session.store'];
        $session->start();
        $previousSessionId = $session->getId();
        $previousToken = $session->token();

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNotSame($previousSessionId, $this->app['session.store']->getId());
        $this->assertNotSame($previousToken, $this->app['session.store']->token());
    }

    public function test_public_registration_routes_do_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_each_role_can_open_its_own_dashboard(): void
    {
        $roleRoutes = [
            User::ROLE_HR_ADMIN => ['dashboards.hr-admin', 'HR Admin Dashboard'],
            User::ROLE_SUPERVISOR => ['dashboards.supervisor', 'Supervisor Dashboard'],
            User::ROLE_EMPLOYEE => ['dashboards.employee', 'Employee Dashboard'],
        ];

        foreach ($roleRoutes as $role => [$route, $heading]) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route($route))
                ->assertOk()
                ->assertSee($heading);
        }
    }

    public function test_shared_navigation_shows_links_for_the_authenticated_role_only(): void
    {
        $admin = User::factory()->create([
            'name' => 'Aarav HR Admin',
            'role' => User::ROLE_HR_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboards.hr-admin'))
            ->assertOk()
            ->assertSee('Aarav HR Admin')
            ->assertSee('HR Admin', false)
            ->assertSee('id="navigation-toggle"', false)
            ->assertSee('aria-label="Toggle navigation"', false)
            ->assertSee('aria-controls="primary-navigation"', false)
            ->assertSee('id="navigation-close"', false)
            ->assertSee('id="navigation-backdrop"', false)
            ->assertSee('id="primary-navigation"', false)
            ->assertSee('aria-label="Overview"', false)
            ->assertSee('title="Employees"', false)
            ->assertSee('aria-label="Log out"', false)
            ->assertSee(route('hr-admin.employees.index'), false)
            ->assertSee(route('hr-admin.departments.index'), false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('method="POST"', false);

        $employee = User::factory()->create([
            'name' => 'Employee Workspace User',
            'role' => User::ROLE_EMPLOYEE,
        ]);

        $this->actingAs($employee)
            ->get(route('dashboards.employee'))
            ->assertOk()
            ->assertSee('Employee Workspace User')
            ->assertSee('My workspace')
            ->assertDontSee(route('hr-admin.employees.index'), false)
            ->assertDontSee(route('hr-admin.departments.index'), false);

        $supervisor = User::factory()->create([
            'name' => 'Supervisor Workspace User',
            'role' => User::ROLE_SUPERVISOR,
        ]);

        $this->actingAs($supervisor)
            ->get(route('dashboards.supervisor'))
            ->assertOk()
            ->assertSee('Supervisor Workspace User')
            ->assertSee('Team workspace')
            ->assertDontSee(route('hr-admin.employees.index'), false)
            ->assertDontSee(route('hr-admin.departments.index'), false);
    }

    public function test_employees_and_supervisors_cannot_open_other_role_dashboards(): void
    {
        $deniedRoutes = [
            User::ROLE_EMPLOYEE => ['dashboards.hr-admin', 'dashboards.supervisor'],
            User::ROLE_SUPERVISOR => ['dashboards.hr-admin', 'dashboards.employee'],
        ];

        foreach ($deniedRoutes as $role => $routes) {
            $user = User::factory()->create(['role' => $role]);

            foreach ($routes as $route) {
                $this->actingAs($user)
                    ->get(route($route))
                    ->assertForbidden();
            }
        }
    }

    public function test_login_and_mass_assignment_cannot_change_a_users_role(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'password' => Hash::make('Correct-Password-123'),
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Correct-Password-123',
            'role' => User::ROLE_HR_ADMIN,
        ])->assertRedirect(route('dashboards.employee'));

        $user->fill(['role' => User::ROLE_HR_ADMIN]);
        $user->save();

        $this->assertSame(User::ROLE_EMPLOYEE, $user->fresh()->role);
        $this->actingAs($user)
            ->post('/profile', ['role' => User::ROLE_HR_ADMIN])
            ->assertNotFound();
    }

    public function test_first_admin_command_creates_a_hashed_hr_admin_account(): void
    {
        $this->artisan('app:create-first-admin')
            ->expectsQuestion('Administrator name', 'Initial HR Admin')
            ->expectsQuestion('Administrator email', 'first.admin@example.com')
            ->expectsQuestion('Password (at least 12 characters)', self::ADMIN_PASSWORD)
            ->expectsQuestion('Confirm password', self::ADMIN_PASSWORD)
            ->expectsOutput('The initial HR Admin account was created.')
            ->assertExitCode(0);

        $user = User::query()->where('email', 'first.admin@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_HR_ADMIN, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check(self::ADMIN_PASSWORD, $user->password));
        $this->assertNotSame(self::ADMIN_PASSWORD, $user->password);
    }

    public function test_first_admin_command_rejects_invalid_email_and_short_password(): void
    {
        $this->artisan('app:create-first-admin')
            ->expectsQuestion('Administrator name', 'Initial HR Admin')
            ->expectsQuestion('Administrator email', 'not-an-email')
            ->expectsQuestion('Password (at least 12 characters)', 'short')
            ->expectsQuestion('Confirm password', 'short')
            ->expectsOutputToContain('The email field must be a valid email address.')
            ->expectsOutputToContain('The password field must be at least 12 characters.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_first_admin_command_rejects_password_confirmation_mismatch(): void
    {
        $this->artisan('app:create-first-admin')
            ->expectsQuestion('Administrator name', 'Initial HR Admin')
            ->expectsQuestion('Administrator email', 'first.admin@example.com')
            ->expectsQuestion('Password (at least 12 characters)', self::ADMIN_PASSWORD)
            ->expectsQuestion('Confirm password', 'Different-Admin-Pass-123')
            ->expectsOutput('The password confirmation does not match.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_first_admin_command_refuses_to_create_a_second_hr_admin(): void
    {
        User::factory()->create(['role' => User::ROLE_HR_ADMIN]);

        $this->artisan('app:create-first-admin')
            ->expectsOutput('An HR Admin account already exists.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 1);
    }
}
