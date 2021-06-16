<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest;

use FastRoute;

class Routes
{
    /**
     * Routes collector.
     */
    public static function collect()
    {
        return FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '/healthz', [v1\Api::class, 'get']);
            $r->addRoute('GET', '/api', [v1\Api::class, 'get']);
            $r->addRoute('GET', '/api/v1', [v1\Api::class, 'get']);
            $r->addRoute('GET', '/openapi/v2', [Specifications::class, 'getApiv2']);
            $r->addRoute('GET', '/openapi/v3', [Specifications::class, 'getApiv3']);
            $r->addRoute('GET', '/api/v1/namespaces', [v1\ResourceNamespaces::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/namespaces', [v1\ResourceNamespaces::class, 'post']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}', [v1\ResourceNamespaces::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/namespaces/{namespace}', [v1\ResourceNamespaces::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/namespaces/{namespace}', [v1\ResourceNamespaces::class, 'put']);
            $r->addRoute('PATCH', '/api/v1/namespaces/{namespace}', [v1\ResourceNamespaces::class, 'patch']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections', [v1\Collections::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/namespaces/{namespace}/collections', [v1\Collections::class, 'post']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}', [v1\Collections::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/namespaces/{namespace}/collections/{collection}', [v1\Collections::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/namespaces/{namespace}/collections/{collection}', [v1\Collections::class, 'put']);
            $r->addRoute('PATCH', '/api/v1/namespaces/{namespace}/collections/{collection}', [v1\Collections::class, 'patch']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/logs', [v1\Collections::class, 'getAllLogs']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/logs/{log}', [v1\Collections::class, 'getOneLog']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints', [v1\Endpoints::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints', [v1\Endpoints::class, 'post']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints/{endpoint}', [v1\Endpoints::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints/{endpoint}', [v1\Endpoints::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints/{endpoint}', [v1\Endpoints::class, 'put']);
            $r->addRoute('PATCH', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints/{endpoint}', [v1\Endpoints::class, 'patch']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints/{endpoint}/objects', [v1\Endpoints::class, 'getAllObjects']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints/{endpoint}/workflows', [v1\Workflows::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints/{endpoint}/workflows', [v1\Workflows::class, 'post']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints/{endpoint}/workflows/{workflow}', [v1\Workflows::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints/{endpoint}/workflows/{workflow}', [v1\Workflows::class, 'delete']);
            $r->addRoute('PATCH', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints/{endpoint}/workflows/{workflow}', [v1\Workflows::class, 'patch']);
            $r->addRoute('PUT', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints/{endpoint}/workflows/{workflow}', [v1\Workflows::class, 'post']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints/{endpoint}/logs', [v1\Endpoints::class, 'getAllLogs']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/endpoints/{endpoint}/logs/{log}', [v1\Endpoints::class, 'getOneLog']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/objects', [v1\Objects::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/namespaces/{namespace}/collections/{collection}/objects', [v1\Objects::class, 'post']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/objects/{object}', [v1\Objects::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/namespaces/{namespace}/collections/{collection}/objects/{object}', [v1\Objects::class, 'delete']);
            $r->addRoute('PATCH', '/api/v1/namespaces/{namespace}/collections/{collection}/objects/{object}', [v1\Objects::class, 'patch']);
            $r->addRoute('PUT', '/api/v1/namespaces/{namespace}/collections/{collection}/objects/{object}', [v1\Objects::class, 'put']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/objects/{object}/history', [v1\Objects::class, 'getHistory']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/objects/{object}/relations', [v1\ObjectRelations::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/namespaces/{namespace}/collections/{collection}/objects/{object}/relations', [v1\ObjectRelations::class, 'post']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/objects/{object}/relations/{relation}', [v1\ObjectRelations::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/namespaces/{namespace}/collections/{collection}/objects/{object}/relations/{relation}', [v1\ObjectRelations::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/namespaces/{namespace}/collections/{collection}/objects/{object}/relations/{relation}', [v1\ObjectRelations::class, 'put']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/objects/{object}/logs', [v1\Objects::class, 'getAllLogs']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/collections/{collection}/objects/{object}/logs/{log}', [v1\Objects::class, 'getOneLog']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/relations', [v1\ObjectRelations::class, 'getAll']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/relations/{relation}', [v1\ObjectRelations::class, 'getOne']);
            $r->addRoute('POST', '/api/v1/namespaces/{namespace}/relations', [v1\ObjectRelations::class, 'post']);
            $r->addRoute('PATCH', '/api/v1/namespaces/{namespace}/relations/{relation}', [v1\ObjectRelations::class, 'patch']);
            $r->addRoute('DELETE', '/api/v1/namespaces/{namespace}/relations/{relation}', [v1\ObjectRelations::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/namespaces/{namespace}/relations', [v1\ObjectRelations::class, 'put']);
            $r->addRoute('GET', '/api/v1/access-rules', [v1\AccessRules::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/access-rules', [v1\AccessRules::class, 'post']);
            $r->addRoute('GET', '/api/v1/access-rules/{rule}', [v1\AccessRules::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/access-rules/{rule}', [v1\AccessRules::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/access-rules/{rule}', [v1\AccessRules::class, 'put']);
            $r->addRoute('PATCH', '/api/v1/access-rules/{rule}', [v1\AccessRules::class, 'patch']);
            $r->addRoute('GET', '/api/v1/access-roles', [v1\AccessRoles::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/access-roles', [v1\AccessRoles::class, 'post']);
            $r->addRoute('GET', '/api/v1/access-roles/{role}', [v1\AccessRoles::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/access-roles/{role}', [v1\AccessRoles::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/access-roles/{role}', [v1\AccessRoles::class, 'put']);
            $r->addRoute('PATCH', '/api/v1/access-roles/{role}', [v1\AccessRoles::class, 'patch']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/secrets', [v1\Secrets::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/namespaces/{namespace}/secrets', [v1\Secrets::class, 'post']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/secrets/{secret}', [v1\Secrets::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/namespaces/{namespace}/secrets/{secret}', [v1\Secrets::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/namespaces/{namespace}/secrets/{secret}', [v1\Secrets::class, 'put']);
            $r->addRoute('PATCH', '/api/v1/namespaces/{namespace}/secrets/{secret}', [v1\Secrets::class, 'patch']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/jobs', [v1\Jobs::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/namespaces/{namespace}/jobs', [v1\Jobs::class, 'post']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/jobs/{job}', [v1\Jobs::class, 'getOne']);
            $r->addRoute('PATCH', '/api/v1/namespaces/{namespace}/jobs/{job}', [v1\Jobs::class, 'patch']);
            $r->addRoute('DELETE', '/api/v1/namespaces/{namespace}/jobs/{job}', [v1\Jobs::class, 'delete']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/processes', [v1\Processes::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/namespaces/{namespace}/processes', [v1\Processes::class, 'post']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/processes/{process}', [v1\Processes::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/namespaces/{namespace}/processes/{process}', [v1\Processes::class, 'delete']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/jobs/{job}/logs', [v1\Jobs::class, 'getAllLogs']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/jobs/{job}/logs/{log}', [v1\Jobs::class, 'getOneLog']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/processes/{process}/logs', [v1\Processes::class, 'getAllLogs']);
            $r->addRoute('GET', '/api/v1/namespaces/{namespace}/processes/{process}/(logs/{log}', [v1\Processes::class, 'getOneLog']);
            $r->addRoute('GET', '/api/v1/users', [v1\Users::class, 'getAll']);
            $r->addRoute('POST', '/api/v1/users', [v1\Users::class, 'post']);
            $r->addRoute('GET', '/api/v1/users/{user}', [v1\Users::class, 'getOne']);
            $r->addRoute('DELETE', '/api/v1/users/{user}', [v1\Users::class, 'delete']);
            $r->addRoute('PUT', '/api/v1/users/{user}', [v1\Users::class, 'put']);
            $r->addRoute('PATCH', '/api/v1/users/{user}', [v1\Users::class, 'patch']);
        });
    }
}
