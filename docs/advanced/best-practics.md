# Best practics

## Using secrets
[Secrets](resources/secrets.md) should be used to secure any sensitive information like access tokens, passwords or certificates.

## Naming conventions

### Collection plural
Collection resources should be named in plural. For example `accounts` and not `account` since a collection usually holds multiple objects.

## Endpoints
An endpoint should always be named the same in each collection. 

For example:
There is a collection `accounts` with a destination endpoint `openldap-mydomain`. There is also a collection named `groups` which also exports
data to the same OpenLDAP server. This endpoint should also be named `openldap-mydomain`.
This is possibile since an endpoint name must only be unique for each namespace/collection pair.

## Workflow performance
It is a good choice to put workflows first which are executed most likely first. Meaning setting the priority to `1` and if there are other worflows give them
a lower priority. That way there is no need to execute multiple workflows to determine which one will match an applied.
