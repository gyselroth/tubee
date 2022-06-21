## 2.0.2
**Maintainer**: Sandro Aebischer <aebischer@gyselroth.com>\
**Date**: Tue Jun 21 09:15:00 CEST 2022

### Bugfix
* Updated to new php-scheduler version (v4.0.3)

## 2.0.1
**Maintainer**: Sandro Aebischer <aebischer@gyselroth.com>\
**Date**: Thu Jun 09 09:45:00 CEST 2022

### Bugfix
* Updated to new php-scheduler version (v4.0.2)

## 2.0.0
**Maintainer**: Sandro Aebischer <aebischer@gyselroth.com>\
**Date**: Mon May 30 16:20:00 CEST 2022

### Feature
* Updated to new php-scheduler version (v4.0.0)

## 1.3.2
**Maintainer**: Sandro Aebischer <aebischer@gyselroth.com>\
**Date**: Thu Feb 03 16:00:00 CEST 2022

### Bugfixes
* OdataRest: Get correct amount of endpoint objects

## 1.3.1
**Maintainer**: Sandro Aebischer <aebischer@gyselroth.com>\
**Date**: Thu Feb 03 12:00:00 CEST 2022

### Bugfixes
* PHP-settings: Changed memory_limit to 512M

## 1.3.0
**Maintainer**: Sandro Aebischer <aebischer@gyselroth.com>\
**Date**: Wed Feb 02 16:00:00 CEST 2022

### Changes
* OdataRest: Allow to set rest data container manually via endpoint configuration

## 1.2.1
**Maintainer**: Sandro Aebischer <aebischer@gyselroth.com>\
**Date**: Mon Sep 20 11:30:00 CEST 2021

### Bugfixes
* MicrosoftGraph: Merge existing and new members/owners properly when ensure merge is configured

## 1.2.0
**Maintainer**: Sandro Aebischer <aebischer@gyselroth.com>\
**Date**: Tue Sep 07 15:30:00 CEST 2021

### Changes
* GarbageWorflow: Allow condition configuration for relation objects

## 1.1.0
**Maintainer**: Sandro Aebischer <aebischer@gyselroth.com>\
**Date**: Tue July 06 10:45:00 CEST 2021

### Changes
* Task-Scheduler: integrated new interval_reference feature of gyselroth/php-task-scheduler (v3.3.0)

## 1.0.0-beta68
**Maintainer**: Sandro Aebischer <aebischer@gyselroth.com>\
**Date**: Tue Feb 16 12:00:00 CEST 2021

### Bugfixes
* MongodbEndpoint: return id of inserted object
* MongodbEndpoint: return id of changed object

## 1.0.0-beta67
**Maintainer**: Sandro Aebischer <aebischer@gyselroth.com>\
**Date**: Tue Oct 27 10:10:00 CEST 2020

### Features
* SqlSrvUsersEndpoint: added option to set default database and default language

## 1.0.0-beta66
**Maintainer**: Sandro Aebischer <aebischer@gyselroth.com>\
**Date**: Tue Sep 08 15:15:00 CEST 2020

### Bugfixes
* Fixed 'Cannot unpack array' error when creating a MongodbEndpoint source-ep

## 1.0.0-beta65
**Maintainer**: Sandro Aebischer <aebischer@gyselroth.com>\
**Date**: Mon Jul 06 12:00:00 CEST 2020

### Bugfixes
* Fixed json to move ucs objects

## 1.0.0-beta64
**Maintainer**: Sandro Aebischer <aebischer@gyselroth.com>\
**Date**: Wed Apr 08 03:39:21 CEST 2020

### Features
* Added endpoint MSSQLUsers to manager user base on a Microsoft SQL server


## 1.0.0-beta63
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Thu Mar 19 13:31:21 CET 2020

### Bugfixes
* Long running processes with MicrosoftGraphEndpoint ends in multiple 401 errors #69


## 1.0.0-beta62
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Fri Jan 31 08:14:21 CET 2020

### Bugfixes
* Fixed workflow updates to manually added DataObjects MongoDB\Driver\Exception\BulkWriteException: Cannot create field 'ep-name' in element {endpoints: null}


## 1.0.0-beta61
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Tue Jan 28 14:43:24 CET 2020

### Bugfixes
* Fixes delete DataObjectRelation uncaught exception Argument 1 passed to Tubee\DataObjectRelation\Factory::deleteOne() must be an instance of Tubee\DataObjectRelation\DataObjectRelationInterface


## 1.0.0-beta60
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Tue Jan 28 13:23:24 CET 2020

### Changes
* Fixes identifier matching in relation attributes (workflow)


## 1.0.0-beta59
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Mon Jan 27 15:25:24 CET 2020

### Changes
* Introduction of Workflow attribute map map.identifiers to uniquely match a relation and update only that one


## 1.0.0-beta58
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Tue Jan 21 14:10:22 CET 2020

### Bugfixes
* MongoDB\Driver\Exception\BulkWriteException: Cannot create field 'eco-relation-csv' in element {endpoints: []}


## 1.0.0-beta57
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Tue Jan 21 11:38:22 CET 2020

### Bugfixes
* MongoDB\Driver\Exception\InvalidArgumentException: invalid document for insert: empty key post add data object into collection


## 1.0.0-beta56
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Fri Jan 17 15:17:22 CET 2020

### Bugfixes
* Fixed possible state conflict between processes which are running at the same time

### Changes
* Added more logging for update/delete operations

### Features
* Added workflow name and process id to endpoint states within a DataObject
* Added endpoint states to history record


## 1.0.0-beta55
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Mon Dec 16 10:41:22 CET 2019

### Bugfixes
* The cli interface does not end with status code > 0 if it ends with an exception
* TypeError: Argument 2 passed to Tubee\DataObject\Factory::deleteOne() must be of the type string, object given, called in /usr/share/tubee/src/lib/Collection.php on line 253

### Changes
* Added /openapi/v2 and /openapi/v3 to retrieve OpenAPI sepcs (Instead /specs)
* Added /healthz endpoints
* Skip authentication for /api and /api/v1 (This includes the new endpoints /healthz and /openapi)

### Packaging
* Discontinue distribution of deb packages
* Removal of example k8s resources
* Helm repository for tubee: https://github.com/gyselroth/tubee-helm
* Docs are now in a separate repository https://github.com/gyselroth/tubee-docs


## 1.0.0-beta54
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Thu Dec 05 10:04:21 CET 2019

### Bugfixes
* Links against taskscheduler v3.2.2 (progress rate to 100% after finish)


## 1.0.0-beta53
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Dec 04 17:14:21 CET 2019

### Bugfixes
* Links against taskscheduler v3.2.1 (progress rate limit fix)
* Fixes exception logging


## 1.0.0-beta52
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Dec 04 15:58:21 CET 2019

### Bugfixes
* Do not send notifications from child processes


## 1.0.0-beta51
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Tue Dec 03 16:31:21 CET 2019

### Bugfixes
* Job notification includes errors from child processes
* Do not cancel processes which have status >= 3 after a job gets disabled

### Features
* Processes with estimated time to finish
* Sync jobs with progress information
* Endpoints can now count their EndpointObjects


## 1.0.0-beta50
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Fri Oct 11 13:55:22 CEST 2019

* CORE: [FIX] TypeError: Argument 2 passed to Tubee\Endpoint\AbstractRest::getResourceId() must implement interface Tubee\EndpointObject\EndpointObjectInterface or be null, array given


## 1.0.0-beta49
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Oct 09 13:04:22 CEST 2019

* CORE: [FIX] Catch throwable errors during checking endpoint status
* CORE: [FIX] Added upgrade migration for SmbStorage (workgroup)


## 1.0.0-beta48
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Oct 09 10:14:21 CEST 2019

* CORE: [FIX] The default XMLEndpoint filter is now //node_name while node_name is the configured node_name of the endpoint options (By default it is `row`).
* CORE: [FIX] SmbStorage openWriteStream() does not truncate files anymore


## 1.0.0-beta47
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Sep 30 09:52:21 CEST 2019

* CORE: [FIX] Error: Call to undefined method mysqli_result::fetch() in /usr/share/tubee/src/lib/Endpoint/Mysql.php:105


## 1.0.0-beta46
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Sep 25 14:42:20 CEST 2019

* CORE: [FIX] Fixes default ordering (created by descending)


## 1.0.0-beta45
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Sep 25 12:21:20 CEST 2019

* CORE: [FIX] Sort operation used more than the maximum 33554432 bytes of RAM. Add an index, or specify a smaller limit. There is now no sorting during stream requests.
* CORE: [FIX] Errors during streams are now handled as StreamError and be returned to the requested as such.


## 1.0.0-beta44
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Fr Sep 20 16:41:20 CEST 2019

* CORE: [FIX] Error: Call to undefined method mysqli_stmt::fetch_assoc() in /usr/share/tubee/src/lib/Endpoint/Mysql.php:77


## 1.0.0-beta43
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Fr Sep 20 15:47:20 CEST 2019

* CORE: [FIX] ArgumentCountError: Wrong parameter count for mysqli_stmt::bind_param() in /usr/share/tubee/src/lib/Endpoint/Mysql/Wrapper.php:159
* CORE: [FEATURE] Support query dsl for PdoEndpoint and MysqlEndpoint


## 1.0.0-beta42
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Wed Sep 11 16:20:21 CEST 2019

* CORE: [FIX] ErrorException: Undefined property: PDOStatement::$num_rows in /usr/share/tubee/src/lib/Endpoint/Pdo.php:84
* CORE: [CHANGE] Use prepared stmts for fetching mysql/pdo endpoint data, filter column/table names
* CORE: [FIX] Fixes endpoints MongodbEndpoint/PdoEndpoint/MysqlEndpoint as destination ep


## 1.0.0-beta41
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Mon Sep 09 10:09:21 CEST 2019

* CORE: [FIX] Fixes unusable MysqlEndpoint (Could not be initialized)


## 1.0.0-beta40
**Maintainer**: Raffael Sahli <sahli@gyselroth.com>\
**Date**: Fri Aug 16 10:57:23 CEST 2019

* CORE: [FIX] Job includes last process status from wrong namespace
* CORE: [FIX] Remove all processors from the logger before a new sync jobs starts
* CORE: [CHANGE] Removed $natural mongodb sorting (replaced with indexed changed: 1 sort by default), ($natural sorting does not use indices ans is therefore too slow)
* CORE: [CHANGE] Changed log resource structure, Log resources have now a more identical structure than other resources
* CORE: [FIX] DOMXPath::query(): Invalid expression at /srv/www/tubee/src/lib/Endpoint/Xml.php:218
* CORE: [FIX] Log error if xml yields an invalid EndpointObject and continue with the next
* CORE: [FEATURE] Added support for $exists query to the XmlEndpoint
* CORE: [FIX] Watch streams now include updates and removals
* CORE: [FIX] Watch dataobjects Fatal error: Method StreamIterator\StreamIterator toString() must not throw an exception, caught TypeError: Argument 1 passed to Tubee\DataObject\Factory::build() must be of the type array
* CORE: [FIX] Do not drop fields if skip is true #56


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
