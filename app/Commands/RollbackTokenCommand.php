<?php

namespace App\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\confirm;
use function Termwind\render;

class RollbackTokenCommand extends Command
{

    protected $signature = 'token:rollback';

    protected $description = 'Rollback the Expose token and setup to the previous version, if applicable.';

    public function handle() {

        $previousSetupPath = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
            '.expose',
            'previous_setup.json',
        ]);

        render('<div class="ml-2 text-pink-500 font-bold"><span class="pr-0.5">></span> Expose</div>');

        if(!file_exists($previousSetupPath)) {
            render('<div class="ml-3 px-2 text-orange-600 bg-orange-100">No previous setup found.</div>');
            return;
        }

        render('<div class="ml-3 font-bold">Previous Setup</div>');

        $previousSetup = json_decode(file_get_contents($previousSetupPath), true);

        $previousSetupTable = collect($previousSetup)->mapWithKeys(function ($value, $key) {
            return [lineTableLabel($key) => lineTableLabel($value)];
        })->toArray();

        renderLineTable($previousSetupTable);

        if(!confirm("Do you want to rollback your Expose setup to the previous state?", false)) {
            return;
        }

        $token = $previousSetup['token'];

        Artisan::call("token $token --no-interaction");

        render("<div class='ml-3'>✔ Set Expose token to <span class='font-bold'>$token</span>.</div>");

        if($domain = $previousSetup['default_domain']) {
            Artisan::call("default-domain $domain");
        }

        if($server = $previousSetup['default_server']) {
            Artisan::call("default-server $server");
        }

        Artisan::output();


    }
}
