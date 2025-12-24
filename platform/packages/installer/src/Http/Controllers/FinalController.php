<?php

namespace Botble\Installer\Http\Controllers;

use Botble\Base\Facades\BaseHelper;
use Botble\Installer\Events\InstallerFinished;
use Botble\Installer\Services\CleanupSystemAfterInstalledService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Throwable;

class FinalController
{
    public function index(
        Request $request,
        CleanupSystemAfterInstalledService $cleanupSystemAfterInstalledService
    ): View|RedirectResponse {
        if (! URL::hasValidSignature($request)) {
            return redirect()->route('installers.requirements.index');
        }

        File::delete(storage_path('installing'));

        try {
            $files = collect(File::files(base_path()))
                ->filter(function ($file) {
                    $fileName = $file->getFilename();

                    if (in_array($fileName, ['database.sql', 'readme.txt', 'document.zip', 'docker-compose.yml'])) {
                        return true;
                    }

                    return str_starts_with($fileName, 'database') && $file->getExtension() === 'sql';
                })
                ->map(function ($file) {
                    return $file->getFilename();
                })
                ->all();

            if (! empty($files)) {
                foreach ($files as $file) {
                    File::delete(base_path($file));
                }
            }

        } catch (Throwable) {
            // do nothing
        }

        BaseHelper::saveFileData(storage_path('installed'), Carbon::now()->toDateTimeString());

        $cleanupSystemAfterInstalledService->handle();

        event(new InstallerFinished());

        // Get installation details from environment
        $installationDetails = $this->getInstallationDetails();

        return view('packages/installer::final', compact('installationDetails'));
    }

    protected function getInstallationDetails(): array
    {
        $details = [
            'website' => [
                'name' => config('app.name', 'Zarachtech'),
                'url' => config('app.url', url('')),
            ],
            'database' => [
                'connection' => config('database.default', 'mysql'),
                'host' => config('database.connections.mysql.host', '127.0.0.1'),
                'port' => config('database.connections.mysql.port', '3306'),
                'database' => config('database.connections.mysql.database', ''),
                'username' => config('database.connections.mysql.username', ''),
                'password' => config('database.connections.mysql.password') ? '••••••••' : '',
            ],
        ];

        // Try to get actual database connection details
        try {
            $connection = DB::connection();
            $config = $connection->getConfig();
            
            $details['database'] = [
                'connection' => config('database.default', 'mysql'),
                'host' => $config['host'] ?? '127.0.0.1',
                'port' => $config['port'] ?? '3306',
                'database' => $config['database'] ?? '',
                'username' => $config['username'] ?? '',
                'password' => isset($config['password']) && !empty($config['password']) ? '••••••••' : '',
            ];
        } catch (Throwable $e) {
            // If database connection fails, use config values
        }

        return $details;
    }
}
