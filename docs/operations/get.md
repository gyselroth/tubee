# Request & Query resources

`tubectl get` knows various different options to request the resources you actually want. 
First you need to specify the type of resource you want to query (`tubectl get <resource> [name]`). 
This will list the latest 20 resources of the requested type in a pretty table. By default the tubee server orders its resource by the created date/time of the resource.

```
tubectl get ns
+---------------------------+---------------------------+---------------------------+---------------------------+
| Name                      | Version                   | Changed                   | Created                   |
|---------------------------|---------------------------|---------------------------|---------------------------|
| foo                       | 1                         | 3 weeks ago               | 4 weeks ago               |
+---------------------------+---------------------------+---------------------------+---------------------------+
| bar                       | 3                         | 2 weeks ago               | 4 weeks ago               |
+---------------------------+---------------------------+---------------------------+---------------------------+
```


If only the resource type is specified, a resource type List gets returned by the server which holds the desired resource types. It is certainly possible to specify by name what resource should get returned.

```
tubeectl get ns foo
```

Most resource types know an alias to help you to type as fast as possible. `ns` in this case is the same as `namespaces`.
`tubectl help get` lists all possible resources to query from and also their related alias.

>**Note** Not all resource types have an alias. Usually only resource with a name >6 characters.


## Sort output
You may change the default sorting behaviour of the server if you specify `-s` or `--sort` accordingly. 

```
tubectl get ns --sort name
```
This will query the latest namespaces sorted by the resource name. You may change the sort order by specifying asc or desc. 

```
tubectl get ns --sort name=desc
```

You may as well specify multiple fileds comma separated.

```
tubectl get ns --sort name=desc,changed=asc
```

If the human styled way to sort output does not fit your needs it is still possible to request a custom sort specification using json.
You may specify this with `--json-sort`. This gets interpreted by the server as such and must be a valid [MongoDB sort](https://docs.mongodb.com/manual/reference/method/cursor.sort/) definition.
This will do the same job as above:

```
tubectl get ns --json-sort '{"name":0,"changed":1}'
```

## Change the output format
By default tubectl will try to pretty print the requested resources in a table on your shell. By design this output may not hold the information you require.
The output format can be changed by using `-o` or `--output` accordingly.

Besides the default there is also yaml or json output.

>**Note** Some resources such as Log may also have even more possibilities.

```
tubectl get ns -o yaml
```

which results in a yaml style output:
```yaml
kind: List
_links:
  self:
    href: 'https://localhost:8090/api/v1/namespaces?offset=0&limit=20'
count: 1
total: 1
data:
  - _links:
      self:
        href: >-
          https://localhost:8090/api/v1/namespaces?query=%7B%7D&offset=NaN&limit=20&sort=%7B%7D
    kind: Namespace
    id: 5bd1a94b035418001a337722
    name: foo
    version: 1
    created: '2018-10-25T11:30:19+00:00'
    changed: '2018-10-25T11:30:19+00:00'
    secrets: []
```

You may also draw a custom table by using `cc` and specify comma separated label:value. The follwing example will print all endpoints within the playground namespace and list the name and the endpoint type.
```
tubectl get ep playground foo -o cc="Name:name,Endpoint Type:data.type"

+---------------------------+---------------------------+
| Name                      | Endpoint Type             |
|---------------------------|---------------------------|
| foo                       | destination               |
|---------------------------|---------------------------|
| bar                       | destination               |
|---------------------------|---------------------------|
| foobar                    | source                    |
+---------------------------+---------------------------+
```

## Query & search resources
Usually the latest 20 resources are not enaugh to work with. By using the option `-q` or `--field-selector` you may find the needed resource.
Like the sort operation a query works in the format key=value and may be delimited by `,`. Keep in mind that delimiting will work as `AND` queries. However it is possible to specify `-q` or `--field-selector` multiple times which will result in `OR` queries.

>**Note** There may be endpoints which do not accept such complex queries (Like the Ucs endpoint).

```
tubectl get ns -q name=bar
```
>**Note** To find a resource by name just specify the name after the resource type since this is the shorthand method to the above example.

or a more complex example:

```
tubectl get ns -q name=bar,version=1 -q name=foo
```

Like `--json-sort` there is also a possibility to use `--json-query`. Likewise sort the query definition is a [MongoDB style query](https://docs.mongodb.com/manual/tutorial/query-documents/).

```
tubectl get ns --json-query '{"$or":["changed":{"$gt":"2018-10-25"},{"name":"bar"}]}'
```

## Using resources in other namespaces
The default namespace is `default`. You may request a different namespace by using `-n` or `--namespace` accordingly.
For example to query all collections in the namespace foo:

```
tubectl get co -n foo
```

You may configure a different namespace by using tubectl config.
```
tubectl edit config defaultNamespace foo
```

## Tail resources
By default tubecli lists the latest 20 resources ordered by created date/time. Meaning the first record is the newest one and the last the oldest (If not more than 20 resources of the requested type are available). By using `-t` or `--tail` accordingly the output is reversed. Meaning the last record is the newest and the first the oldest (Again, if there are not more than 20 resources available of that type.)

### Resource history & diff
Each modification on a resource will result in an incrision of the resource version and the old version gets stored safely. You can find the resource version in the default output on most resource types or by specifying a custom output such as yaml. The resources history can be requested if `-v` or `--history` accordingly is specified. Note that if this option is requested a resource name must be specified. You can not request the history on a List response (Multiple resources).
`-H` will list all older versions of the requested resource.

```
tubectl get ns foo -H
```

tubectl features also diff mechanism whereas you might find differences between resource version much more easily.
By specifying `-d` or `--diff` accordingly one can compare the resource differences in the difftool of your choice.

>**Note** You need to set an env variable DIFFTOOL on your host system for that case or start tubectl with such.
On linux, `vimdiff` is highly recommended. 

This will compare the namespace foo (current version) with the version 1 of itself.

```
DIFFTOOL=vimdiff tubectl get ns foo --diff 1
```

## Limit resources in lists
By default you get the newest 20 resources. You may lower this limit by using `-L` or `--limit` accordingly or increase to a maximum of 100 resources.
```
tubectl get ns --limit 2
```

>**Note** The limit can not be higher than 100. If more resources are required either specify a more exact query or request a stream by using `--stream`.

## Stream

To retrive a large list of resources you may use `--stream` which streams the resources back from the server instead normal request.
```
tubectl get do foo --stream
```

## Watch realtime updates

The tubee server can push realtime updates to listening clients. By specifying `-w` or `--watch` accordingly you will receive any updates made to resources of the requested list. This includes new resources, modifications or removals. Note that a watch request does operate for 5min and then dies. Watch does not include existing resources. If existing resource shall get returned, one may combine `--stream` and `--watch`. 
