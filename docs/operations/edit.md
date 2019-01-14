# Modify resources

Modify resources is a combination of options between [get](/operations/get) and [create](/operations/create) operations. You may query for resources to get changed and 
edit them in your editor of choice.

```
tubectl edit co mynamespace mycollection
```

The editing works the same as it does for [create](/operations/create).

## Query and edit

It ist possible to use most options which are also known to working for get. So for example it is possible to query resources and edit them directly:

```
tubectl edit do mynamespace mycollection -q data.field1=test,data.field2=foo
```

This will query for DataObject resources whereas data.field1 is test and data.field2 is foo.
A List object is mutable as well as long as you edit the resource within the list. A resource of type List itself is immutable.
