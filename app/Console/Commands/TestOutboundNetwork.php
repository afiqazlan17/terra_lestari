<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('app:test-outbound-network')]
#[Description('One-off diagnostic: check whether outbound HTTPS works from a cron-triggered process on this host (composer install via cron is known to hang/fail here without error)')]
class TestOutboundNetwork extends Command
{
    public function handle(): void
    {
        $targets = [
            'Google (generic)' => 'https://www.google.com',
            'Google APIs (Drive API host)' => 'https://www.googleapis.com/discovery/v1/apis/drive/v3/rest',
        ];

        foreach ($targets as $label => $url) {
            $this->info("Testing: {$label} ({$url})");
            $started = microtime(true);

            try {
                $response = Http::timeout(15)->get($url);
                $elapsed = round(microtime(true) - $started, 2);
                $this->info("  -> OK, status {$response->status()}, {$elapsed}s");
            } catch (\Throwable $e) {
                $elapsed = round(microtime(true) - $started, 2);
                $this->error('  -> FAILED after '.$elapsed.'s: '.$e->getMessage());
            }
        }
    }
}
