<?php

namespace App\Notifications;

use App\Models\Customer;
use App\Services\UserNotificationPreferenceService;
use Illuminate\Notifications\Notification;

class CustomerCreated extends Notification
{
    public function __construct(public Customer $customer) {}

    public function via(object $notifiable): array
    {
        return app(UserNotificationPreferenceService::class)->isEnabled($notifiable, self::class, 'database')
            ? ['database']
            : [];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subject' => 'New customer created',
            'message' => 'A new customer, '.$this->customer->name.', was created.',
            'url' => route('customers.show', $this->customer, false),
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
        ];
    }
}
