<?php

namespace Botble\RealEstate\Console\Commands;

use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\SavedSearch;
use Botble\RealEstate\Notifications\SavedSearchAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessSavedSearchAlerts extends Command
{
    protected $signature = 'real-estate:process-saved-search-alerts 
                            {--frequency=daily : Alert frequency (daily, weekly)}
                            {--dry-run : Run without sending emails}';

    protected $description = 'Process saved search alerts and send notifications to users';

    public function handle(): int
    {
        $frequency = $this->option('frequency');
        $dryRun = $this->option('dry-run');

        $this->info("Processing saved search alerts ({$frequency})...");

        $savedSearches = SavedSearch::query()
            ->where('alert_frequency', $frequency)
            ->whereNotNull('account_id')
            ->with('account')
            ->get();

        $processed = 0;
        $notified = 0;

        foreach ($savedSearches as $savedSearch) {
            $processed++;

            // Get properties matching search criteria
            $matchingProperties = $this->getMatchingProperties($savedSearch);

            // Count new properties (created after last notification)
            $newCount = $matchingProperties->filter(function ($property) use ($savedSearch) {
                if (!$savedSearch->last_notified_at) {
                    return true;
                }
                return $property->created_at > $savedSearch->last_notified_at;
            })->count();

            if ($newCount > 0) {
                if (!$dryRun) {
                    // Send notification
                    $savedSearch->account->notify(
                        new SavedSearchAlertNotification($savedSearch, $newCount)
                    );

                    // Update saved search
                    $savedSearch->update([
                        'new_results_count' => $newCount,
                        'last_notified_at' => now(),
                    ]);
                }

                $notified++;
                $this->info("  ✓ Notified {$savedSearch->account->name} about {$newCount} new properties");
            } else {
                $this->line("  - No new properties for {$savedSearch->account->name}");
            }
        }

        $this->info("\nProcessed: {$processed} searches");
        $this->info("Notified: {$notified} users");

        if ($dryRun) {
            $this->warn("\n⚠ DRY RUN MODE - No emails were sent");
        }

        return Command::SUCCESS;
    }

    protected function getMatchingProperties(SavedSearch $savedSearch)
    {
        $criteria = $savedSearch->criteria;
        $query = Property::query()->where('moderation_status', 'approved');

        // Apply search criteria
        if (isset($criteria['bedrooms'])) {
            $query->where('number_bedroom', '>=', $criteria['bedrooms']);
        }

        if (isset($criteria['bathrooms'])) {
            $query->where('number_bathroom', '>=', $criteria['bathrooms']);
        }

        if (isset($criteria['min_price'])) {
            $query->where('price', '>=', $criteria['min_price']);
        }

        if (isset($criteria['max_price'])) {
            $query->where('price', '<=', $criteria['max_price']);
        }

        if (isset($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }

        if (isset($criteria['city_id'])) {
            $query->where('city_id', $criteria['city_id']);
        }

        if (isset($criteria['state_id'])) {
            $query->where('state_id', $criteria['state_id']);
        }

        if (isset($criteria['location'])) {
            $query->where(function ($q) use ($criteria) {
                $q->where('name', 'like', "%{$criteria['location']}%")
                    ->orWhere('description', 'like', "%{$criteria['location']}%");
            });
        }

        return $query->get();
    }
}

