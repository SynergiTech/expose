<?php

namespace App\Commands;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\table;
use function Termwind\render;

class ServerListCommand extends Command
{
    const DEFAULT_SERVER_ENDPOINT = 'https://expose.dev/api/servers';

    protected $signature = 'servers {--json}';

    protected $description = 'Set or retrieve the default server to use with Expose.';

    public function handle()
    {

        $servers = collect($this->lookupRemoteServers())->map(function ($server) {
            return [
                'key' => $server['key'],
                'region' => $server['region'],
                'plan' => Str::ucfirst($server['plan']),
            ];
        });

        if($this->option('json')) {
            $this->line($servers->toJson());
            return;
        }

        render('<div class="ml-2 text-pink-500 font-bold"><span class="pr-0.5">></span> Expose</div>');

        render("<div class='ml-3'>You can connect to a specific server with the --server=key option or set this server as default with the default-server command.</div>");

        table(['Key', 'Region', 'Type'], $servers);
    }

    protected function lookupRemoteServers()
    {
        try {
            return Http::withOptions([
                'verify' => false,
            ])->get(config('expose.server_endpoint', static::DEFAULT_SERVER_ENDPOINT))->json();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
