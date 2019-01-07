# Apply from file

`tubectl apply` is an advanced command to create and modify one or multiple resources from a file.
Existing resources get updated and new ones get created accordingly.

For example a file resources.yaml with the following content is given:

```yaml
name: default
kind: Namespace
---
name: news
namespace: default
kind: Collection
```

Executing `tubectl apply -f resources.yaml` will create those two resources for you. 
You may edit this file locally and run `tubectl apply -f resources.yaml` again to apply your changes. 

>**Note** The order of resources does not matter. tubectl will order the resource for you automatically.

```yaml
name: default
kind: Namespace
---
name: news
namespace: default
kind: Collection
data:
  schema: 
    title: string
```

Executing `tubectl apply -f resources.yaml` again will leave the namespace resource `default` untouched since nothing changed but will update the collection `news`.
