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
and you may correct the error. If you do not change anything and quit a seccond time the editor gets closed.

>**Note**: Your changes are not lost, tubectl creates a temporary file in your temp directory which still exists after you quit. This may just get reapplied using the `-f` or `--file` option.

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
tubectl create co mynamespace mycollection --from-template
``` 

You may as well set a specifc resource type as argument to `--from-template`. This may be required if you want to create a new endpoint:
```
tubectl create ep mynamespace mycollection --from-template MongodbEndpoint
``` 
