<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailService
{
    private MailerInterface $mailer;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        MailerInterface $mailer,
        string $brevoSenderEmail,
        string $brevoSenderName
    ) {
        $this->mailer      = $mailer;
        $this->senderEmail = $brevoSenderEmail;
        $this->senderName  = $brevoSenderName;
    }

    public function sendEmail(string $toEmail, string $toName, string $subject, string $htmlContent): bool
    {
        try {
            $email = (new Email())
                ->from($this->senderEmail)
                ->to($toEmail)
                ->subject($subject)
                ->html($htmlContent);

            $this->mailer->send($email);
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }
}