<?php

namespace Database\Seeders;

use App\Enums\LinkStatus;
use App\Enums\Role;
use App\Models\Student;
use App\Models\StudentLink;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * FAKE demo accounts for local testing (addresses end in .test, no real mailbox).
 * The password comes from SEED_PASSWORD in .env, default "password".
 * Staff and admin must still turn on 2FA in Settings > Security before they
 * can open the staff portal (set REQUIRE_STAFF_2FA=false locally to skip it).
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SEED_PASSWORD', 'password');

        $accounts = [
            ['Demo Principal', 'admin@eskwelahub.test', Role::Admin],
            ['Demo Registrar', 'staff@eskwelahub.test', Role::Staff],
            ['Demo Parent', 'parent@eskwelahub.test', Role::StudentParent],
            ['Demo Alumni', 'alumni@eskwelahub.test', Role::StudentParent],
        ];

        $users = [];
        foreach ($accounts as [$name, $email, $role]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => $password, 'email_verified_at' => now()],
            );
            $user->syncRoles([$role->value]);
            $users[$email] = $user;
        }

        // The parent has one approved link and one waiting for staff review.
        $students = Student::whereNotNull('lrn')->orderBy('id')->limit(2)->get();
        if ($students->count() === 2) {
            StudentLink::updateOrCreate(
                ['user_id' => $users['parent@eskwelahub.test']->id, 'student_id' => $students[0]->id],
                ['relationship' => 'parent', 'status' => LinkStatus::Approved, 'reviewed_at' => now()],
            );
            StudentLink::updateOrCreate(
                ['user_id' => $users['alumni@eskwelahub.test']->id, 'student_id' => $students[1]->id],
                ['relationship' => 'self', 'status' => LinkStatus::Pending],
            );
        }
    }
}
