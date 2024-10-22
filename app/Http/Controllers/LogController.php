<?php

namespace Expose\Client\Http\Controllers;

use Expose\Client\Logger\RequestLogger;
use Expose\Common\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class LogController extends Controller
{
    /** @var RequestLogger */
    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $httpConnection->send(respond_json($this->requestLogger->getData()));
    }
}
