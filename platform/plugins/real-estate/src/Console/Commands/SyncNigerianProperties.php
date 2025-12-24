<?php

namespace Botble\RealEstate\Console\Commands;

use Botble\RealEstate\Models\PropertySyncSource;
use Botble\RealEstate\Services\NigerianPropertySyncService;
use Illuminate\Console\Command;

class SyncNigerianProperties extends Command
{
    protected $signature = 'real-estate:sync-nigerian-properties 
                            {source? : Specific source to sync (propertypro.ng, nigerianpropertycentre.com, tolet.com.ng)}
                            {--limit=50 : Maximum number of properties to sync}
                            {--dry-run : Run without creating properties}';

    protected $description = 'Sync properties from Nigerian real estate websites';

    public function handle(): int
    {
        $sourceName = $this->argument('source');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $service = app(NigerianPropertySyncService::class);

        if ($sourceName) {
            // Sync specific source
            $this->syncSource($service, $sourceName, $limit, $dryRun);
        } else {
            // Sync all enabled sources
            $sources = PropertySyncSource::where('is_enabled', true)->get();

            if ($sources->isEmpty()) {
                $this->warn('No enabled property sync sources found.');
                return Command::FAILURE;
            }

            foreach ($sources as $source) {
                $this->info("\nSyncing from: {$source->name}");
                $this->syncSource($service, $source->name, $limit, $dryRun);
            }
        }

        return Command::SUCCESS;
    }

    protected function syncSource(NigerianPropertySyncService $service, string $sourceName, int $limit, bool $dryRun): void
    {
        try {
            $this->info("Starting sync from {$sourceName} (limit: {$limit})...");

            if ($dryRun) {
                $this->warn('DRY RUN MODE - No properties will be created');
            }

            $result = $service->syncFromSource($sourceName, $limit, $dryRun);

            $this->info("✓ Sync completed:");
            $this->line("  - Fetched: {$result['fetched']} properties");
            $this->line("  - Created: {$result['created']} new properties");
            $this->line("  - Updated: {$result['updated']} existing properties");
            $this->line("  - Skipped: {$result['skipped']} duplicates");

            if (!empty($result['errors'])) {
                $this->error("  - Errors: " . count($result['errors']));
                foreach ($result['errors'] as $error) {
                    $this->error("    • {$error}");
                }
            }
        } catch (\Exception $e) {
            $this->error("Failed to sync from {$sourceName}: {$e->getMessage()}");
        }
    }
}

