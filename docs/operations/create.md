# Create resources

A new resource can easily be created using `tubectl create <resource>`.
By default tubectl tries to open your editor of choice. The editor gets selected from the env variable `EDITOR`.

```
tubectl create ns
```

You may as well specify the editor:
```
EDITOR=vi tubectl create ns
```

After exiting the editor, tubectl tries to create the specified resource, if this fails for whatever reason the error message gets prepended on the top
and you may correct the error. If you do not change anything and quit a second time the editor gets closed.

Your changes are not lost, tubectl creates a temporary file in your temp directory which still exists after you quit. This may just get reapplied using the `-f` or `--file` option.
```
Could not create resource /tmp/.vaaFmRP.yml
```

Apply this resource again:
```
tubectl create -f /tmp/.vaaFmRP.yml
```

## Set resource name before open editor

It is certainly possible to already name the resource before open it. Just specify the
name after the resource type (or at last argument after the other required arguments if some resource types require more).
This will still open the resource and you may continue configuring it.

```
EDITOR=vi tubectl create ns foobar
```

## Specify input format

The create operation opens the editor and you may create your resource.
By default yaml is expected, this may be changed by setting the option -i or --input accordingly.
```
tubectl create ns -i json
```

## Read from file

By default tubectl opens a temporary file where you create the new resource. You may tell tubectl where to read an existing file from and skip the editor.

```
tubectl create -f resources.yaml
```

>**Note** The more advanced command [tubectl apply](apply) will also update resources if they may already exist. 

## Read from stdin

You may as well read from stdin instead creating a file manually. This is done by specifying `-s` or `--stdin` accordingly.
For example you may clone a resource like this:

```
tubectl get ns foo -o yaml | tubectl create -s
```

Open from stdin will always open the content in your editor since cloning can never be done automatically since at least the resource name
must get changed.

## Create from template

There is a possibility to open a template in the editor. Meaning you have pre defined fields so you actually know what you can configure. 
This is done by specifying `--from--template`. 

```
tubectl create ep mycollection -n mynamespace --from-template
``` 

This will open the requested resource with all configuration possibilities. Usually you only need to modify things in `data`, and the resource coordinates such as `namespace`, `collection`, `endpoint` and `name`.

```yaml
namespace: mynamespace
collection: mycollection
_links:
  self:
    href: null #<string> undefined
name: null #<string> Resource identifier. Note that the name is immutable once created on the server and must be unique in its own resource context.
id: null #<string> Unique 12-byte resource identifier. Note this is a MongoDB ObjectId. The name is the standard resource identifier, the id only useful to verify that a given resource was completely recreated. An ID is immutable and will be created on the server.
version: null #<number> The version of the resource. A version gets increased once the resource have been modified.
created: null #<string> ISO 8601 timestamp when the resource was created.
changed: null #<string> ISO 8601 timestamp when the resource was changed.
secrets: null #<array> Injected secrets in this resource.
kind: null #<string [PdoEndpoint,MysqlEndpoint,XmlEndpoint,CsvEndpoint,ImageEndpoint,JsonEndpoint,MongodbEndpoint,MoodleEndpoint,BalloonEndpoint,OdataRestEndpoint,UcsEndpoint]> The type of endpoint.
data:
  type: "browse" #<string [browse,source,destination,bidirectional]> Specify the type of the endpoint.
  options:
    identifier: null #<string> Endpoint resource identifier.
    import: null #<array> A list of attributes which gets used to uniquely identify an object on the endpoint.
    flush: false #<boolean> If true and the endpoint is of type source, the endpoint gets flushed before export. If the type is destination, the endpoints collection gets flushed before import. Pay attention with flush as it may result in data loss!
    filter_one: null #<string> Specify an endpoint filter which gets used to filter for a single object.
    filter_all: null #<string> Specify a filter which always gets applied to the endpoint if objects are retrieved.
```

You may as well set a specifc resource type as argument to `--from-template`. This may be required if you want to create a new endpoint:
```
tubectl create ep mycollection -n mynamespace --from-template MongodbEndpoint
``` 
