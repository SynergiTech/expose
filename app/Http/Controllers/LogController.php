<?php

namespace Expose\Client\Http\Controllers;

use Expose\Client\Logger\DatabaseRequestLogger;
use Expose\Client\Logger\RequestLogger;
use Expose\Common\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class LogController extends Controller
{
    /** @var RequestLogger */
    protected $requestLogger;

    protected DatabaseRequestLogger $databaseRequestLogger;

    public function __construct(RequestLogger $requestLogger, DatabaseRequestLogger $databaseRequestLogger)
    {
        $this->requestLogger = $requestLogger;
        $this->databaseRequestLogger = $databaseRequestLogger;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $httpConnection->send(respond_json($this->databaseRequestLogger->getData()));
//        $httpConnection->send(respond_json($this->requestLogger->getData()));
    }
}
