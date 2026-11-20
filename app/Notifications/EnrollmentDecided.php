<?php

namespace App\Notifications;

use App\Enums\EnrollmentStatus;
use App\Models\EnrollmentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells the applicant that their enrollment form was received, approved or rejected. Queued. */
class EnrollmentDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public EnrollmentApplication $application, public EnrollmentStatus $status)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->application;

        $mail = (new MailMessage)
            ->subject("{$a->reference_no}: {$this->headline()}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Enrollment form **{$a->reference_no}** for **{$a->full_name}**: {$this->headline()}.");

        if ($this->status === EnrollmentStatus::Rejected && $a->decision_note) {
            $mail->line("Reason: {$a->decision_note}");
        }
        if ($this->status === EnrollmentStatus::Approved) {
            $mail->line('Your child is now on the school list. Please follow the registrar\'s instructions for the first day.');
        }

        return $mail
            ->action('Open my enrollment', route('portal.enrollments.show', $a))
            ->line(config('eskwelahub.school_name'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'enrollment_id' => $this->application->id,
            'reference_no' => $this->application->reference_no,
            'status' => $this->status->value,
            'message' => $this->headline(),
        ];
    }

    private function headline(): string
    {
        return match ($this->status) {
            EnrollmentStatus::Submitted => 'we received your form',
            EnrollmentStatus::UnderReview => 'the registrar is reviewing it',
            EnrollmentStatus::Approved => 'approved',
            EnrollmentStatus::Rejected => 'not approved',
        };
    }
}
