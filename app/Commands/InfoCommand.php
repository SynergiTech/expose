<?php

namespace App\Commands;

use App\Contracts\FetchesPlatformDataContract;
use App\Traits\FetchesPlatformData;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

use function Termwind\render;

class InfoCommand extends Command implements FetchesPlatformDataContract
{
    use FetchesPlatformData;

    protected $signature = 'info {--json}';

    protected $description = 'Displays the current configuration for Expose.';

    public function handle()
    {

        if (!$this->option('json')) {
            render('<div class="ml-2 text-pink-500 font-bold"><span class="pr-0.5">></span> Expose</div>');
        }

        $configuration = [];

        $configuration = [
            "token" => config('expose.auth_token'),
            "default_server" => config('expose.default_server'),
            "default_domain" => config('expose.default_domain'),
            "plan" => $this->isProToken() ? "pro" : "free",
            "version" => $this->getVersion(),
            "latency" => $this->checkLatency(config('expose.default_server')) . "ms"
        ];

        if ($this->option('json')) {
            $this->line(json_encode($configuration));
            return;
        }

        render('<div class="ml-3 font-bold">Configuration</div>');

        $configuration = collect($configuration)->mapWithKeys(function ($value, $key) {
            return [lineTableLabel($key) => lineTableLabel($value)];
        })->toArray();

        renderLineTable($configuration);
    }

    protected function checkLatency(string $server): int
    {

        if ($server === "free") {
            $host = "sharedwithexpose.com";
        } else {
            $host = "expose.{$server}.sharedwithexpose.com";
        }

        try {
            $startTime = microtime(true);
            Http::timeout(5)->get($host);
            $latency = microtime(true) - $startTime;

            return round($latency * 1000);
        } catch (Exception $e) {
            if ($this->option("verbose")) {
                render("<div class='ml-3 px-2 text-orange-600 bg-orange-100'>Error while checking latency: {$e->getMessage()}</div>");
            }

            return 999;
        }
    }

    protected function getVersion(): string {
        Artisan::call("help --version");
        $version = Artisan::output();
        return 'v'.str()->after($version, "Expose ");
    }

    public function getToken()
    {
        return config('expose.auth_token');
    }
}
