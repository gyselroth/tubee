# Quick start

You need two things to get started, a server (usually you want a hosted server) and tubectl. 
tubectl is the tubee console client for Linux, Windows and OS X.

## Get tubectl

All releases and executeable binaries are available tubee [@github](https://github.com/gyselroth/tubee-client-cli/releases).
Besides installing the binary manually, you may also install it from a package repository.

### Linux 

For debian based users you may just the tubee apt repository and install the latest client from there:

```sh
apt-get -y install apt-transport-https
echo "deb https://dl.bintray.com/gyselroth/tubee stable main" | sudo tee -a /etc/apt/sources.list
wget -qO - https://bintray.com/user/downloadSubjectPublicKey?username=gyselroth | sudo apt-key add -
sudo apt-get update && apt-get install tubectl
```

### OSX

As OSX user you may install tubectl using brew:

```sh
brew tap gyselroth/core
brew install tubectl
```

## tubectl first steps

At first you have tell tubectl what tubee server you want to communicate to and you must provide your authentication credentials:

```sh
tubectl login -u raffis -P -s https://tubee.example
```

>**Note** tubectl login stores your password in your operating systems credentials vault. You may be asked to unlock it.

As of tubectl v1.0.0 it is only possible to authenticate using http basic credentials eventough the tubee server also offers OpenID-connect.
By default self signed ssl certificated are not accepted by tubectl, however you may change this behaviour by setting the option `-a` or `--allow-self-signed` accordingly.

Lets start our first request and query the available namespaces:

```sh
tubectl get ns
```

This lists all namespace resources in a pretty table.
For requesting resources you need to use `get`, to edit resources `edit` and of course to create new ones `create`. You may just execute tubectl without any options to get the available commands:

```sh
tubectl
  Usage:  [options] [command]

  Options:

    -c, --config <file>  Specify the config for the client (If different than ~/.tubee/config)


  Commands:

    login                      Login resources
    get                        Get resources
    edit                       Edit resources
    explain                    Describe a resource
    delete                     Delete resources
    create                     Create resources
    sync                       Sync resources
```

>**Note** tubecl loads its config (.yml) from the users home directory (~/.tubee/config) and only holds configuration data like the tubee server. You may specify a custom configuration by specifying `-c path/to/config`.

## Help & explain resources

Using help explains you what certain commands do, like:

```
tubectl help login
```

A very useful command is also explain. Using explain describes entire resource types.
For example you might want to know what PdoEndpoint is and what can be configured:

```
tubectl explain PdoEndpoint
```

## Playground
How does it all work and looks like? Want to play around with? You may want to import a playground with defines various resources and possibilities.

This will create a namespace `playground` with example data. This may also be useful for new developers to test resources.

```
tubectl apply -f https://raw.githubusercontent.com/gyselroth/tubee/dev/example/dump.yaml
```
