## 1.0.0-beta39
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Thu Jul 18 10:36:23 CEST 2019

* CORE: [FIX] A Throwable exception (TypeError) might lead to multiple loggers and therefore the wrong process id gets attached to logs 
* CORE: [FIX] MicrosoftGraphEndpoint: Remove group member/owner ends in "Write requests are only supported on contained entities"
* CORE: [FIX] MicrosoftGraphEndpoint: Resolve all group members/owners (limit of 100 resources)
* CORE: [FIX] MicrosoftGraphEndpoint: Do not throw exception if /groups/{group}/team fails
* CORE: [CHANGE] Add attribute type #34


## 1.0.0-beta38
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Tue Jul 16 14:28:23 CEST 2019

* CORE: [FIX] GRAPH API (ODataRest) returns a 404 if an object was not found from id=x filter


## 1.0.0-beta37
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Tue Jul 16 09:24:22 CEST 2019

* CORE: [FIX] Member batch result verify team via status and not code
* CORE: [FIX] Validate Process/Job filter before register/update a new one
* CORE: [FIX] Validate Endpoint filter_all/filter_new before register/update a new one


## 1.0.0-beta36
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Mon Jul 15 15:44:11 CEST 2019

* CORE: [FIX] Fix DataObject relations to array conversion


## 1.0.0-beta35
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Mon Jul 15 13:48:11 CEST 2019

* CORE: [FIX] Fix order if resources were retrieved via api
* CORE: [CHANGE] Cache resolved relations during workflow executions


## 1.0.0-beta34
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Mon Jul 15 10:45:14 CEST 2019

* CORE: [FIX] Do not count objects during fetch if no limit was given


## 1.0.0-beta33
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Mon Jul 15 10:44:11 CEST 2019

* CORE: [FIX] Performance fix during fetching relations


## 1.0.0-beta32
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Jul 11 15:42:11 CEST 2019

* CORE: [FIX] last_sync/last_successful_sync have an old timestamp #53 


## 1.0.0-beta31
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Tue Jul 09 09:04:11 CEST 2019

* CORE: [FEATURE] Added MicrosoftGraph endpoint with support for groups and teams


## 1.0.0-beta30
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Fri Jun 28 09:04:11 CEST 2019

* CORE: [FIX] Import DataObject (update) does not update last_sync to current timestamp


## 1.0.0-beta29
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Thu Jun 27 11:22:11 CEST 2019

* CORE: [CHANGE] Includes exception for failed DataObject syncs
* CORE: [CHANGE] DataObject endpoint garbage is set to true if the DataObject does not exists on the endpoint


## 1.0.0-beta28
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Tue Jun 18 13:45:12 CEST 2019

* CORE: [FIX] Fixes stream


## 1.0.0-beta27
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Tue Jun 18 11:03:12 CEST 2019

* CORE: [FIX] Fixes watch changeStream


## 1.0.0-beta26
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Mon Jun 17 16:57:12 CEST 2019

* CORE: [FIX] DataObjectRealtion context gets now changed if the context changes during sync


## 1.0.0-beta25
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Mon Jun 17 09:23:12 CEST 2019

* CORE: [FIX] Added new logs mongodb index (parent, namespace) which drastically increase log request performance
* DOCS: [CHANGE] Added permalink extension (anchors)
* CORE: [FEATURE] Added predefined filters to workflow attributes
* CORE: [CHANGE] Update endpoint status of DataObject #49


## 1.0.0-beta24
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Mon Jun 17 09:23:12 CEST 2019

* CORE: [FIX] Do not sort if sort is equal {$natual: 1}, this is a default anyway but will slow down the query if mentioned


## 1.0.0-beta23
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Jun 12 14:01:12 CEST 2019

* API: [FIX] error":"TypeError","message":"Argument 3 passed to Tubee\\DataObjectRelation\\Factory::watch() must be of the type boolean, array given, called in /usr/share/tubee/src/lib/Rest/v1/ObjectRelations.php on line 80


## 1.0.0-beta22
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Jun 12 14:01:12 CEST 2019

* API: [FIX] fixes process data Tubee\Rest\Middlewares\ExceptionHandler,ERROR]: uncaught exception Unexpected property: data


## 1.0.0-beta21
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Fri Jun 07 09:01:12 CEST 2019

* CORE: [FIX] Add workflow with no data ends in Undefined index exception #44
* API: [CHANGE] Added core.v1 prefix to all requests and resources in openapi/swagger schemas
* CORE: [CHANGE] Ucs endpoint must check search response for equality #45
* API: [CHANGE] Added readonly flags to Job/Process/Endpoint,DataObjectRelation,DataObject status fieldss


## 1.0.0-beta20
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Mon Jun 03 15:50:12 CEST 2019

* CORE: [FIX] Using own log formatter for mongodb to encode most context as json since context may contain invalid mongodb field names ($ prefix or .)


## 1.0.0-beta19
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Mon Jun 03 13:50:11 CEST 2019

* CORE: [FIX] Always include $dn$ in Ucs change()
* CORE: [FIX] factories watch() include default filter like getAll()


## 1.0.0-beta18
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed May 29 11:50:12 CEST 2019

* CORE: [FIX] Skip objects if build() returns null


## 1.0.0-beta17
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Tue May 28 16:49:12 CEST 2019

* CORE: [FIX] Trying to get property 'relations' of non-object


## 1.0.0-beta16
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Thu May 23 10:26:12 CEST 2019

* CORE: [FIX] encode found export object, fixes MongoDB\Driver\Exception\InvalidArgumentException: invalid document for insert: keys cannot begin with "$"
* CORE: [FIX] added missing simulate field to Job resource in swagger specs 


## 1.0.0-beta15
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed May 22 12:26:12 CEST 2019

* CORE: [FIX] do not throw Exception\ImportConditionNotMet if multiple data objects were found, sync relations first and log a warning instead 
* CORE: [FIX] Wrong debug log: total counter is less than the current DataObject #40
* CORE: [FIX] pdo endpoint (mssql) generates wrong filter #39
* CORE: [FIX] Do not throw an exception during query for an non existing mssql field #38


## 1.0.0-beta14
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed May 15 12:26:12 CEST 2019

* CORE: [FIX] undefined class constant self::COLLECTION_NAME in Tubee\DataObjectRelation\Factory


## 1.0.0-beta13
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Thu May 09 15:51:14 CEST 2019

* CORE: [FIX] Error: Cannot access protected property Tubee\DataObjectRelation\Factory::$logger
* CORE: [FIX] Error: Cannot access protected property Tubee\DataObjectRelation\Factory::$resource_factory
* CORE: [FIX] MongoDB\Driver\Exception\InvalidArgumentException: invalid document for insert: keys cannot contain ".":


## 1.0.0-beta12
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed May 08 17:06:12 CEST 2019

* CORE: [FIX] TypeError: Argument 2 passed to Tubee\Async\Sync::export() must be of the type array, null given


## 1.0.0-beta11
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed May 08 15:06:12 CEST 2019

* CORE: [CHANGE] Set garbage endpoint flag to `true` if object gets removed from endpoint during export absent workflow
* CORE: [CHANGE] ldap entrydn from mapping gets normalized as well (all lowercase)
* CORE: [FIX] MongoDB\Driver\Exception\BulkWriteException: WiredTigerIndex::insert: key too large to index, failing 1134
* CORE: [CHANE] throw Tubee\Workflow\Exception\ImportConditionNotMet if source data objects are not unique
* CORE: [FIX] sort operation does not work for sorting data object, endpoint, collection resources
* CORE: [FIX] Method `StreamIterator\StreamIterator::__toString()` must not throw an exception, caught ErrorException: Undefined index: created in
* API: [CHANGE] If authentication failed a 401 gets returned instead of a 500
* API: [CHANGE] filter is now a json encoded object in Process and Job resources
* CORE: [FIX] skip garbage collection if a filtered process was issued


## 1.0.0-beta10
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Tue May 07 12:06:12 CEST 2019

* CORE: [FIX] Undefined property: Tubee\DataObjectRelation\Factory::$logger in /usr/share/tubee/src/lib/DataObjectRelation/Factory.php:160
* CORE: [FIX] nullable endpoint result after a seccond sync


## 1.0.0-beta9
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Sun Apr 18 11:34:01 CEST 2019

* CORE: [FIX] Ldap auth adapter dremscape dependency is no correctly recreated for every ldap auth adapter
* CORE: [FIX] Allow calls to /api and /api/v1 for everyone if authenticated


## 1.0.0-beta8
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Sub Apr 14 15:04:01 CEST 2019

* CORE: [FIX] automatically drop unresolvable relations and do not throw exception if such events occur during relation resolving
* CORE: [CHANGE] Add index for field name during collection creation
* CORE: [CHANGE] ensure index for import fields after updating endpoint
* CORE: [FIX] workflow priority ordner, 0 first
* CORE: [FIX] fixed getAll during ldap query if filter_all is null
* CORE: [FIX] fixed ldap endpoint non utf-8 data encoding
* CORE: [FIX] normalize ldap dn (ignore case of dn attribute parts)


## 1.0.0-beta7
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Apr 11 17:11:01 CEST 2019

* CORE: [FIX] attributes of type array and unwind will now be properly converted to a list
* CORE: [FIX] fixed cancel process after setting job active to false


## 1.0.0-beta6
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Apr 11 15:51:01 CEST 2019

* CORE: [CHANGE] attribute map type array converts to real numeric array
* CORE: [FIX] Undefined index: data in PATCH update job
* CORE: [CHANGE] creating dataobject relations on thy fly may now match multiple objects to create relations to


## 1.0.0-beta5
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Apr 11 10:51:01 CEST 2019

* CORE: [CHANGE] log endpoint object during import/export workflows
* CORE: [FIX] ODataRest endpoint does not required specific declaration of the id
* CORE: [FIX] LdapEndpoint entrydn is now always lowercase
* CORE: [FIX] ignore ldap entrydn in diff
* CORE: [FEATURE] new option `active` for jobs. jobs may be enabled/disabled.


## 1.0.0-beta4
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Fri Apr 05 16:51:01 CEST 2019

* PACKAGING: [FIX] fixed TUBEE_CONFIG_DIR in docker image to /etc/tubee
* API: [FIX] Argument 5 passed to Tubee\DataObject\Factory::watch() must be of the type integer or null, boolean given
* API: [FIX] Argument 2 passed to Tubee\DataObjectRelation\Factory::watch() must implement interface MongoDB\BSON\ObjectIdInterface or be null, array given
* API: [FIX] Argument 6 passed to Tubee\Resource\Factory::watchFrom() must be of the type integer or null, object given,
* API: [FIX] Added default empty array to Workflow map.context
* CORE: [FIX] Invalid json (especially filter_one and filter_all) results now in Exception\InvalidJson
* CORE: [FEATURE] Added new attribute map option `writeonly` to only apply attributes initially if true


## 1.0.0-beta3
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Apr 03 16:01:01 CEST 2019

* API: [FIX] Added route /api to /api/v1
* CORE: [FIX] Fixed route/acl middleware order
* CORE: [CHANGE] upgraded micro-auth to latest alpha which fixes Adapter\Basic\Ldap identifier issue
* CORE: [FIX] user password can now be changed correctly
* CORE: [CHANGE] Invalid secret key now responds with Tubee\Secret\Exception\SecretNotResolvable instead Tubee\Exception
* API: [CHANGE] Endpoints with default identifiers specify those now in the openapi specs


## 1.0.0-beta2
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Mon Mar 25 15:14:01 CET 2019\

* TESTING: [CHANGE] Added new xml endpoint unit tests
* PACKAGING: [CHANGE] Dev docker container now sets TUBEE_SECRET_KEY
* PACKAGING: [FIX] fixes no make dep npm
* PACKAGING: [CHANGE] Dockerfile and Dockerfile-dev are no part of the server repo itself
* PACKAGING: [CHANGE] docker images now inherits from gyselroth/tubee:php7.2-fpm-v8js which already includes v8js
* DOCS: [CHANGE] Various fixes
* CORE: [FIX] Do not block GET /logs requests if log response from MongoDB is empty (do not use natural sorting)
* CORE: [FIX] Xml, Csv validators merge defaults before validation
* CORE: [FIX] fixed ldap filter concat for filter_all and custom query
* CORE: [CHANGE] StorageInterface::syncWriteStream() implementations do now fclose() the resource
* CORE: [FEATURE] Enhanced query transformation for xml endpoint, query to xpath including $and, $or, $gt, $gte, $lt, $lte, $ne
* CORE: [FIX] Fixes issue due xml object change with diff type AttributeMapInterface::ACTION_ADD
* CORE: [FIX] resource validation now takes place with mounted secrets
* CORE: [CHANGE] Replaced resource validators with Garden\Schema\Schema OpenAPI v3 validation
* CORE: [CHANGE] Added OpenAPI v3 specs besides swagger v2
* CORE: [FEATURE] Added new resource type GarbageWorkflow which replaces the need to write a scripted condition and check for garbage run
* CORE: [FIX] Fixed workflow cleanup execution
* CORE: [FEATURE] Remove DataObjectRelation with GarbageWorkflows
* CORE: [CHANGE] CoreInstallation delta now initializes required MongoDB replset if this has not been done yet
* CORE: [CHANGE] Possibility to automatically remove DataObjectRelations during GarbageWorkflows (set map[].ensure to absent)
* CORE: [CHANGE] Refactoring Workflow into Workflow\ImportWorkflow and Workflow\ExportWorkflow
* CORE: [CHANGE] Added TUBEE_CACHE_ADAPTER and TUBEE_LOG_LEVEL env variables to default container config
* CORE: [CHANGE] All resource factories now depend on Resource\Factory which itself uses a Psr cache for resource validation
* CORE: [FIX] flush: true results in "TypeError: Argument 1 passed to Tubee\DataObject\Factory::deleteAll() must implement interface Tubee\Collection\CollectionInterface, boolean given"
* CORE: [FEATURE] Added -f to cli jobs (flush queue)
* CORE: [FEATURE] Added (bool)`skip` to attribute mapping to skip attributes to map
* CORE: [CHANGE] endpoint filter_all and filter_one use the tubee (mongodb) dql now instead filters in endpoint specific formats
* CORE: [FEATURE] filter_one and filter_all can now be used for Csv and Json endpoints (Note that performance is not optimal since those formats do not have a propper query language and neither now indexing)
* CORE: [CHANGE] Added Endpoint\LoggerTrait to apply generic endpoint operation logging
* CORE: [FIX] readOnly attributes get stripped out from request
* CORE: [FIX] binary values in Endpoint\Ldap get base64 encoded
* CORE: [FEATURE] Possibility to set context data within `map` definition in worklow
* API: [FIX] uncaught exception: Argument 4 passed to Tubee\Rest\v1\Processes::delete() must implement interface MongoDB\BSON\ObjectIdInterface
* API: [FIX] uncaught exception: Argument 1 passed to Tubee\Secret\Factory::getOne() must implement interface Tubee\ResourceNamespace\ResourceNamespaceInterface, string given at POST /api/v1/secrets
* API: [FIX] uncaught exception: Undefined variable: job]  [object] (ErrorException(code: 0): Undefined variable: job at POST /api/v1/jobs
* API: [FIX] Added ImageEndpoint to openapi v3 specs
* API: [FIX] fixed max execution time of 5min for watch stream requests
* API: [FIX] Added reaOnly flags to openapi spec for readonly attributes (like created, changed, version)
* API: [FIX] Exception middleware catches now throwables instead just exceptions only

## 1.0.0-beta1
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Fri Jan 25 17:14:01 CET 2019\

Initial beta relase v1.0.0-beta1
