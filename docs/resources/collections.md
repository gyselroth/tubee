# Collections

Each DataObject needs to be placed in a collection. See collection as a pod of multiple and similar objects. 
A collection may define a schema for its DataObjects but this is optional. At its simplest form a collection just has a name.

## Create a new collection

```yaml 
kind: Collection
namespace: playground
name: accounts
```

```sh
tubectl create -f spec.yaml
```

Check the just created resource:

```sh
tubectl get co accounts -n playground -o yaml
```

The collection `accounts` is now ready to be used.

## Define a collection with a schema

A schema enforces DataObjects to be consistent with the provided schema. An attribute in a defined schema may
specify the value type, a label and regex.

>**Note** Regex are of the type [PCRE](https://www.pcre.org/) - Perl Compatible Regular Expressions.

```yaml 
kind: Collection
namespace: playground
name: accounts
data:
  schema:
    username:
      label: Username
      type: string
      require_regex: '#[a-zA-Z0-1]+#'
    firstname:
      label: Firstname
      type: string
    lastname:
      label: Surname
      type: string
    disabled:
      label: Surname
      type: int
    mail:
      label: Mail adress
      type: string
      require_regex: '#[a-zA-z0-9.-]+\@[a-zA-z0-9.-]+.[a-zA-Z]+#'
```

>**Note** Changing a schema will have no affect on any existing DataObjects! DataObjects are only validated against the schema if they get added or modified.

### Attribute types

tubee acknowledges the following valid attribute value types:

| Name      | Description  |
| ------------- |--------------|
| `string` | Just a simple string  |
| `int` | A number positive or negative |
| `bool` | Boolean value, `true` or `false`  |
| `float` | A comma number like `0.12`  |
| `array` | Holds multiple values  |
| `null` | Represents NULL values  |
| `binary` | Binary content  |
