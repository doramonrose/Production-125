<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WithdrawProceedNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array{
     *   display_code:string,
     *   subject_text:string,
     *   recipient_name:string,
     *   applicant_text:string,
     *   amount_text:string,
     *   action_url:string
     * } $payload
     */
    public function __construct(public array $payload)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine()
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.withdraw.proceed-notification',
            with: [
                'payload' => $this->payload,
            ]
        );
    }

    public function subjectLine(): string
    {
        $customSubject = trim((string) ($this->payload['mail_subject'] ?? ''));
        if ($customSubject !== '') {
            return $customSubject;
        }

        return 'แจ้งเตือนรายการเบิกจ่ายทั่วไปรอตรวจสอบ #'.($this->payload['display_code'] ?? '-');
    }

    public function plainMessage(): string
    {
        $recipientName = (string) ($this->payload['recipient_name'] ?? '-');
        $subjectText = (string) ($this->payload['subject_text'] ?? 'เรื่อง -');
        $applicantText = (string) ($this->payload['applicant_text'] ?? '-');
        $amountText = (string) ($this->payload['amount_text'] ?? '0.00');
        $displayCode = (string) ($this->payload['display_code'] ?? '-');
        $actionUrl = (string) ($this->payload['action_url'] ?? '');
        $introMessage = trim((string) ($this->payload['intro_message'] ?? ''));
        if ($introMessage === '') {
            $introMessage = 'มีรายการเบิกจ่ายทั่วไป รอการตรวจสอบจากท่าน';
        }
        $extraNote = trim((string) ($this->payload['extra_note'] ?? ''));

        $message = 'เรียน '.$recipientName
            ."\n\n"
            .$introMessage.' - '.$subjectText.' ผู้ขอเบิก '.$applicantText.' จำนวนเงิน '.$amountText
            ."\n\n"
            .'เลขที่รายการ: '.$displayCode
            ."\n"
            .'ลิงก์ตรวจสอบ: '.$actionUrl;

        if ($extraNote !== '') {
            $message .= "\n".'หมายเหตุ: '.$extraNote;
        }

        return $message;
    }
}
