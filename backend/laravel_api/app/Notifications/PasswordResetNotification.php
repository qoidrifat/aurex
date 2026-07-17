<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification
{
    use Queueable;

    public string $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $resetUrl = config('app.url') . '/api/v1/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Reset Password AUREX')
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Anda menerima email ini karena kami menerima permintaan reset password untuk akun AUREX Anda.')
            ->action('Reset Password', $resetUrl)
            ->line('Token reset password Anda: **' . $this->token . '**')
            ->line('Link ini akan kadaluarsa dalam 60 menit.')
            ->line('Jika Anda tidak meminta reset password, abaikan email ini.')
            ->salutation('Terima kasih, Tim AUREX');
    }
}
