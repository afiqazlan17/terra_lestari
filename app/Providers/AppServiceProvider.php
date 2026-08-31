<?php

namespace App\Providers;

use App\Http\Responses\PasskeyLoginResponse;
use Illuminate\Filesystem\LocalFilesystemAdapter as IlluminateLocalFilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PathPrefixing\PathPrefixedAdapter;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PasskeyLoginResponseContract::class, PasskeyLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // This host does not have PHP's fileinfo extension installed, so
        // League\MimeTypeDetection's default FinfoMimeTypeDetector fatals
        // with "Class finfo not found" the moment anything writes to the
        // local/public disk. Rebuild the local disk driver with an
        // extension-based detector instead (mirrors Laravel's own
        // FilesystemManager::createLocalDriver(), swapping only the
        // mime type detector).
        Storage::extend('local', function ($app, array $config) {
            $visibility = PortableVisibilityConverter::fromArray(
                $config['permissions'] ?? [],
                $config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE
            );

            $links = ($config['links'] ?? null) === 'skip'
                ? LocalFilesystemAdapter::SKIP_LINKS
                : LocalFilesystemAdapter::DISALLOW_LINKS;

            $adapter = new LocalFilesystemAdapter(
                $config['root'],
                $visibility,
                $config['lock'] ?? LOCK_EX,
                $links,
                new ExtensionMimeTypeDetector,
            );

            $flysystemAdapter = $adapter;

            if ($config['read-only'] ?? false) {
                $flysystemAdapter = new ReadOnlyFilesystemAdapter($flysystemAdapter);
            }

            if (! empty($config['prefix'])) {
                $flysystemAdapter = new PathPrefixedAdapter($flysystemAdapter, $config['prefix']);
            }

            $flysystem = new Filesystem($flysystemAdapter, Arr::only($config, [
                'directory_visibility',
                'disable_asserts',
                'retain_visibility',
                'temporary_url',
                'url',
                'visibility',
            ]));

            return (new IlluminateLocalFilesystemAdapter($flysystem, $adapter, $config))
                ->shouldServeSignedUrls(
                    $config['serve'] ?? false,
                    fn () => $app['url'],
                );
        });
    }
}
