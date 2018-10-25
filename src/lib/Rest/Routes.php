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

class Routes
{
    public function __construct(Router $roueter)
    {
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
            $r->addRoute('POST', '/api/v1/mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}/workflows', [v1\Workflows::class, 'post']);
            $r->addRoute('GET', '/api/v1/mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}/workflows/{workflow}', [v1\Workflows::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}/workflows/{workflow}', [v1\Workflows::class, 'delete']);
            $r->addRoute('PATCH', '/api/v1/mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}/workflows/{workflow}', [v1\Workflows::class, 'patch']);
            $r->addRoute('PUT', '/api/v1/mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}/workflows/{workflow}', [v1\Workflows::class, 'post']);
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
            $r->addRoute('GET', '/api/v1/watch/access-roles', [v1\AccessRoles::class, 'watchAll']);
            $r->addRoute('GET', '/api/v1/access-roles/{role}', [v1\AccessRoles::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/access-roles/{role}', [v1\AccessRoles::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/access-roles/{role}', [v1\AccessRoles::class, 'put']);
            $r->addRoute('PATCH', '/api/v1/access-roles/{role}', [v1\AccessRoles::class, 'patch']);
            $r->addRoute('GET', '/api/v1/jobs', [v1\Jobs::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/jobs', [v1\Jobs::class, 'post']);
            $r->addRoute('GET', '/api/v1/jobs/{job}', [v1\Jobs::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/jobs/{job}', [v1\Jobs::class, 'delete']);
            $r->addRoute('GET', '/api/v1/jobs/{job}/processes', [v1\Processes::class, 'getAll']);
            $r->addRoute('GET', '/api/v1/jobs/{job}/processes/{process}', [v1\Processes::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/jobs/{job}/processes/{process}', [v1\Processes::class, 'delete']);
            $r->addRoute('GET', '/api/v1/jobs/{job}/logs', [v1\JobLogs::class, 'getAll']);
            $r->addRoute('GET', '/api/v1/watch/jobs/{job}/logs', [v1\JobLogs::class, 'watchAll']);
            $r->addRoute('GET', '/api/v1/jobs/{job}/logs/{log}', [v1\JobLogs::class, 'getOne']);
            //$r->addRoute('GET', '/api/v1/jobs/{job}/processes/{process}/logs', [v1\ProcessLogs::class, 'getAll']);
            //$r->addRoute('GET', '/api/v1/watch/jobs/{job}/processes/{process}/logs', [v1\ProcessLogs::class, 'watchAll']);
            //$r->addRoute('GET', '/api/v1/jobs/{job}/processes/{process}/(logs/{log}', [v1\ProcessLogs::class, 'getOne']);
        });
    }
}
