<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest;

use FastRoute;
use Micro\Http\Router;
use Micro\Http\Router\Route;

class Routes
{
    /**
     * Create routing table.
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
            ->appendRoute(new Route('/api/v1/jobs/{job:#([0-9a-zA-Z_-])#}/errors/{error:#([0-9a-zA-Z_-])#}(/|\z)', v1\Jobs::class))
            ->appendRoute(new Route('/api/v1/jobs/{job:#([0-9a-zA-Z_-])#}(/|\z)', v1\Jobs::class))
            ->appendRoute(new Route('/api/v1/jobs$', v1\Jobs::class))
            ->appendRoute(new Route('/api/v1/watch/jobs/{job:#([0-9a-zA-Z_-])#}/errors', [v1\Jobs::class, 'watchErrors']))
            ->appendRoute(new Route('/api/v1/access-rules/{rule:#([0-9a-zA-Z_-])#}(/|\z)', v1\AccessRules::class))
            ->appendRoute(new Route('/api/v1/access-rules$', v1\AccessRules::class))
            ->appendRoute(new Route('/api/v1/access-roles/{role:#([0-9a-zA-Z_-])#}(/|\z)', v1\AccessRoles::class))
            ->appendRoute(new Route('/api/v1/access-roles$', v1\AccessRoles::class))
            ->appendRoute(new Route('/api/v1', v1\Api::class))
            ->appendRoute(new Route('/api$', v1\Api::class))
            ->appendRoute(new Route('^$', v1\Api::class));
    }

    public function collect()
    {
        return FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '/api/v1/mandators', [v1\Mandators::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/mandators', [v1\Mandators::class, 'post']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}', [v1\Mandators::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/mandators/{mandator}', [v1\Mandators::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/mandators/{mandator}', [v1\Mandators::class, 'put']);
            $r->addRoute('PATCH', '/api/v1/mandators/{mandator}', [v1\Mandators::class, 'patch']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}/datatypes', [v1\DataTypes::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/mandators/{mandator}/datatypes', [v1\DataTypes::class, 'post']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}/datatypes/{datatype}', [v1\DataTypes::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/mandators/{mandator}/datatypes/{datatype}', [v1\DataTypes::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/mandators/{mandator}/datatypes/{datatype}', [v1\DataTypes::class, 'put']);
            $r->addRoute('PATCH', '/api/v1/mandators/{mandator}/datatypes/{datatype}', [v1\DataTypes::class, 'patch']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}/datatypes/{datatype}/endpoints', [v1\Endpoints::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/mandators/{mandator}/datatypes/{datatype}/endpoints', [v1\Endpoints::class, 'post']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}', [v1\Endpoints::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}', [v1\Endpoints::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}', [v1\Endpoints::class, 'put']);
            $r->addRoute('PATCH', '/api/v1/mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}', [v1\Endpoints::class, 'patch']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}/workflows', [v1\Workflows::class, 'getAll']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}/workflows/{workflow}', [v1\Workflows::class, 'getOne']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}/datatypes/{datatype}/objects', [v1\Objects::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/mandators/{mandator}/datatypes/{datatype}/objects', [v1\Objects::class, 'post']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}/datatypes/{datatype}/objects/{object}', [v1\Objects::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/mandators/{mandator}/datatypes/{datatype}/objects/{object}', [v1\Objects::class, 'delete']);
            $r->addRoute('PATCH', '/api/v1/mandators/{mandator}/datatypes/{datatype}/objects/{object}', [v1\Objects::class, 'patch']);
            $r->addRoute('PUT', '/api/v1/mandators/{mandator}/datatypes/{datatype}/objects/{object}', [v1\Objects::class, 'put']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}/datatypes/{datatype}/objects/{object}/endpoints', [v1\ObjectEndpoints::class, 'getAll']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}/datatypes/{datatype}/objects/{object}/endpoints/{endpoint}', [v1\ObjectEndpoints::class, 'getOne']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}/datatypes/{datatype}/objects/{object}/history', [v1\Objects::class, 'getHistory']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}/datatypes/{datatype}/objects/{object}/relatives', [v1\ObjectRelatives::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/mandators/{mandator}/datatypes/{datatype}/objects/{object}/relatives', [v1\ObjectRelatives::class, 'post']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}/datatypes/{datatype}/objects/{object}/relatives/{relative}', [v1\ObjectRelatives::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/mandators/{mandator}/datatypes/{datatype}/objects/{object}/relatives/{relative}', [v1\ObjectRelatives::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/mandators/{mandator}/datatypes/{datatype}/objects/{object}/relatives/{relative}', [v1\ObjectRelatives::class, 'put']);
            $r->addRoute('GET', '/api/v1/access-rules', [v1\AccessRules::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/access-rules', [v1\AccessRules::class, 'post']);
            $r->addRoute('GET', '/api/v1/access-rules/{rule}', [v1\AccessRules::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/access-rules/{rule}', [v1\AccessRules::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/access-rules/{rule}', [v1\AccessRules::class, 'put']);
            $r->addRoute('PATCH', '/api/v1/access-rules/{rule}', [v1\AccessRules::class, 'patch']);
            $r->addRoute('GET', '/api/v1/access-roles', [v1\AccessRoles::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/access-roles', [v1\AccessRoles::class, 'post']);
            $r->addRoute('GET', '/api/v1/access-roles/{rule}', [v1\AccessRoles::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/access-roles/{rule}', [v1\AccessRoles::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/access-roles/{rule}', [v1\AccessRoles::class, 'put']);
            $r->addRoute('PATCH', '/api/v1/access-roles/{rule}', [v1\AccessRoles::class, 'patch']);
            $r->addRoute('GET', '/api/v1/jobs', [v1\Jobs::class, 'getAll']);
            $r->addRoute('GET', '/api/v1/jobs/{job}', [v1\Jobs::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/jobs/{job}', [v1\Jobs::class, 'delete']);
            $r->addRoute('GET', '/api/v1/jobs/{job}/errors', [v1\JobErrors::class, 'getAll']);
            $r->addRoute('GET', '/api/v1/watch/jobs/{job}/errors', [v1\JobErrors::class, 'watchAll']);
            $r->addRoute('GET', '/api/v1/jobs/{job}/errors/{error}', [v1\JobErrors::class, 'getOne']);
        });
    }
}
