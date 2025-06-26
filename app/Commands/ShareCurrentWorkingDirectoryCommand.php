<?php

namespace App\Commands;

use App\Client\Factory;
use React\EventLoop\LoopInterface;
use function Clue\React\Block\await;

class ShareCurrentWorkingDirectoryCommand extends ShareCommand
{
    protected $signature = 'share-cwd {host?} {--subdomain=} {--auth=} {--basicAuth=} {--dns=} {--domain=}';

    public function handle()
    {
        $folderName = $this->detectName();

        $this->input->setArgument('host', 'localhost');

        if (! $this->option('subdomain')) {
            $this->input->setOption('subdomain', strtolower(str_replace(['.', ' '], '-', $folderName)));
        }

        parent::handle();
    }

    protected function detectName(): string
    {
        $auth = $this->option('auth') ?? config('expose.auth_token', '');

        (new Factory())
            ->setLoop(app(LoopInterface::class))
            ->setHost($this->getServerHost())
            ->setPort($this->getServerPort())
            ->setAuth($auth)
            ->createClient();

        try {
            await(app('expose.client')->getUsernameForAuthToken($this->getServerPort(), $auth));
        } catch (\Throwable $e) {
            if ($e->getMessage() !== 'Closed') {
                throw $e;
            }
        }

        return cache()->get('expose_username', get_current_user()) . '-' . basename(getcwd());
    }
}
