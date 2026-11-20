<?php

use App\Enums\LinkStatus;
use App\Enums\Role;
use App\Models\Student;
use App\Models\StudentLink;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pest configuration
|--------------------------------------------------------------------------
| Feature tests get the Laravel app, a fresh database and the three roles.
| The existing PHPUnit-style tests from the starter kit keep working.
*/
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => test()->seed(RolePermissionSeeder::class))
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/** Registrar account that already confirmed two-factor authentication. */
function staffUser(): User
{
    $user = User::factory()->withTwoFactor()->create();
    $user->assignRole(Role::Staff->value);

    return $user;
}

/** Principal / admin account that already confirmed two-factor authentication. */
function adminUser(): User
{
    $user = User::factory()->withTwoFactor()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

/** Student / parent account, optionally approved to see the given student. */
function parentUser(?Student $linkedTo = null): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::StudentParent->value);

    if ($linkedTo) {
        StudentLink::create([
            'user_id' => $user->id,
            'student_id' => $linkedTo->id,
            'relationship' => 'parent',
            'status' => LinkStatus::Approved,
        ]);
    }

    return $user;
}
