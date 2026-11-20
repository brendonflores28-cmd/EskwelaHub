<?php

use App\Models\SessionLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Spatie\Activitylog\Models\Activity;

/*
| Session logs = sign-in events only (login, logout, register, failed login...).
| The audit trail (activity_log) = what users do inside the system. They are separate.
*/

it('logs a successful login with the account, IP and browser', function () {
    $user = User::factory()->create(['email' => 'maria@example.test']);

    $this->withHeader('User-Agent', 'TestBrowser/1.0')
        ->post(route('login.store'), ['email' => 'maria@example.test', 'password' => 'password'])
        ->assertRedirect();

    $log = SessionLog::where('event', 'login')->firstOrFail();
    expect($log->user_id)->toBe($user->id)
        ->and($log->email)->toBe('maria@example.test')
        ->and($log->ip)->not->toBeNull()
        ->and($log->user_agent)->toBe('TestBrowser/1.0');
});

it('logs a failed login but never stores the password', function () {
    $user = User::factory()->create(['email' => 'maria@example.test']);

    $this->post(route('login.store'), ['email' => 'maria@example.test', 'password' => 'my-secret-wrong-password']);
    $this->post(route('login.store'), ['email' => 'nobody@example.test', 'password' => 'my-secret-wrong-password']);

    $logs = SessionLog::where('event', 'login_failed')->orderBy('id')->get();
    expect($logs)->toHaveCount(2)
        ->and($logs[0]->user_id)->toBe($user->id)         // known account
        ->and($logs[1]->user_id)->toBeNull()              // unknown address
        ->and($logs[1]->email)->toBe('nobody@example.test');
    expect(json_encode(SessionLog::all()->toArray()))->not->toContain('my-secret-wrong-password');
});

it('logs a lockout after too many failed tries', function () {
    User::factory()->create(['email' => 'maria@example.test']);

    foreach (range(1, 6) as $i) {
        $this->post(route('login.store'), ['email' => 'maria@example.test', 'password' => 'wrong']);
    }

    expect(SessionLog::where('event', 'locked_out')->count())->toBeGreaterThanOrEqual(1)
        ->and(SessionLog::where('event', 'locked_out')->first()->email)->toBe('maria@example.test');
});

it('logs logout and registration', function () {
    $this->post(route('register.store'), [
        'name' => 'New Parent', 'email' => 'new@example.test',
        'password' => 'a-long-Password-123!', 'password_confirmation' => 'a-long-Password-123!',
    ]);
    $this->post(route('logout'));

    expect(SessionLog::pluck('event')->all())->toContain('registered', 'logout')
        ->and(SessionLog::where('event', 'registered')->first()->email)->toBe('new@example.test');
});

it('logs password reset and two-step events', function () {
    $user = User::factory()->create();

    event(new PasswordReset($user));
    event(new TwoFactorAuthenticationEnabled($user));
    event(new TwoFactorAuthenticationDisabled($user));
    event(new TwoFactorAuthenticationFailed($user));

    expect(SessionLog::pluck('event')->all())->toEqualCanonicalizing(['password_reset', 'two_factor_enabled', 'two_factor_disabled', 'two_factor_failed']);
});

it('keeps session logs and the audit trail apart', function () {
    $staff = staffUser();
    $student = Student::factory()->create();

    // A login creates a session log and no audit entry (a plain account: staff would be asked for the two-step code first).
    $plain = User::factory()->create();
    $this->post(route('login.store'), ['email' => $plain->email, 'password' => 'password']);
    auth()->logout();
    expect(SessionLog::where('event', 'login')->count())->toBe(1)->and(Activity::where('log_name', 'access')->count())->toBe(0);

    // Viewing a record creates an audit entry and no session log.
    $before = SessionLog::count();
    $this->actingAs($staff)->get(route('staff.students.show', $student))->assertOk();
    expect(Activity::where('event', 'viewed')->count())->toBe(1)->and(SessionLog::count())->toBe($before);
});

it('shows the session logs to the principal only', function () {
    $this->get(route('admin.session-logs'))->assertRedirect(route('login'));
    $this->actingAs(parentUser())->get(route('admin.session-logs'))->assertForbidden();
    $this->actingAs(staffUser())->get(route('admin.session-logs'))->assertForbidden();

    $this->actingAs(adminUser())->get(route('admin.session-logs'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/SessionLogs')->has('events')->has('recent'));
});

it('filters session logs by event and by email or IP', function () {
    SessionLog::create(['event' => 'login', 'email' => 'maria@example.test', 'ip' => '10.0.0.5']);
    SessionLog::create(['event' => 'login_failed', 'email' => 'juan@example.test', 'ip' => '10.0.0.9']);
    SessionLog::create(['event' => 'logout', 'email' => 'maria@example.test', 'ip' => '10.0.0.5']);
    $this->actingAs(adminUser());

    $this->get(route('admin.session-logs', ['event' => 'login_failed']))
        ->assertInertia(fn ($page) => $page->has('logs.data', 1)->where('logs.data.0.email', 'juan@example.test'));
    $this->get(route('admin.session-logs', ['q' => 'maria']))
        ->assertInertia(fn ($page) => $page->where('logs.total', 2));
    $this->get(route('admin.session-logs', ['q' => '10.0.0.9']))
        ->assertInertia(fn ($page) => $page->where('logs.total', 1));
});

it('pages the session logs 25 at a time, newest first', function () {
    foreach (range(1, 30) as $i) {
        SessionLog::create(['event' => 'login', 'email' => "user{$i}@example.test"]);
    }

    $this->actingAs(adminUser())->get(route('admin.session-logs'))
        ->assertInertia(fn ($page) => $page->has('logs.data', 25)->where('logs.last_page', 2)->where('logs.data.0.email', 'user30@example.test'));
    $this->get(route('admin.session-logs', ['page' => 2]))
        ->assertInertia(fn ($page) => $page->has('logs.data', 5));
});

it('deletes session logs older than a year', function () {
    $old = SessionLog::create(['event' => 'login', 'email' => 'old@example.test']);
    $old->forceFill(['created_at' => now()->subYears(2)])->save();
    SessionLog::create(['event' => 'login', 'email' => 'new@example.test']);

    $this->artisan('model:prune', ['--model' => [SessionLog::class]])->assertSuccessful();

    expect(SessionLog::pluck('email')->all())->toBe(['new@example.test']);
});

it('does not log anything when events are faked away (listener only reacts to auth events)', function () {
    Event::fake();

    event(new PasswordReset(User::factory()->create()));

    expect(SessionLog::count())->toBe(0); // the listener did not run because events were faked
});
