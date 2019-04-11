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
