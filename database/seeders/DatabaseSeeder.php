<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles and permissions are safe everywhere, including production.
        $this->call(RolePermissionSeeder::class);

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
