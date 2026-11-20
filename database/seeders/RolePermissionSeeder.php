<?php

namespace Database\Seeders;

use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the three roles and their permissions. Safe to run more than once
 * (also on production: it contains no accounts and no passwords).
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'students.view',    // search and read any student record
            'students.manage',  // create and edit student records
            'students.delete',  // soft delete a record
            'sections.manage',  // create class sections and move learners between them
            'links.review',     // approve or reject account-to-student link requests
            'audit.view',       // read the audit log
            'requests.view',    // see all document requests
            'requests.process', // review, approve, reject, release document requests
            'enrollments.view',     // see online enrollment forms
            'enrollments.process',  // review, approve, reject enrollment forms
            'ocr.use',              // upload scans and verify OCR drafts
            'surveys.view',         // read satisfaction surveys (admin only)
            'sessions.view',        // read the session logs: logins, logouts, registrations, failed logins (admin only)
            'reports.view',         // principal dashboard with enrollment, requests, forecast (admin only)
            'reports.export',       // download the dashboard as Excel (admin only)
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        RoleModel::findOrCreate(Role::StudentParent->value, 'web');

        RoleModel::findOrCreate(Role::Staff->value, 'web')
            ->syncPermissions(['students.view', 'students.manage', 'sections.manage', 'links.review', 'requests.view', 'requests.process', 'enrollments.view', 'enrollments.process', 'ocr.use']);

        RoleModel::findOrCreate(Role::Admin->value, 'web')
            ->syncPermissions($permissions);
    }
}
