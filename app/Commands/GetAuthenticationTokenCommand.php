<?php

namespace App\Commands;

use Illuminate\Console\Command;

use function Termwind\render;

class GetAuthenticationTokenCommand extends Command
{
    protected $signature = 'token:get {--clean}';
    protected $description = 'Retrieve the authentication token to use with Expose.';

    public function handle()
    {
        $token = config('expose.auth_token');

        if (is_null($token)) {
            render('<div class="ml-3 px-2 text-orange-600 bg-orange-100">There is no authentication token specified.</div>');
        } else {
            if ($this->option('no-interaction') === true) {
                $this->line($token);
            } else {
                render('<div class="ml-2 text-pink-500 font-bold"><span class="pr-0.5">></span> Expose</div>');
                render("<div class='ml-3'>Current authentication token: <span class='font-bold'>$token</span></div>");
            }
        }
    }
}
