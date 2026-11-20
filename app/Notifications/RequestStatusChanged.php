<?php

namespace App\Notifications;

use App\Enums\RequestStatus;
use App\Models\DocumentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells the requester that their document request moved to a new status. Queued. */
class RequestStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DocumentRequest $documentRequest, public RequestStatus $status)
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
        $request = $this->documentRequest;

        $mail = (new MailMessage)
            ->subject("{$request->request_no}: {$this->headline()}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Request **{$request->request_no}** for **{$request->document_type->label()}**: {$this->headline()}.");

        if ($request->remarks && $this->status === RequestStatus::Rejected) {
            $mail->line("Reason: {$request->remarks}");
        }

        if ($this->status === RequestStatus::Ready) {
            $mail->line('Your document is ready. The registrar will release it to you.');
        }
        if ($this->status === RequestStatus::Released) {
            $mail->line('You can now download your document from your account.');
        }

        return $mail
            ->action('Open my request', route('portal.requests.show', $request))
            ->line(config('eskwelahub.school_name'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->documentRequest->id,
            'request_no' => $this->documentRequest->request_no,
            'status' => $this->status->value,
            'message' => $this->headline(),
        ];
    }

    private function headline(): string
    {
        return match ($this->status) {
            RequestStatus::Submitted => 'we received your request',
            RequestStatus::UnderReview => 'the registrar is reviewing it',
            RequestStatus::Approved => 'approved',
            RequestStatus::Rejected => 'not approved',
            RequestStatus::Processing => 'your document is being prepared',
            RequestStatus::Ready => 'ready for release',
            RequestStatus::Released => 'released',
        };
    }
}
