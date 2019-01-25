# Secrets

Secrets contain sensitive information and may get injected into other resources.
A secret may hold anything but is usually a password, digital certificate or access tokens.
Secret resources get special treatment on the server. Their contents get encryted on the server and decrypted on request.
Besides encryption, a secret is useful to not accidentally leak secret information while sharing resource definitions.

## Create Secret

A secret holds its data in `data` and can be any key:value combination.
The secret value must be encoded using base64. This is due a secret may also contain binary data.

```
echo -n foobar200 | base64
echo -n cn=admin,dc=example,dc=org | base64
```

>**Note** Using echo `n` is required otherwise echo will append a new line \n and invalidates the secret.

```yaml 
name: ldap-credentials
kind: Secret
namespace: playground
data:
  binddn: Y249YWRtaW4sZGM9ZXhhbXBsZSxkYz1vcmc=
  bindpw: Zm9vYmFyMjAw
```

```sh
tubectl create -f spec.yaml
```

Check the just created resource:

```sh
tubectl get secrets ldap-credentials -n playground -o yaml
```

## Inject secret

A secret may be injected into any other resource using the field `secrets`. In this example lets inject the secret to 
an ldap endpoint:

```yaml
name: tam-ldap
kind: LdapEndpoint
namespace: playground
collection: accounts
data:
  type: destination
  resource:
    uri: ldap://openldap-endpoint
    basedn: ou=users,dc=example,dc=org
  options:
    filter_one: '{uid={map.uid}}'
    filter_all: '(objectClass=PosixAccount)'
secrets:
- secret: ldap-credentials
  key: binddn
  to: 'data.resource.binddn'
- secret: ldap-credentials
  key: bindpw
  to: 'data.resource.bindpw'
```


## Injection options:

| Key      | Description  |
| ------------- |--------------|
| secret | The name of the secret to mount. |
| key | Specify the name of the key/value pair from the secret.  |
| to | The place where the secrets value should get injected. `.` may be used to delimit a path. |

>**Note** If the same path already exists in the resource definition itself, it will be overruled by the secret value.
