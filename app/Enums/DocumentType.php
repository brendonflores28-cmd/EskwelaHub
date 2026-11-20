<?php

namespace App\Enums;

/** Records a learner or alumnus can ask the registrar for. */
enum DocumentType: string
{
    case Form137 = 'form_137';
    case DiplomaCertification = 'diploma_certification';
    case EnrollmentCertification = 'enrollment_certification';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Form137 => 'Form 137 (permanent record)',
            self::DiplomaCertification => 'Diploma / certificate of graduation',
            self::EnrollmentCertification => 'Certificate of enrollment',
            self::Other => 'Other school record',
        };
    }

    /** Heading printed on the generated PDF. */
    public function pdfTitle(): string
    {
        return match ($this) {
            self::Form137 => 'Form 137 - Learner\'s Permanent Record',
            self::DiplomaCertification => 'Certification of Graduation',
            self::EnrollmentCertification => 'Certificate of Enrollment',
            self::Other => 'Certification',
        };
    }
}
