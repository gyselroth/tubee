<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest;

use Micro\Http\Router;
use Micro\Http\Router\Route;

class Routes
{
    /**
     * Create routing table
     */
    public function __construct(Router $router)
    {
        $router
            ->appendRoute(new Route('/api/v1/mandators/{mandator:#([0-9a-zA-Z_-])#}/datatypes/{datatype:#([0-9a-zA-Z_-])#}/objects/{object:#([0-9a-zA-Z_-])#}(/|\z)', v1\Objects::class))
            ->appendRoute(new Route('/api/v1/mandators/{mandator:#([0-9a-zA-Z_-])#}/datatypes/{datatype:#([0-9a-zA-Z_-])#}/objects(/|\z)$', v1\Objects::class))
            ->appendRoute(new Route('/api/v1/mandators/{mandator:#([0-9a-zA-Z_-])#}/datatypes/{datatype:#([0-9a-zA-Z_-])#}/endpoints/{endpoint:#([0-9a-zA-Z_-])#}(/|\z)$', v1\Endpoints::class))
            ->appendRoute(new Route('/api/v1/mandators/{mandator:#([0-9a-zA-Z_-])#}/datatypes/{datatype:#([0-9a-zA-Z_-])#}/endpoints(/|\z)$', v1\Endpoints::class))
            ->appendRoute(new Route('/api/v1/mandators/{mandator:#([0-9a-zA-Z_-])#}/datatypes/{datatype:#([0-9a-zA-Z_-])#}(/|\z)$', v1\DataTypes::class))
            ->appendRoute(new Route('/api/v1/mandators/{mandator:#([0-9a-zA-Z_-])#}/datatypes(/|\z)$', v1\DataTypes::class))
            ->appendRoute(new Route('/api/v1/mandators/{mandator:#([0-9a-zA-Z_-])#}(/|\z)$', v1\Mandators::class))
            ->appendRoute(new Route('/api/v1/mandators$', v1\Mandators::class))
            ->appendRoute(new Route('/api/v1/jobs/{job:#([0-9a-zA-Z_-])#}(/|\z)', v1\Jobs::class))
            ->appendRoute(new Route('/api/v1/jobs$', v1\Jobs::class))
            ->appendRoute(new Route('/api/v1/access-rules/{rule:#([0-9a-zA-Z_-])#}(/|\z)', v1\AccessRules::class))
            ->appendRoute(new Route('/api/v1/access-rules$', v1\AccessRules::class))
            ->appendRoute(new Route('/api/v1/access-roles/{role:#([0-9a-zA-Z_-])#}(/|\z)', v1\AccessRoles::class))
            ->appendRoute(new Route('/api/v1/access-roles$', v1\AccessRules::class))
            ->appendRoute(new Route('/api/v1', v1\Api::class))
            ->appendRoute(new Route('/api$', v1\Api::class))
            ->appendRoute(new Route('^$', v1\Api::class));
    }
}
