# tubectl config

The tubectl config may be requested using `tubectl get config`. A typical config resource looks like this:
```yaml
context:
  - username: admin
    url: 'https://localhost:8090'
    allowSelfSigned: true
    name: dev
    defaultNamespace: test
  - username: raffis
    url: 'https://tubeestage'
    allowSelfSigned: false
    name: stage
kind: Config
defaultContext: stage
```

## Edit config
The configuration may be modified using `tubectl edit config`.

>**Note** tubectl login will also modify the configuration in a more user friendly way.

## Context

You may use different tubee environments easily with tubectl by using different contexts.
Using `tubectl login` will create a new context named `default`.
As long as you do not specify a different context using `-c` or `--context` accordingly the context `default` gets used.

If you would like to specify a new context just set a different context during `tubectl login`:

```
tubectl --context production login -s https://tubee-prod -u admin -p admin
```

This will create a new context named `production`.
You may specify the context for every request, for example:

```
tubectl --context production get ps
```

## Default context
The default context is usually named `default`. You may change the default context in the tubectl config:

```
tubectl edit config
```

and set `defaultContext` to another context name.

## Configuring context

A context may have different settings, usually how tubectl can connect to a tubee server.

| Field      | Type | Description  |
| ------------- | ----- |--------------|
| username | `string` | The username using to authenticate. |
| url | `string` | tubee server URL.  |
| allowSelfSigned | `boolean` | If true tubectl may accept self signed ssl certificates.  |
| name | `string` | The name of the tubectl context.  |
| defaultNamespace | `string` | Specify a different default namespace other than `default`.  |

>**Note** There is no way to configure a secret here. Secrets are stored in the operating systems credential vault and may only be changed/added using `tubectl login`.
