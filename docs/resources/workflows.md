# Workflows

A workflow defines how attributes are mapped between tubee and an endpoint. Workflows are required for both a source and a destination endpoint.
For source endpoints a worklfow specifies what attributes from an endpoint are mapped to a tubee DataObject. The same is true for destination endpoint workflows
but just reversed. Such a workflow defines what tubee attributes get mapped to a destination endpoint.

Each endpoint may have more than one workflow however only one workflow can get selected and executed.
What workflow is selected depends on what ensure type is set or if a condition allows the worklfow to get executed.

## Create workflow

This will create a workflow for the endpoint named `ldap` within the collection `accounts`.

```yaml 
name: create-update
kind: Workflow
namespace: playground
collection: accounts
endpoint: ldap
data:
  priority: 0
  ensure: last
  condition: "core.result(core.object.data.disabled === false && core.object.data.username)"
  map:
  - name: entrydn
    script: "core.result('uid='+core.object.data.username+',ou='+core.object.department+',o=company,dc=example,dc=org')"
    required: true
  - name: uid
    from: data.username
    required: false
  - name: sn
    from: data.last_name
    required: false
  - name: givenName
    from: data.givenname
    required: false
  - name: cn
    script: "core.result(core.object.data.firstname +' '+core.object.data.last_name)"
    required: false
  - name: objectClass
    value: 
      - inetOrgPerson
    required: true
```

```sh
tubectl create -f spec.yaml
```

Check the just created resource:

```sh
tubectl get wf accounts ldap create-update -n playground -o yaml
```

## Ensure

The ensure type defines what the workflow actually should do. It defines in what state a [DataObject](resources/data-objects.md) or an [EndpointObject](resources/endpoint-objects.md) should be. 
There are three known ensure types:

| Ensure      | Description  |
| ------------- |--------------|
| exists | The resource gets created if it does not already exists.  |
| last | The resource gets created if it does not already exists and if it does the resource gets updated.  |
| absent | The resource gets removed.  |

The default ensure type for a workflow is always `last`.

## Condition

Each workflow may have a scripted condition. This condition gets execute and determines if a given workflow shall get executed.
The condition is JavaScript and gets executed by googles [V8](https://github.com/v8/v8) engine.
The condition has to execute `core.result(<bool>)` to define if the workflow should get executed.
Each object traverses the workflow stack and gets tested against each workflow as soon as one workflow matches.
Each currently processed object is available at `core.object`.

Example:
```js
core.result(core.object.data.disabled === false && core.object.data.username)
```

This workflow only gets executed if the object has a property `disabled` with a value `false` and has a field `username` set.

>**Note** By default there is no condition. A workflow may only match if the ensure type does match.

## Priority

Besides a condition and the ensure type there is possibility to set a priority in which order the workflows get tested.
By default each workflow has the priority `0`.
`0` is the highest priority. A workflow with the priority `0` gets executed before a worklfow with the priority `2`.

## Mapping

The mapping is the most important fact about a workflow. The mapping defines what and how certain attributes get mapped to each other.
For example it defines what attributes from a [DataObject](resources/data-objects.md) get mapped to what attributes of an OpenLDAP object.
The mapping is defined within `map` and contains a list of attribute mappings. Each attribute mapping may have different options.

| Option      | Default | Description  |
| ------------- | -------------- |--------------|
| name | `<required>` | The name of the destination attribute. (May also contain `.` to specify a deep path like `data.username`).  |
| ensure | `last` | Like a workflow itself, each attribute may have a different ensure level.  |
| from | `null` | Map the value from the attribute 1:1 to the attribute named in `name`.  |
| value | `null`  | Defines a static value. |
| script | `null` | Execute JavaScript using the V8 engine.  |
| type | `<same type as value>` | Convert the value to another type. |
| rewrite | `null` | Rewrite a mapped attribute to another value (May also be done using a scripted attribute). |
| unwind | `null`  | Unwind a list and operate attribute options on each list element. |
| skip | `false` | If true the attribute gets ignored. This may be useful if an attribute is only used for an object relation creation or relation context. |
| map | <object> | Define an object relation mapping to another collection. Automatically create DataObjectReations. |
| writeonly | `false` | Writeonly flag may be set to true for attributes which get not returned by endpoints (writeonly attributes). |

### Name

The name is required and defines the name of the destination attribute name. 
>**Note** Important, DataObject attributes are usually in the data container.  

For example, lets define an attribute from an active directory source endpoint and map the samAccountName to our DataObject.

```yaml
map:
- name: data.username
  from: samAccountName
```

The `data.` prefix is required since a DataObjects data is placed within a data container. See an example of a DataObject [here](resources/data-objects.md).

### Ensure

The ensure type on a attributes knows one more type `merge` compared to workflow itself.

| Ensure      | Description  |
| ------------- |--------------|
| exists | The attribute gets only mapped if it does not exists yet.  |
| last | The attribute gets mapped with the latest value.  |
| absent | The attribute gets removed.  |
| merge | The attributes get merged, only useful if the value is an array/list.  |


### Map: from/value/script

A mapping attribute may be one of `from` (1:1 mapping), `value` (static mapping) or `script` (scripted attribute).

#### From
From defines the source attribute. The given source attributes value gets mapped to the named attribute.

#### Value
Instead using a value from an object, you may define a static value for an attribute using `value`.

#### Script
Defines mighty scipted attributes. The engine executes JavaScript and used the result of `core.result()` as the value for the attribute.

### Type

By default the source attributes type will be untouched. However you may convert the value to another type.
The same attribute types as defined in a [collection schema](resources/collections.md#schema) are available.

>**Note** The type conversion takes place at the very end of the attribute mapping.

### Rewrite

You may use rewrite rules to rewrite a value. Rewrite rules may either be of static matching using `from` or 
a regex ([PCRE](https://www.pcre.org/)) matching using `match`.
The rewrite processor stops as soon as either `from` or a regex `match` match the given attributes value.

Example:
```yaml
map:
- name: entrydn
  script: "core.result('uid='+core.object.data.username+',ou='+core.object.department+',o=company,dc=example,dc=org')"  
  rewrite:
  - from: uid=admin,ou=admins,o=company,dc=example,dc=org
    to: uid=Administrator,ou=admins,o=company,dc=example,dc=org
  - match: #uid=([^,]),ou=hr,o=company,dc=example,dc=org#
    to: uid=$2,ou=secretariat,o=company,dc=example,dc=org
  - match: #uid=([^,]),ou=assistence,o=company,dc=example,dc=org#
    to: uid=$2,ou=secretariat,o=company,dc=example,dc=org
```

>**Note** Rewrite rules get execute once the value is determined from either from,value or script but before a possible type conversion.

You may achive the same using scripted attributes. But rewrite rules are a more readable way and should be the preferred choice if you need
to rewrite any values.

### Unwind

Using unwind you may operate on each element within a list attribute. You may apply each attribute mapping option to a unwind operator.

For example, we have a [DataObject](resources/data-objects.md) with an attribute adresses which is a list of adresses and we want to 
map all streets to an attribute called street.

DataObject:
```yaml
kind: DataObject
collection: people
data:
  firstName: John
  lastName: Meyer
  adresses: 
  - street: First street 20
    zip: 33445
    city: New York
  - street: Company street 38
    zip: 3445
    city: Zurich
``` 

The goal is to get an attribute called street with the value `['First street 20', 'Company street 38']`.
Lets unwind the attribute `adresses` and grab the value with `from`.

>**Note** While unwindig the each list value gets available at `root`.

```yaml
map:
- name: street
  from: data.adresses
  unwind:
    from: root.street
```

The same is achievable using scripted attributes, but like rewrite rules, unwinding may be the preferrable solution.

 ### Map

Map is particulary useful to automatically create DataObjectRelations between DataObjects. DataObjectRelations represent a relationship between two (or more) DataObjects.
Those DataObject may be from different collections and/or namespaces.

Lets asume we have a collection named `accounts` and one named `groups`.
We would like to import LDAP objects into both of them. Into accounts we import person objects and into group group objects.
While importing we'd like to automatically create tubee DataObjectRelations between group and group members. 
Lets asume those LDAP group objects have an attribute `member` which is a list of user distinguished names.
To automatically create a relationship we need to specify the `map` keyword and pointing the destination collection to `accounts` and
the key which gets used to identify the destination account object to `data.dn` (data.dn will hold the account dn).

Optionally we set an attribute `ou` as context.

Example:
```yaml
map:
- name: data.member
  from: member
  map:
    collection: accounts
    to: data.dn
    context:
    - ou
  skip: true
- name: data.ou
  from: ou
  skip: true
```

This mapping will automatically try to create DataObjectRelations between those two collections.

| Option      | Default | Description  |
| ------------- | -------------- |--------------|
| collection | `<required>` | The name of the destination collection.  |
| to | `<required>` | The name of the DataObject in the destination collection to use as relationship reference. (Usually you will ned to specify the attribute within data. (for example data.id)).  |
| ensure | `last` | By default the relationship gets created and updated. You may set ensure to `exists` or `absent` while on exists the relation will not get updated (for example if context data gets changed). If absent the relation gets removed.  |
| context | `<empty list>` | A list of attributes to use as context attributes. (Note that you can also use attributes which have `skip: true` set.)  |

### Skip

Skip is by default false. An attribute which has skip on `true` is declared as a virtual attribute and never gets written anywhere. 
However a skip: true attribute may be useful if it gets used to write a DataObjectRelation context (See [Map](#Map)).


### Writeonly 

A password attribute is an example for such attributes. Passwords usually may be writeable but get not returned. While writeonly is set to true and DataObject already exists on the endpoint, the attribute does not get written. writeonly: true attributes may only get written during object creation. Note that it does not matter what value `ensure` holds while the attribute is set on writeonly and the DataObject already exists.
