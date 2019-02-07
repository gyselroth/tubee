# DataRelationObjects

DataRelationObjects represent a relationship between two (or more) DataObjects.
This is similar to a cross table in traditional relation SQL database engines. One defines a relationship between two objects.
All related objects can be retrieved from a single DataObject.
A tubee DataObjectRelation holds the releated objects (This may also be cross namespace and/or cross collection) and optionally
may hold unstructured context data which may provide more context data.

>**Note** tubee v1.0.0 only allows a relationship between exactly two DataObjects. (This behaviour will most likely be upgraded to allow an undefined number of cross relations).

## Create a new data object

Lets say we have a group `group1` in a collection `groups` and a user `user1` in 
a collection `users` and we want to declare a relationship between those very objects:

```yaml 
kind: DataObjectRelation
namespace: playground
name: user1-group2
data:
  relation:
  - namespace: playground
    collection: accounts
    object: user1
  - namespace: playground
    collection: groups
    object: group1
  context:
    foo: bar
```

>**Note** The namespace `playground` only defines in what namespace the relation object is placed, but not from what namespace the data objects are!

```sh
tubectl create -f spec.yaml
```

Check the just created resource:

```sh
tubectl get re user1-group2 -n playground -o yaml
```

DataObjectRelations can be automatically created during importing source endpoints and declaring [`map` in attributes](resources/workflows/#mapping).

## Get relations
`tubectl get re` will return a list of (all) unresolved DataRelationObjects. It is also possibile to retrive all related objects for 
a single DataObject directly using `-r` or `--relations` accordingly:

```
tubectl get do accounts user1 --relations
```

Given the example above, this will give us a list with relations for `user1`:

```yaml
kind: List
_links:
  self:
    href: >-
      https://localhost:8090/api/v1/namespaces/playground/collections/accounts/objects/user1/relations?offset=0&limit=100
count: 1
total: 1
data:
  - _links:
      self:
        href: >-
          https://localhost:8090/api/v1/namespaces/playground/collections/accounts/objects/user1/relations?query=%7B%7D&offset=0&limit=100&sort=%7B%7D&stream=false&watch=false
    id: 5c519fd9d9a7d2009b7f0695
    name: user1-group1
    version: 1
    created: '2019-01-30T13:00:09+00:00'
    changed: '2019-02-04T10:09:14+00:00'
    secrets: []
    kind: DataObjectRelation
    namespace: playground
    data:
      context:
        foo: bar
      relation:
        - namespace: playground
          collection: accounts
          object: user1
        - namespace: playground
          collection: groups
          object: group1
    status:
      object:
        _links:
          self:
            href: >-
              https://localhost:8090/api/v1/namespaces/playground/collections/accounts/objects/user1/relations?query=%7B%7D&offset=0&limit=100&sort=%7B%7D&stream=false&watch=false
          namespace:
            href: >-
              https://localhost:8090/api/v1/namespaces/playground?query=%7B%7D&offset=0&limit=100&sort=%7B%7D&stream=false&watch=false
          collection:
            href: >-
              https://localhost:8090/api/v1/namespaces/playground/collections/groups?query=%7B%7D&offset=0&limit=100&sort=%7B%7D&stream=false&watch=false
        id: 5c3736e58ad7e303ed591e49
        name: group1
        version: 1
        created: '2019-01-10T12:13:25+00:00'
        changed: '2019-01-10T12:13:25+00:00'
        secrets: []
        kind: DataObject
        namespace: playground
        collection: groups
        data:
          name: group1
          disabled: null
```
