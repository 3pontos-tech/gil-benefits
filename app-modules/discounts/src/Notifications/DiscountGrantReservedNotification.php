<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Support\DiscountInviteUrl;

final class DiscountGrantReservedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DiscountGrant $grant)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $firstName = Str::of((string) $this->grant->name)->trim()->before(' ')->toString();

        return (new MailMessage)
            ->subject(__('emails.discounts.grant_reserved.subject'))
            ->view('emails.discounts.grant-reserved', [
                'summary' => $this->grant->discount->summary(),
                'greeting' => $firstName !== ''
                    ? __('emails.discounts.grant_reserved.greeting', ['name' => $firstName])
                    : __('emails.discounts.grant_reserved.greeting_generic'),
                'expiresAt' => $this->grant->expires_at->format('d/m/Y'),
                'activationUrl' => DiscountInviteUrl::for($this->grant),
            ]);
    }
}
