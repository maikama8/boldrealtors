<?php

namespace Botble\RealEstate\Notifications;

use Botble\RealEstate\Models\SavedSearch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SavedSearchAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SavedSearch $savedSearch,
        public int $newResultsCount
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $searchName = $this->savedSearch->name ?: 'Your saved search';
        $criteria = $this->savedSearch->criteria;

        $message = (new MailMessage)
            ->subject("New Properties Match Your Search: {$searchName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("We found {$this->newResultsCount} new property(ies) that match your saved search criteria.");

        // Build search criteria summary
        $criteriaSummary = [];
        if (isset($criteria['bedrooms'])) {
            $criteriaSummary[] = "{$criteria['bedrooms']} bedroom(s)";
        }
        if (isset($criteria['bathrooms'])) {
            $criteriaSummary[] = "{$criteria['bathrooms']} bathroom(s)";
        }
        if (isset($criteria['min_price'])) {
            $criteriaSummary[] = "From ₦" . number_format($criteria['min_price']);
        }
        if (isset($criteria['max_price'])) {
            $criteriaSummary[] = "Up to ₦" . number_format($criteria['max_price']);
        }
        if (isset($criteria['location'])) {
            $criteriaSummary[] = "in {$criteria['location']}";
        }

        if (!empty($criteriaSummary)) {
            $message->line('Search Criteria: ' . implode(', ', $criteriaSummary));
        }

        $message->action('View New Properties', route('public.properties', array_merge($criteria, ['saved_search_id' => $this->savedSearch->id])))
            ->line('Thank you for using our platform!');

        return $message;
    }
}

