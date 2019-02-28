## 1.0.0-beta2
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Fri Jan 25 17:14:01 CET 2019\

* TESTING: [CHANGE] Added new xml endpoint unit tests
* PACKAGING: [CHANGE] Dev docker container now sets TUBEE_SECRET_KEY, Cache adapter to void and log level to debug (100)
* PACKAGING: [FIX] fixes no make dep npm
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
* API: [FIX] uncaught exception: Argument 1 passed to Tubee\Secret\Factory::getOne() must implement interface Tubee\ResourceNamespace\ResourceNamespaceInterface, string given at POST /api/v1/secrets
* API: [FIX] uncaught exception: Undefined variable: job]  [object] (ErrorException(code: 0): Undefined variable: job at POST /api/v1/jobs
* API: [FIX] Added ImageEndpoint to openapi v3 specs


## 1.0.0-beta1
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Fri Jan 25 17:14:01 CET 2019\

Initial beta relase v1.0.0-beta1
