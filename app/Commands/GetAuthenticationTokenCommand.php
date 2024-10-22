<?php

namespace App\Commands;

use App\Commands\Concerns\RendersBanner;
use App\Commands\Concerns\RendersOutput;
use Illuminate\Console\Command;

use function Termwind\render;

class GetAuthenticationTokenCommand extends Command
{
    use RendersBanner, RendersOutput;

    protected $signature = 'token:get';
    protected $description = 'Retrieve the authentication token to use with Expose.';

    public function handle()
    {
        $token = config('expose.auth_token');

        if ($this->option('no-interaction') === true) {
            $this->line($token ?? '');
            return;
        }

        $this->renderBanner();

        if (is_null($token)) {
            $this->renderWarning('There is no authentication token specified.');
        } else {
            render("<div class='ml-3'>Current authentication token: <span class='font-bold'>$token</span></div>");
        }
    }
}
