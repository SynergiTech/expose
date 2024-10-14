<?php

namespace App\Commands;

use App\Commands\Concerns\RendersBanner;
use App\Commands\Concerns\RendersLineTable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\confirm;
use function Termwind\render;

class RollbackTokenCommand extends Command
{
    use RendersBanner, RendersLineTable;

    protected $signature = 'token:rollback';

    protected $description = 'Rollback the Expose token and setup to the previous version, if applicable.';

    protected string $previousSetupPath;

    public function handle()
    {

        $this->previousSetupPath = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.expose',
            'previous_setup.json',
        ]);

        $this->renderBanner();

        if (!file_exists($this->previousSetupPath)) {
            render('<div class="ml-3 px-2 text-orange-600 bg-orange-100">No previous setup found.</div>');
            return;
        }

        render('<div class="ml-3 font-bold">Previous Setup</div>');

        $previousSetup = json_decode(file_get_contents($this->previousSetupPath), true);

        $previousSetupTable = collect($previousSetup)->mapWithKeys(function ($value, $key) {
            return [$this->lineTableLabel($key) => $this->lineTableLabel($value)];
        })->toArray();

        $this->renderLineTable($previousSetupTable);

        if (!confirm("Do you want to rollback your Expose setup to the previous state?", false)) {
            return;
        }

        $this->rememberPreviousSetup();

        $token = $previousSetup['token'];

        Artisan::call("token $token --no-interaction");

        render("<div class='ml-3'>✔ Set Expose token to <span class='font-bold'>$token</span>.</div>");

        if ($domain = $previousSetup['default_domain']) {
            Artisan::call("default-domain $domain");
        }

        if ($server = $previousSetup['default_server']) {
            Artisan::call("default-server $server");
        }

        Artisan::output();
    }


    protected function rememberPreviousSetup()
    {
        $previousSetup = [
            'token' => config('expose.auth_token'),
            'default_server' => config('expose.default_server'),
            'default_domain' => config('expose.default_domain'),
        ];

        file_put_contents($this->previousSetupPath, json_encode($previousSetup));
    }
}
