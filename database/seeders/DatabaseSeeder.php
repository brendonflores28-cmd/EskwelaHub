<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles and permissions are safe everywhere, including production.
        $this->call(RolePermissionSeeder::class);
        $this->call(FaqSeeder::class); // chatbot answers: content, safe in production (the school must review them)

        // Fake students and demo accounts with a known password: local only.
        if (app()->isProduction()) {
            $this->command?->warn('Production: skipped fake data. Create accounts with `php artisan eskwelahub:create-user`.');

            return;
        }

        $this->call([
            SchoolDataSeeder::class,
            DemoUserSeeder::class,
        ]);
    }
}
