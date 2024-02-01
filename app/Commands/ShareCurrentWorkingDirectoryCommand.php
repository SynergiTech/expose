<?php

namespace App\Commands;

class ShareCurrentWorkingDirectoryCommand extends ShareCommand
{
    protected $signature = 'share-cwd {host?} {--subdomain=} {--auth=} {--basicAuth=} {--dns=} {--domain=}';

    public function handle()
    {
        $folderName = $this->detectName();

        $this->input->setArgument('host', 'localhost');

        if (! $this->option('subdomain')) {
            $this->input->setOption('subdomain', str_replace('.', '-', $folderName));
        }

        parent::handle();
    }

    protected function detectName(): string
    {
        return get_current_user() . '-' . basename(getcwd());
    }
}
