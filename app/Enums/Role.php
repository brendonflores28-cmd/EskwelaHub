<?php

namespace App\Enums;

/**
 * The three portals of EskwelaHub. The value is the Spatie role name.
 */
enum Role: string
{
    case StudentParent = 'student_parent'; // students, parents, guardians and alumni
    case Staff = 'staff';                  // registrar, clerk, administrative officer
    case Admin = 'admin';                  // principal / system administrator

    public function label(): string
    {
        return match ($this) {
            self::StudentParent => 'Student / Parent / Alumni',
            self::Staff => 'Registrar / Staff',
            self::Admin => 'Principal / Admin',
        };
    }

    /** Roles that work with school records and must use two-factor authentication. */
    public static function staffRoles(): array
    {
        return [self::Staff->value, self::Admin->value];
    }
}
