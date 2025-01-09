<?php

namespace Expose\Client\Logger;

use Expose\Client\Contracts\LoggerContract;
use React\Http\Browser;

class FrontendLogger implements LoggerContract
{

    public function __construct(protected Browser $browser)
    {
    }

    public function synchronizeRequest(LoggedRequest $loggedRequest): void
    {
        $this
            ->browser
            ->post(
                'http://127.0.0.1:4040/api/logs',
                ['Content-Type' => 'application/json'],
                json_encode($loggedRequest, JSON_INVALID_UTF8_IGNORE)
            );
    }

    public function synchronizeResponse(LoggedRequest $loggedRequest, string $rawResponse): void
    {
        $this->synchronizeRequest($loggedRequest);
    }
}
