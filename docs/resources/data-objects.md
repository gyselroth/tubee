# DataObjects

A DataObject is a record from a Collection. It can represent anyting and may hold data of any kind (As long as it follows the collection schema).

## Create a new data object

```yaml 
kind: DataObject
namespace: playground
collection: accounts
name: user
data:
  username: user
  mail: user@example.org
  firstname: user
  lastname: bar
  disabled: null
```

```sh
tubectl create -f spec.yaml
```

Check the just created resource:

```sh
tubectl get do accounts user -n playground -o yaml
```
