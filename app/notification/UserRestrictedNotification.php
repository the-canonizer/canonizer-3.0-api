<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\CampUserRestriction;
use App\Models\Camp;

class UserRestrictedNotification extends Notification
{
    use Queueable;

    protected $camp;
    protected $restriction;

    public function __construct(Camp $camp, CampUserRestriction $restriction)
    {
        $this->camp = $camp;
        $this->restriction = $restriction;
    }

    public function via($notifiable)
    {
        return ['database', 'mail']; // in-app (DB) + email
    }

    public function toMail($notifiable)
    {
        $end = $this->restriction->end_time->toDateTimeString();
        return (new MailMessage)
            ->subject("You have been restricted from participating in camp: {$this->camp->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been restricted from participating in \"{$this->camp->name}\".")
            ->line("Reason: {$this->restriction->reason}")
            ->line("Restriction duration: until {$end} (approx. 24 hours).")
            ->line("Further violations will reset the 24-hour cooldown and extend your restriction.")
            ->action('View Camp', url("/camps/{$this->camp->id}"))
            ->line('If you believe this is a mistake, contact the camp leader.');
    }

    public function toArray($notifiable)
    {
        return [
            'camp_id' => $this->camp->id,
            'camp_name' => $this->camp->name,
            'reason' => $this->restriction->reason,
            'start_time' => $this->restriction->start_time,
            'end_time' => $this->restriction->end_time,
            'restriction_id' => $this->restriction->id,
        ];
    }
}
