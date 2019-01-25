# Delete resources

Using `tubectl delete` will completely remove resources. You may recreate resourced with the same name, they
will only differ by their resource id.

This will remove the workflow `create` from the endpoint `csv` within the accounts collection.

```
tubectl delete wf accounts csv create -n playground
```

>**Note** Be very careful by deleting resources of type DataObject. They will loose their entire state and can not get removed from any possible export endpoints. It is [best practics](advanced/best-practics.md) to define a deleted timestamp field instead.
