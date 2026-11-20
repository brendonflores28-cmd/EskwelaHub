<?php

namespace App\Services\Enrollment;

use App\Enums\Curriculum;
use App\Enums\EnrollmentStatus;
use App\Enums\EnrollmentType;
use App\Enums\LinkStatus;
use App\Enums\StudentStatus;
use App\Exceptions\InvalidRequestTransition;
use App\Models\EnrollmentApplication;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\StudentLink;
use App\Models\User;
use App\Notifications\EnrollmentDecided;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Online enrollment: submit the form with documents, staff review, then
 * approve (creates or links the student) or reject (reason required).
 */
class EnrollmentWorkflow
{
    public const DISK = 'private';

    /**
     * @param  array<string, mixed>  $data  validated form fields
     * @param  array<string, UploadedFile>  $files  document kind => uploaded file
     */
    public function submit(User $applicant, array $data, array $files): EnrollmentApplication
    {
        $schoolYear = SchoolYear::where('is_current', true)->firstOrFail();

        $application = DB::transaction(function () use ($applicant, $data, $files, $schoolYear): EnrollmentApplication {
            $application = EnrollmentApplication::create([
                ...$data,
                'user_id' => $applicant->id,
                'school_year_id' => $schoolYear->id,
                'grade_level' => $data['type'] === EnrollmentType::Grade7->value ? 7 : $data['grade_level'],
            ]);

            foreach ($files as $kind => $file) {
                $this->storeDocument($application, $kind, $file);
            }

            $this->audit($application, 'submitted', 'Submitted enrollment form', $applicant);

            return $application;
        });

        $applicant->notify(new EnrollmentDecided($application, EnrollmentStatus::Submitted));

        return $application;
    }

    public function startReview(EnrollmentApplication $application, User $staff): EnrollmentApplication
    {
        return $this->decide($application, $staff, EnrollmentStatus::UnderReview, null, 'review_started', 'Started reviewing the enrollment');
    }

    /** Creates (or finds) the student, enrolls them for the school year and links the applicant's account. */
    public function approve(EnrollmentApplication $application, User $staff, ?string $note = null): EnrollmentApplication
    {
        return $this->decide($application, $staff, EnrollmentStatus::Approved, $note, 'approved', 'Approved the enrollment', function (EnrollmentApplication $a) use ($staff): void {
            // A learner who already has a record (same LRN, for example a transferee) is reused, not duplicated.
            $student = $a->lrn ? Student::where('lrn', $a->lrn)->first() : null;

            $student ??= Student::create([
                'lrn' => $a->lrn,
                'last_name' => $a->last_name,
                'first_name' => $a->first_name,
                'middle_name' => $a->middle_name,
                'suffix' => $a->suffix,
                'sex' => $a->sex,
                'birthdate' => $a->birthdate,
                'birthplace' => $a->birthplace,
                'status' => StudentStatus::Enrolled,
            ]);

            $student->enrollments()->updateOrCreate(
                ['school_year_id' => $a->school_year_id],
                ['grade_level' => $a->grade_level, 'curriculum' => Curriculum::K12, 'status' => StudentStatus::Enrolled],
            );

            // The parent or learner who applied can now see the record and request documents.
            StudentLink::updateOrCreate(
                ['user_id' => $a->user_id, 'student_id' => $student->id],
                ['relationship' => $a->relationship, 'status' => LinkStatus::Approved, 'reviewed_by' => $staff->id, 'reviewed_at' => now()],
            );

            $a->student_id = $student->id;
        });
    }

    public function reject(EnrollmentApplication $application, User $staff, string $reason): EnrollmentApplication
    {
        return $this->decide($application, $staff, EnrollmentStatus::Rejected, $reason, 'rejected', 'Rejected the enrollment');
    }

    /** @param  (callable(EnrollmentApplication): void)|null  $apply  extra work inside the same transaction */
    private function decide(
        EnrollmentApplication $application,
        User $staff,
        EnrollmentStatus $to,
        ?string $note,
        string $event,
        string $description,
        ?callable $apply = null,
    ): EnrollmentApplication {
        $updated = DB::transaction(function () use ($application, $staff, $to, $note, $event, $description, $apply): EnrollmentApplication {
            $locked = EnrollmentApplication::query()->lockForUpdate()->findOrFail($application->id);

            // Only open forms can be decided; a decided form is final.
            if (! $locked->status->isOpen() || ($locked->status === $to)) {
                throw new InvalidRequestTransition("An enrollment that is {$locked->status->label()} cannot become {$to->label()}.");
            }

            $locked->status = $to;
            $locked->reviewed_by = $staff->id;
            $locked->reviewed_at = now();
            if ($note !== null) {
                $locked->decision_note = $note;
            }
            if ($apply) {
                $apply($locked);
            }
            $locked->save();

            $this->audit($locked, $event, $description, $staff);

            return $locked;
        });

        if ($to !== EnrollmentStatus::UnderReview) {
            $updated->user->notify(new EnrollmentDecided($updated, $to));
        }

        return $updated;
    }

    private function storeDocument(EnrollmentApplication $application, string $kind, UploadedFile $file): void
    {
        // The stored name is random; the visitor's file name is only kept for display.
        $path = $file->storeAs(
            "enrollments/{$application->id}",
            Str::random(40).'.'.$file->extension(),
            self::DISK,
        );

        $application->documents()->create([
            'kind' => $kind,
            'original_name' => Str::limit(basename($file->getClientOriginalName()), 120, ''),
            'disk_path' => $path,
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'sha256' => hash_file('sha256', Storage::disk(self::DISK)->path($path)),
        ]);
    }

    private function audit(EnrollmentApplication $application, string $event, string $description, User $by): void
    {
        activity('enrollments')
            ->performedOn($application)
            ->causedBy($by)
            ->event($event)
            ->withProperties(['reference_no' => $application->reference_no, 'ip' => request()->ip()])
            ->log($description);
    }
}
