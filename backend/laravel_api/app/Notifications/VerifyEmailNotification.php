<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Get the verification URL for the given notifiable.
     * Override untuk membuat URL API-friendly.
     */
    protected function verificationUrl($notifiable): string
    {
        $hash = sha1($notifiable->getEmailForVerification());
        $id = $notifiable->getKey();

        return config('app.url') . "/api/verify-email/$id/$hash";
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifikasi Email AUREX')
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Terima kasih telah mendaftar di AUREX.')
            ->line('Silakan klik tombol di bawah untuk memverifikasi alamat email Anda.')
            ->action('Verifikasi Email', $verificationUrl)
            ->line('Jika Anda tidak membuat akun ini, abaikan email ini.')
            ->salutation('Terima kasih, Tim AUREX');
    }
}
