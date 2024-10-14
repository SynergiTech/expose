<?php

namespace App\Http;

use App\Client\Http\Controllers\FileController;
use Expose\Common\Http\RouteGenerator;
use Symfony\Component\Routing\Route;

class ClientRouteGenerator extends RouteGenerator
{
    public function addPublicFilesystem()
    {
        $this->routes->add('get-files', new Route(
            '/files/{path}',
            ['_controller' => FileController::class],
            ['path' => '.*']
        ));
    }
}
