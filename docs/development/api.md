Tubee API
=========


**Version:** 1.0.0

**Terms of service:**  


**Contact information:**  
opensource@gyselroth.net  

**License:** [MIT](https://opensource.org/licenses/MIT)

### /
---
##### ***GET***
**Summary:** Get api entrypoint

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | List of objects |
| 403 | Access denied |

### /watch/mandators
---
##### ***GET***
**Summary:** Watch mandators

**Description:** Watch mandators in realtime

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | List of objects |
| 403 | Access denied |

### /mandators
---
##### ***GET***
**Summary:** Get mandators

**Description:** A mandator is a logical group of datatypes

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | List of mandators | [mandators](#mandators) |
| 403 | Access denied |  |

##### ***POST***
**Summary:** Add mandator

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| data | body |  | No | [mandator](#mandator) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 201 | If successful the server will respond with 201 Created | [mandator](#mandator) |
| 403 | Access denied |  |

### /mandators/{mandator}
---
##### ***GET***
**Summary:** Get specific mandator

**Description:** A mandator is a logical group of datatypes

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| attributes | query | Filter attributes | No | [ string ] |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Mandator | [mandator](#mandator) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

##### ***DELETE***
**Summary:** Delete specific mandator

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 204 | If successful the server will respond with 204 No Content |
| 403 | Access denied |
| 404 | Resource does not exists |

##### ***PUT***
**Summary:** Create or replace mandator

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| data | body |  | No | [mandator](#mandator) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | If successful the server will respond with 200 OK | [mandator](#mandator) |
| 201 | If successful and the object was newly created the server will respond with 201 Created | [mandator](#mandator) |
| 403 | Access denied |  |

##### ***PATCH***
**Summary:** Patch mandator as rfc6902 request

**Description:** Update specific attributes of a mandator

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| data | body | Mandator | No | [ [json-patch](#json-patch) ] |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Mandator | [mandator](#mandator) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

### /watch/mandators/{mandator}/datatypes
---
##### ***GET***
**Summary:** Watch datatypes

**Description:** Watch datatypes in realtime

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | List of objects |
| 403 | Access denied |

### /mandators/{mandator}/datatypes
---
##### ***GET***
**Summary:** Get datatypes

**Description:** A datatype is collection of data objects of a specific type

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | List of datatypes | [datatypes](#datatypes) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

##### ***POST***
**Summary:** Add datatype

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| data | body |  | No | [datatype](#datatype) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 201 | If successful the server will respond with 201 Created | [datatype](#datatype) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

### /mandators/{mandator}/datatypes/{datatype}
---
##### ***GET***
**Summary:** Get specific datatype

**Description:** A datatype is collection of dataobjects of a specific type

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| attributes | query | Filter attributes | No | [ string ] |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Datatype | [datatype](#datatype) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

##### ***DELETE***
**Summary:** Delete specific datatype

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 204 | If successful the server will respond with 204 No Content |
| 403 | Access denied |
| 404 | Resource does not exists |

##### ***PUT***
**Summary:** Create or replace datatype

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| data | body |  | No | [datatype](#datatype) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | If successful the server will respond with 200 OK | [datatype](#datatype) |
| 201 | If successful and the object was newly created the server will respond with 201 Created | [datatype](#datatype) |
| 403 | Access denied |  |

##### ***PATCH***
**Summary:** Patch datatype as rfc6902 request

**Description:** Update specific attributes of a datatype

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| data | body | Datatype | No | [ [json-patch](#json-patch) ] |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Datatype | [datatype](#datatype) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

### /watch/mandators/{mandator}/datatypes/{datatype}/endpoints
---
##### ***GET***
**Summary:** Watch endpoints

**Description:** Watch updates in realtime

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | List of objects |
| 403 | Access denied |

### /mandators/{mandator}/datatypes/{datatype}/endpoints
---
##### ***GET***
**Summary:** Get endpoints

**Description:** An endpoint is either of type source or destination and defines an import/export destination

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | List of endpoints | [endpoints](#endpoints) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

##### ***POST***
**Summary:** Add Endpoint

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| data | body |  | No | object |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 201 | If successful the server will respond with 201 Created | [endpoint](#endpoint) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

### /mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}
---
##### ***GET***
**Summary:** Get specific endpoint

**Description:** An endpoint is either of type source or destination and defines an import/export destination

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| endpoint | path | Endpoint name | Yes | string |
| attributes | query | Filter attributes | No | [ string ] |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Endpoint | [endpoint](#endpoint) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

##### ***DELETE***
**Summary:** Delete specific endpoint

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| endpoint | path | Endpoint name | Yes | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 204 | If successful the server will respond with 204 No Content |
| 403 | Access denied |
| 404 | Resource does not exists |

##### ***PUT***
**Summary:** Create or replace endppoint

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| endpoint | path | Endpoint name | Yes | string |
| data | body |  | No | [endpoint](#endpoint) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | If successful the server will respond with 200 OK | [endpoint](#endpoint) |
| 201 | If successful and the object was newly created the server will respond with 201 Created | [endpoint](#endpoint) |
| 403 | Access denied |  |

##### ***PATCH***
**Summary:** Patch datatype as rfc6902 request

**Description:** Update specific attributes of a endpoint

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| endpoint | path | Endpoint name | Yes | string |
| data | body | Datatype | No | [ [json-patch](#json-patch) ] |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Endpoint | [endpoint](#endpoint) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

### /watch/mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}/workflows
---
##### ***GET***
**Summary:** Watch workflows

**Description:** Watch updates in realtime

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| endpoint | path | Endpoint name | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | List of objects |
| 403 | Access denied |

### /mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}/workflows
---
##### ***GET***
**Summary:** Get endpoint workflows

**Description:** A workflow is an action how to import/export a datatype and with what attribute map

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| endpoint | path | Endpoint name | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | List of workflows | [workflows](#workflows) |
| 403 | Access denied |  |

##### ***POST***
**Summary:** Add worfklow to endpoint

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| endpoint | path | Endpoint name | Yes | string |
| data | body |  | No | [workflow](#workflow) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 201 | If successful the server will respond with 201 Created | [workflow](#workflow) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

### /mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}/workflows/{workflow}
---
##### ***GET***
**Summary:** Get specifc endpoint workflow

**Description:** A workflow is an action how to import/export a datatype and with what attribute map

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| endpoint | path | Endpoint name | Yes | string |
| workflow | path | Workflow name | Yes | string |
| attributes | query | Filter attributes | No | [ string ] |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Workflow | [workflow](#workflow) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

##### ***DELETE***
**Summary:** Delete specific workflow from

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| endpoint | path | Endpoint name | Yes | string |
| workflow | path | Workflow name | Yes | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 204 | If successful the server will respond with 204 No Content |
| 403 | Access denied |
| 404 | Resource does not exists |

##### ***PUT***
**Summary:** Create or replace workflow

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| endpoint | path | Endpoint name | Yes | string |
| workflow | path | Workflow name | Yes | string |
| data | body |  | No | [workflow](#workflow) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | If successful the server will respond with 200 OK | [workflow](#workflow) |
| 201 | If successful and the object was newly created the server will respond with 201 Created | [workflow](#workflow) |
| 403 | Access denied |  |

##### ***PATCH***
**Summary:** Patch workflow as rfc6902 request

**Description:** Update specific attributes of a workflow

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| endpoint | path | Endpoint name | Yes | string |
| workflow | path | Workflow name | Yes | string |
| data | body | Workflow | No | [ [json-patch](#json-patch) ] |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Workflow | [workflow](#workflow) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

### /mandators/{mandator}/datatypes/{datatype}/objects/{object}
---
##### ***GET***
**Summary:** Get specific object

**Description:** Get an object of a specific datatype

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| object | path | Object ID | Yes | string |
| attributes | query | Filter attributes | No | [ string ] |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | Data object |
| 403 | Access denied |
| 404 | Resource does not exists |

##### ***PATCH***
**Summary:** Patch object as rfc6902 request

**Description:** Update specific attributes of an object

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| object | path | Object ID | Yes | string |
| data | body | Object | No | [ [json-patch](#json-patch) ] |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | If successful the server will respond with 200 OK | [data-object](#data-object) |
| 202 | If write argument is true the server will respond with 202 Accepted since this is an asynchronous request. | [job](#job) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

##### ***PUT***
**Summary:** Replace or create object

**Description:** Replace all data attributes of an object (Or create one if not exists)

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| object | path | Object ID | Yes | string |
| write | query | If true, the objects gets synced to all configured destination endpoints | No | boolean |
| data | body |  | No | [data-object](#data-object) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | If successful the server will respond with 200 OK | [data-object](#data-object) |
| 201 | If successful and the object was newly created the server will respond with 201 Created | [data-object](#data-object) |
| 202 | If write argument is true the server will respond with 202 Accepted since this is an asynchronous request. | [job](#job) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

##### ***DELETE***
**Summary:** Delete object

**Description:** Delete a specific object

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| object | path | Object ID | Yes | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 202 | If write argument is true the server will respond with 202 Accepted since this is an asynchronous request. | [job](#job) |
| 204 | If successful the server will respond with 204 No Content |  |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

### /watch/mandators/{mandator}/datatypes/{datatype}/objects/{object}/relatives
---
##### ***GET***
**Summary:** Watch object relatives

**Description:** Watch updates in realtime

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| object | path | Object ID | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | List of objects |
| 403 | Access denied |

### /mandators/{mandator}/datatypes/{datatype}/objects/{object}/relatives
---
##### ***GET***
**Summary:** Get relative objects of an object

**Description:** Get all objects the object is related to

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| object | path | Object ID | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Object relatives | [object-relatives](#object-relatives) |
| 403 | Access denied |  |
| 404 | Resource not found |  |

##### ***POST***
**Summary:** Add new object relation to an object

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| object | path | Object ID | Yes | string |
| data | body |  | No | [object-relative](#object-relative) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Object relative | [object-relative](#object-relative) |
| 403 | Access denied |  |
| 404 | Resource not found |  |

### /mandators/{mandator}/datatypes/{datatype}/objects/{object}/relatives/{relative}
---
##### ***GET***
**Summary:** Get single relative object of an object

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| object | path | Object ID | Yes | string |
| relative | path | Object ID | Yes | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Object relative | [object-relative](#object-relative) |
| 403 | Access denied |  |
| 404 | Resource not found |  |

##### ***PUT***
**Summary:** Update object relation

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| object | path | Object ID | Yes | string |
| relative | path | Object ID | Yes | string |
| data | body |  | No | [object-relative](#object-relative) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Object relative | [object-relative](#object-relative) |
| 403 | Access denied |  |
| 404 | Resource not found |  |

##### ***DELETE***
**Summary:** Delete object relation

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| object | path | Object ID | Yes | string |
| relative | path | Object ID | Yes | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 204 | No body if successfully deleted |
| 403 | Access denied |
| 404 | Resource not found |

### /mandators/{mandator}/datatypes/{datatype}/endpoints/{endpoint}/objects
---
##### ***GET***
**Summary:** Get objects from endpoint

**Description:** An endpoint is either of type source or destination and defines an import/export destination

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| endpoint | path | Endpoint | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | List of objects | [endpoint-objects](#endpoint-objects) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

### /mandators/{mandator}/datatypes/{datatype}/objects/{object}/history
---
##### ***GET***
**Summary:** Get object history

**Description:** Get the history of all modifications from a specific object

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| object | path | Object ID | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Object history | [data-objects](#data-objects) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

### /watch/mandators/{mandator}/datatypes/{datatype}/objects
---
##### ***GET***
**Summary:** Watch objects

**Description:** Watch updates in realtime

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | List of objects |
| 403 | Access denied |

### /mandators/{mandator}/datatypes/{datatype}/objects
---
##### ***GET***
**Summary:** Get objects of a specific datatype

**Description:** A object is a data object from a specifc datatype

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | List of objects | [data-objects](#data-objects) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

##### ***POST***
**Summary:** Add a new object of a specifc datatype

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| mandator | path | Mandator name | Yes | string |
| datatype | path | Datatype | Yes | string |
| data | body |  | No | [data-object](#data-object) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 201 | If successful the server will respond with 201 Created | [data-object](#data-object) |
| 202 | If write argument is true the server will respond with 202 Accepted since this is an asynchronous request. | [job](#job) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

### /watch/secrets
---
##### ***GET***
**Summary:** Watch secrets

**Description:** Watch updates in realtime

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | List of secrets |
| 403 | Access denied |

### /secrets
---
##### ***GET***
**Summary:** Get secrets

**Description:** An secret defines what role is granted access to what resource

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | List of secrets | [secrets](#secrets) |
| 403 | Access denied |  |

##### ***POST***
**Summary:** Create a new secret

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| data | body |  | No | [secret](#secret) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 201 | The created secret | [secret](#secret) |
| 403 | Access denied |  |

### /secrets/{secret}
---
##### ***GET***
**Summary:** Get secret by name

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| secret | path | secret name | Yes | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | secret | [secret](#secret) |
| 403 | Access denied |  |
| 404 | secret does not exists |  |

##### ***PATCH***
**Summary:** Patch secret as rfc6902 request

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| secret | path | secret name | Yes | string |
| job | body | secret json patch | No | [ [json-patch](#json-patch) ] |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | The updated secret | [secret](#secret) |
| 403 | Access denied |  |

##### ***PUT***
**Summary:** Create or replace an secret

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| secret | path | secret name | Yes | string |
| data | body |  | No | [secret](#secret) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 201 | The created secret | [secret](#secret) |
| 403 | Access denied |  |

##### ***DELETE***
**Summary:** Delete secret by name

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| secret | path | secret name | Yes | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 204 | Server responds with 204 No Content if secret removal was successful |
| 403 | Access denied |
| 404 | secret does not exists |

### /watch/users
---
##### ***GET***
**Summary:** Watch users

**Description:** Watch updates in realtime

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | List of users |
| 403 | Access denied |

### /users
---
##### ***GET***
**Summary:** Get users

**Description:** An User defines what role is granted access to what resource

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | List of users | [users](#users) |
| 403 | Access denied |  |

##### ***POST***
**Summary:** Create a new User

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| data | body |  | No | [user](#user) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 201 | The created User | [user](#user) |
| 403 | Access denied |  |

### /users/{User}
---
##### ***GET***
**Summary:** Get User by name

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| User | path | User name | Yes | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | User | [user](#user) |
| 403 | Access denied |  |
| 404 | User does not exists |  |

##### ***PATCH***
**Summary:** Patch User as rfc6902 request

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| User | path | User name | Yes | string |
| job | body | User json patch | No | [ [json-patch](#json-patch) ] |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | The updated User | [user](#user) |
| 403 | Access denied |  |

##### ***PUT***
**Summary:** Create or replace an User

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| User | path | User name | Yes | string |
| data | body |  | No | [user](#user) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 201 | The created User | [user](#user) |
| 403 | Access denied |  |

##### ***DELETE***
**Summary:** Delete User by name

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| User | path | User name | Yes | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 204 | Server responds with 204 No Content if User removal was successful |
| 403 | Access denied |
| 404 | User does not exists |

### /watch/access-rules
---
##### ***GET***
**Summary:** Watch access rules

**Description:** Watch updates in realtime

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | List of access rules |
| 403 | Access denied |

### /access-rules
---
##### ***GET***
**Summary:** Get access rules

**Description:** An access rule defines what role is granted access to what resource

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | List of access rules | [access-rules](#access-rules) |
| 403 | Access denied |  |

##### ***POST***
**Summary:** Create a new access rule

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| data | body |  | No | [access-rule](#access-rule) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 201 | The created access rule | [access-rule](#access-rule) |
| 403 | Access denied |  |

### /access-rules/{access-rule}
---
##### ***GET***
**Summary:** Get access rule by name

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| access-rule | path | Access rule name | Yes | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Acccess rule | [access-rule](#access-rule) |
| 403 | Access denied |  |
| 404 | access rule does not exists |  |

##### ***PATCH***
**Summary:** Patch access rule as rfc6902 request

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| access-rule | path | Access rule name | Yes | string |
| job | body | Access rule json patch | No | [ [json-patch](#json-patch) ] |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | The updated access rule | [access-rule](#access-rule) |
| 403 | Access denied |  |

##### ***PUT***
**Summary:** Create or replace an access rule

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| access-rule | path | Access rule name | Yes | string |
| data | body |  | No | [access-rule](#access-rule) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 201 | The created access rule | [access-rule](#access-rule) |
| 403 | Access denied |  |

##### ***DELETE***
**Summary:** Delete access-rule by name

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| access-rule | path | Access rule name | Yes | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 204 | Server responds with 204 No Content if access rule removal was successful |
| 403 | Access denied |
| 404 | Access rule does not exists |

### /watch/access-roles
---
##### ***GET***
**Summary:** Watch access roles

**Description:** Watch updates in realtime

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | List of access roles |
| 403 | Access denied |

### /access-roles
---
##### ***GET***
**Summary:** Get access roles

**Description:** An access role defines what role is granted access to what resource

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | List of access roles | [access-roles](#access-roles) |
| 403 | Access denied |  |

##### ***POST***
**Summary:** Create a new access role

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| data | body |  | No | [access-role](#access-role) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 201 | The created access role | [access-role](#access-role) |
| 403 | Access denied |  |

### /access-roles/{access-role}
---
##### ***GET***
**Summary:** Get access role by name

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| access-role | path | Access role name | Yes | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Acccess role | [access-role](#access-role) |
| 403 | Access denied |  |
| 404 | access role does not exists |  |

##### ***PATCH***
**Summary:** Patch access role as rfc6902 request

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| access-role | path | Access role name | Yes | string |
| job | body | Access role json patch | No | [ [json-patch](#json-patch) ] |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | The updated access role | [access-role](#access-role) |
| 403 | Access denied |  |

##### ***PUT***
**Summary:** Create or replace an access role

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| access-role | path | Access role name | Yes | string |
| data | body |  | No | [access-role](#access-role) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | The updated access role | [access-role](#access-role) |
| 201 | The created access role if it did not exists yet | [access-role](#access-role) |
| 403 | Access denied |  |

##### ***DELETE***
**Summary:** Delete access-role by name

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| access-role | path | Access role name | Yes | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 204 | Server responds with 204 No Content if access role removal was successful |
| 403 | Access denied |
| 404 | Access role does not exists |

### /watch/jobs
---
##### ***GET***
**Summary:** Get realtime updates

**Description:** A job is an asynchronous server process

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | List of active jobs |
| 403 | Access denied |

### /jobs
---
##### ***GET***
**Summary:** Get list of active queued jobs

**Description:** A job is an asynchronous server process

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | List of active jobs | [jobs](#jobs) |
| 403 | Access denied |  |

##### ***POST***
**Summary:** Create new job

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| data | body |  | No | [job](#job) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 202 | The created job | [job](#job) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

### /jobs/{job}
---
##### ***GET***
**Summary:** Get job by id

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| job | path | Job ID | Yes | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Job | [job](#job) |
| 403 | Access denied |  |
| 404 | Job does not exists |  |

##### ***DELETE***
**Summary:** Delete job by id

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| job | path | Job ID | Yes | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 204 | Server responds with 204 No Content if job removal was successful |
| 403 | Access denied |
| 404 | Job does not exists |

##### ***PATCH***
**Summary:** Patch job as rfc6902 request

**Description:** Update specific attributes of a job

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| job | path | Job ID | Yes | string |
| data | body | Object | No | [ [json-patch](#json-patch) ] |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | If successful the server will respond with 200 OK | [job](#job) |
| 403 | Access denied |  |
| 404 | Resource does not exists |  |

### /processes
---
##### ***GET***
**Summary:** Get all processes

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Process | [processes](#processes) |
| 403 | Access denied |  |
| 404 | Job does not exists |  |

##### ***POST***
**Summary:** Trigger a new process

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| data | body |  | No | [process](#process) |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 202 | Returns a 202 if successful | [process](#process) |
| 403 | Access denied |  |
| 404 | Job does not exists |  |

### /watch/processes
---
##### ***GET***
**Summary:** Watch job processes

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | Processes |
| 403 | Access denied |
| 404 | Job does not exists |

### /processes/{process}
---
##### ***GET***
**Summary:** Get a single process of a job

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| process | path | Process ID | Yes | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Process | [process](#process) |
| 403 | Access denied |  |
| 404 | Process does not exists |  |

##### ***DELETE***
**Summary:** Abort running process

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| process | path | Process ID | Yes | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 204 | Server responds with 204 No Content if process abort was successful |
| 403 | Access denied |
| 404 | Process does not exists |

### /jobs/{job}/logs
---
##### ***GET***
**Summary:** Get logs of a job

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| job | path | Job ID | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Job | [log](#log) |
| 403 | Access denied |  |
| 404 | Job does not exists |  |

### /jobs/{job}/logs/{log}
---
##### ***GET***
**Summary:** Get a single job error

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| job | path | Job ID | Yes | string |
| log | path | Log id | Yes | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Log | [log](#log) |
| 403 | Access denied |  |
| 404 | Job does not exists |  |

### /watch/jobs/{job}/logs
---
##### ***GET***
**Summary:** Watch log stream

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| job | path | Job ID | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | Logs |
| 403 | Access denied |
| 404 | Job does not exists |

### /processes/{process}/logs
---
##### ***GET***
**Summary:** Get logs of a process

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| process | path | Process ID | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Log | [log](#log) |
| 403 | Access denied |  |
| 404 | Process does not exists |  |

### /process/{process}/logs/{log}
---
##### ***GET***
**Summary:** Get a single process log

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| process | path | Process ID | Yes | string |
| log | path | Log id | Yes | string |

**Responses**

| Code | Description | Schema |
| ---- | ----------- | ------ |
| 200 | Log | [log](#log) |
| 403 | Access denied |  |
| 404 | Log does not exists |  |

### /watch/processes/{process}/logs
---
##### ***GET***
**Summary:** Watch log stream

**Parameters**

| Name | Located in | Description | Required | Schema |
| ---- | ---------- | ----------- | -------- | ---- |
| process | path | Process ID | Yes | string |
| query | query | Specify a MongoDB based resource query (https://docs.mongodb.com/manual/tutorial/query-documents) using JSON (For example: {"name": {$regex: 'foo.*'}}). | No | string |
| attributes | query | Filter attributes | No | [ string ] |
| offset | query | Objects offset, per default it starts from 0. You may also request a negative offset which will return results from the end [total - offset]. | No | number |
| limit | query | Objects limit, per default 20 objects will get returned | No | number |
| sort | query | Specify a MongoDB sort operation (https://docs.mongodb.com/manual/reference/method/cursor.sort/) using JSON (For example: {"name": -1}). | No | string |

**Responses**

| Code | Description |
| ---- | ----------- |
| 200 | Logs |
| 403 | Access denied |
| 404 | Job does not exists |

### Models
---

### json-patch  

A JSON Patch according rfc6902.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| op | string |  | No |
| path | string |  | No |
| value | string |  | No |

### links  

Contains resource links (URL) to other resources.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| self | [link](#link) |  | No |

### list-links  

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| self | [link](#link) |  | No |
| prev | [link](#link) |  | No |
| next | [link](#link) |  | No |

### link  

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| href | string |  | No |

### list  

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| _links | [list-links](#list-links) | Holds a list of links rfc1738 to other resources. | No |
| kind | string | The resource type, always List. | No |
| count | number | Holds the number of items in the current list response. | No |
| total | number | Holds the number of total available items on the server. Note that a List resource is always paged. You need to traverse with offset and limit to request further resources in the list. | No |

### resource  

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| _links | [links](#links) |  | No |
| name | string | Resource identifier. Note that the name is immutable once created on the server and must be unique in its own resource context. | No |
| id | string | Unique 12-byte resource identifier. Note this is a MongoDB ObjectId. The name is the standard resource identifier, the id only useful to verify that a given resource was completely recreated. An ID is immutable and will be created on the server. | No |
| version | number | The version of the resource. A version gets increased once the resource have been modified. | No |
| created | string | ISO 8601 timestamp when the resource was created. | No |
| changed | string | ISO 8601 timestamp when the resource was changed. | No |
| secrets | [ [secret-mount](#secret-mount) ] | Injected secrets in this resource. | No |

### secret-mount  

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| secret | string | The name of the secret from which the key's value should be injected. | No |
| key | string | The name of the key which should be taken from a secret (You may use a recursive path by delimiting keys with '.', for example: password). | No |
| to | string | The resource path where the secret value should be injected (You may use a recursive path by delimiting keys with '.', for example: data.resource.password). | No |

### mandators  

A list of mandators.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| mandators |  | A list of mandators. |  |

### mandator  

A mandator is a namespace to separate resources.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| mandator |  | A mandator is a namespace to separate resources. |  |

### secrets  

A list of secrets.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| secrets |  | A list of secrets. |  |

### secret  

A secret is sensitive information which can be injected into another resource. A secret gets specially encrypted on the server and is always base64 encoded.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| secret |  | A secret is sensitive information which can be injected into another resource. A secret gets specially encrypted on the server and is always base64 encoded. |  |

### users  

A list of users.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| users |  | A list of users. |  |

### user  

A local tubee user.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| user |  | A local tubee user. |  |

### access-rules  

A list of access rules.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| access-rules |  | A list of access rules. |  |

### access-rule  

An access rule allows to specify what access roles can access which resources.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| access-rule |  | An access rule allows to specify what access roles can access which resources. |  |

### access-roles  

A list of access roles.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| access-roles |  | A list of access roles. |  |

### access-role  

An access role is defined list which matches authenticated user identifiers.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| access-role |  | An access role is defined list which matches authenticated user identifiers. |  |

### processes  

A list of processes.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| processes |  | A list of processes. |  |

### process  

A process is a sub resource of a job. Each process represents one job execution.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| process |  | A process is a sub resource of a job. Each process represents one job execution. |  |

### jobs  

A list of jobs.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| jobs |  | A list of jobs. |  |

### job  

A job is a synchronization job which declares when and what datatypes should be synchronized.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| job |  | A job is a synchronization job which declares when and what datatypes should be synchronized. |  |

### logs  

A list of logs.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| logs |  | A list of logs. |  |

### log  

A log messagage from a process.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| log |  | A log messagage from a process. |  |

### datatypes  

A list of datatypes.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| datatypes |  | A list of datatypes. |  |

### datatype  

A datatype is a collection of data objects, meaning a collection of similar objects.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| datatype |  | A datatype is a collection of data objects, meaning a collection of similar objects. |  |

### endpoints  

A list of endpoints.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| endpoints |  | A list of endpoints. |  |

### endpoint  

An endpoint represents an external resource to browse (proxy), import or export. This may be a database, a file, a http service, ...

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| endpoint |  | An endpoint represents an external resource to browse (proxy), import or export. This may be a database, a file, a http service, ... |  |

### pdo-endpoint  

Pdo endpoint

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| pdo-endpoint |  | Pdo endpoint |  |

### mysql-endpoint  

MySQL/MariaDB (and other MySQL forks) endpoint

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| mysql-endpoint |  | MySQL/MariaDB (and other MySQL forks) endpoint |  |

### ldap-endpoint  

LDAP (OpenLDAP, Microsoft AD and other LDAP compatible Server) endpoint

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| ldap-endpoint |  | LDAP (OpenLDAP, Microsoft AD and other LDAP compatible Server) endpoint |  |

### odatarest-endpoint  

OData REST API endpoint (Compatible with Microsoft graph (Office365 and more) and other OData compatible api's)

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| odatarest-endpoint |  | OData REST API endpoint (Compatible with Microsoft graph (Office365 and more) and other OData compatible api's) |  |

### workflows  

A list of workflows.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| workflows |  | A list of workflows. |  |

### workflow  

A workflow gets used if an endpoint gets imported or exported. A workflow defines if and what object and also if and what attributes of an object should be written to or from an endpoint.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| workflow |  | A workflow gets used if an endpoint gets imported or exported. A workflow defines if and what object and also if and what attributes of an object should be written to or from an endpoint. |  |

### attribute-map  

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| map | object | Attribute map | No |

### endpoint-objects  

A list of endpoint objects.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| endpoint-objects |  | A list of endpoint objects. |  |

### endpoint-object  

An endpoint object is the actual object on an endpoint itself.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| endpoint-object |  | An endpoint object is the actual object on an endpoint itself. |  |

### data-objects  

List of data objects.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| data-objects |  | List of data objects. |  |

### data-object  

A data object represents a single object in a datatype (data collection).

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| data-object |  | A data object represents a single object in a datatype (data collection). |  |

### object-relatives  

A list of related objects.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| object-relatives |  | A list of related objects. |  |

### object-relative  

An object relation represents a releationship between two data objects. A relationship may apply to objects of different datatypes and/or mandators.

| Name | Type | Description | Required |
| ---- | ---- | ----------- | -------- |
| object-relative |  | An object relation represents a releationship between two data objects. A relationship may apply to objects of different datatypes and/or mandators. |  |