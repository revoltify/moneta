<?php

declare(strict_types=1);

use App\Mcp\Servers\MonetaServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::web('/mcp', MonetaServer::class)
    ->middleware(['throttle:60,1', 'auth:api'])
    ->name('mcp.moneta');
