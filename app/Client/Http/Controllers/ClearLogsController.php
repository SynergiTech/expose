<?php

namespace App\Client\Http\Controllers;

use App\Logger\RequestLogger;
use Expose\Common\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class ClearLogsController extends Controller
{
    /** @var RequestLogger */
    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $this->requestLogger->clear();

        $httpConnection->send(respond_json([], 200));
    }
}
