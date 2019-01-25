# Endpoints

Endpoints are other services around tubee. An endpoint may represent an Active Directory, OpenLDAP server, MongoDB server, XMl file or much more.
An endpoint is required if dataobjects needs to be exported or imported. An endpoint is always attached directly to a collection. If you have two or more collections and you want to synchronize objects from each of those, you will need to create an endpoint for each of those (And for every type of endpoint).
You may also create an endpoint to just browse objects in it and do not use it for any synchronization which is actually the default.

## Create endpoint

This will create an export endpoint and creates a JSON in /tmp/accounts.json with all data objects in the collection accounts.

>**Note** A workflow is required as well which defines the mapping between tubee and the JSON file. Using only an endpoint with no workflows does not do any action. 

```yaml 
name: json-export
kind: JsonEndpoint
namespace: playground
collection: accounts
data:
  storage:
    kind: StreamStorage
  type: destination
  options:
    identifier: null
    flush: true
    import: []
    filter_one: test
    filter_all: null
  resource: []
  file: /tmp/accounts.json
```

```sh
tubectl create -f spec.yaml
```

Check the just created resource:

```sh
tubectl get ep accounts json-export -n playground -o yaml
```

## Destination endpoints
Destination endpoints are used to export objects from tubee to another service.

An endpoint with type `destination` always requires the option `filter_one` to be defined. This filter defines how an object on the endpoint can be uniquely identified by tubee.
This filter varies between the different type of endpoints. For example file_one with LdapEndpoint is an ldap query. Using MysqlEndpoint filter_one is an SQL where query. Using XmlEndpoint filter_one is an xpath.

Like filter_one you may specify a filter_all which filters all data objects on the endpoint. This may be useful if the endpoint is of type `source`.

## Source endpoints

Source endpoints are used to import objects from other services into tubee. Each record from the endpoint gets created as a single DataObject in a tubee collection.
Source endpoints require the option `import` to be defined. This options holds a list of one or more attribute names which gets used to uniquely identify a tubee DataObject and its object on the endpoint.

## Endpoints

| Resource      | Description  |
| ------------- |--------------|
| LdapEndpoint | LDAP compatible server (OpenLDAP, Active Directory, ...) |
| PdoEndpoint | SQL server |
| MysqlEndpoint | MySQL/MariaDB server (native client) |
| MongodbEndpoint | MongoDB server |
| MoodleEndpoint | Moodle |
| BalloonEndpoint | [balloon](https://github.com/gyselroth/balloon) cloud server |
| ODataRestEndpoint | REST API defined as [OData](https://www.odata.org/), like microsoft online services (Office 365). |
| XmlEndpoint | Xml data format |
| CsvEndpoint | Csv data format |
| JsonEndpoint | Json data format |
| ImageEndpoint | Binary images |
| UcsEndpoint | [Univention Corporate server](https://www.univention.com/products/ucs/) |

XmlEndpoint, CsvEndpoint, JsonEndpoint and ImageEndpoint use a storage technolgy to define where data can be read or written. Possible storage drivers are:

| Name      | Description  |
| ------------- |--------------|
| LocalFilesystem | Path on the local filesystem where the tubee server runs |
| Balloon | balloon cloud server |
| Smb | Windows/Samba share |
| Stream | Stream (HTTP, FTP, ...) |
