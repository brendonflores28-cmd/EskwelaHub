<?php

namespace App\Enums;

enum EnrollmentType: string
{
    case Grade7 = 'grade7';         // new learner entering Grade 7
    case Transferee = 'transferee'; // coming from another school into Grades 7-12

    public function label(): string
    {
        return $this === self::Grade7 ? 'New Grade 7 student' : 'Transferee';
    }
}
