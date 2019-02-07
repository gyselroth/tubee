# Deploy Server

This is a step-by-step tutorial how to correctly deploy the tubee server.

There are multiple supported ways to deploy tubee:

* Docker (docker-compose)
* Container orchestration plattform like [Kubernetes](https://kubernetes.io/docs/concepts/overview/what-is-kubernetes/))
* Classic way as deb package via apt
* Manually as tar archive
* Compile manually from source

The docker deployment using docker-compose or a container orchestration platform like kubernetes is the **recommended** way to deploy tubee. And it is also the simplest way.
Deploy tubee using debian packages, tar archives or even installing from source requires some advanced system knowledge.

## Debian based distribution

Both the server and the web ui get distributed as .deb packages to make it easy to install and upgrade.

**Requirements**:

* Debian based linux distribution

You need a running debian based linux distribution. This can be [debian](https://www.debian.org) itself or debian based distribution like [Ubuntu](https://www.ubuntu.com). You may also convert the package using `alien` to rpm and other package formats. 
If you are not sure how to deploy such a server please visit the documentation of those distributions as this is out of the scope of this documentation.

This tutorial describes how to install all tubee components on the same server. In production environments this may not be the best way and it is neither scalable nor performant. You certainly can deploy all components on different server. The tubee server is fully scalable and can be scaled horizontally as well as can the required components like MongoDB. Everything is easlily scaleable to your needs.

### Package Repository

You need to add the tubee repository to your package management configuration as well as repositories for the latest PHP and MongoDB releases.
The following commands must be executed with `root` permissions unless noted otherwise.

#### tubee
```sh
apt-get -y install apt-transport-https
echo "deb https://dl.bintray.com/gyselroth/tubee stable main" | sudo tee -a /etc/apt/sources.list
wget -qO - https://bintray.com/user/downloadSubjectPublicKey?username=gyselroth | sudo apt-key add -
sudo apt-get update
```

>**Note** If you want to install beta and alpha versions replace `stable` with `unstable`. Pre-releases are only ment for testing purposes and are in no way recommended in production environements!

>**Note** This repository also includes the shell client `tubectl`.

#### PHP
The tubee server requires PHP 7.2. If your current distribution does not provide 7.2 out of their stable archives (which is most certainly the case) please add the PPA ppa:ondrej/php which will provide the latest PHP 7.2 releases.

```sh
sudo apt-get install lsb-release ca-certificates
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list
sudo apt-get update
```

#### MongoDB
tubee uses MongoDB as its main database. At least MongoDB 3.4 is required. If your current distribution does not ship at least this release you will need to add the official MongoDB repository.

>**Note** MongoDB recommends to use the official MongoDB repository anyway since the releases in the debian and or ubuntu repositories are not maintained by them and lack newer minor releases.

```sh
sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 2930ADAE8CAF5059EE73BB4B58712A2291FA4AD5
echo "deb http://repo.mongodb.org/apt/debian jessie/mongodb-org/3.6 main" | sudo tee /etc/apt/sources.list.d/mongodb-org-3.6.list
sudo apt-get update
``` 

>**Note** This will add the repository for debian jessie, if you need another repository please refer to the [MongoDB installation](https://docs.mongodb.com/manual/administration/install-on-linux/) docs.

### Install tubee
Now tubee and its components can be installed.
```
apt-get install mongodb-org tubee
```

## Docker (docker-compose)

The easiest, fastest and recommended way to deploy a tubee environment is to spin it up using docker and docker-compose.
Since the installation is not the same for different host os and docker can be started on Linux, Windows and Mac please visit 
the docker documentation on how to install [docker](https://docs.docker.com/install) and [docker-compose](https://docs.docker.com/compose/install).

Now a docker-compose file is required with all required containers by tubee.
Create a file named `tubee-stable.yaml` with this content:

**Requirements**:
* docker
* docker-compose


```yaml
mongodb:
    image: mongo:3.6.0
    entrypoint: [ "/usr/bin/mongod", "--bind_ip_all", "--replSet", "rs0" ]
postfix:
    image: webuni/postfix
tubee:
    image: gyselroth/tubee:latest
    links:
        - mongodb
        - postfix
    entrypoint: service nginx start && php-fpm
    environment:
        - TUBEE_MONGODB_URI=mongodb://mongodb:27017
        - TUBEE_SMTP_HOST=postfix
tubee-jobs:
    image: gyselroth/tubee:latest
    links:
        - mongodb
        - postfix
    entrypoint: tubeecli jobs
    environment:
        - TUBEE_MONGODB_URI=mongodb://mongodb:27017
        - TUBEE_SMTP_HOST=postfix
```

The tubee server can now be started using:
```
docker-compose -f tubee-stable.yaml up
```

You need to initialize tubee once (You do not need to execute this everytime you start the server via docker-compose, it is just a one time call):
```
docker exec tubee-stable-tubee_1 tubeecli upgrade -i -vvv
```

>**Note** All tubee containers provide a version tag besides `latest`. It is best practice to use an exact version of a service instead the latest tag in production environment.
The containers provide a `latest-unstable` tag for the tubee-jobs, tubee and tubee-web container. It is in no way reccomened to use pre-releases in production environments! 

>**Note** If you want to install beta and alpha versions replace `latest` with `latest-unstable` or specify an exact version tag. Pre-releases are only ment for testing purposes and are in no way recommended in production environements!

## Deploy on kubernetes
tubee runs awesome on kubernetes (And is developed with cloud native in mind).

**Requirements**:

* Kubernetes cluster
* kubectl
* git
* Persistent storage provider

tubee itself does not require any persistent storage, but MongoDB does. It is recommended to deploy a [MongoDB replset](https://docs.mongodb.com/manual/tutorial/deploy-replica-set/) using a kubernetes [statefulset](https://kubernetes.io/docs/concepts/workloads/controllers/statefulset/).

Tubee comes with working kubernetes resources:
```
git clone https://github.com/gyselroth/tubee/tree/master/packaging/kubernetes tubee-kube
cd tubee-kube
kubectl apply -f .
```

>**Note** This will create a new namespace tubee. The MongoDB replset gets made using [mongo-k8s-sidecar](https://github.com/cvallance/mongo-k8s-sidecar).

If you have no possibility to deploy persistent volumes, you may also just deploy tubee on kubernetes and use another MongoDB instance elsewhere.

## Using the tar archive
Instead a deb package you may also use a tar archive and install tubee manually on your system. 
A tar archive is an already builded relase, you you just need to have all requirements installed on your system, you may have a look at [Manually install from source](#manually-install-from-source).

## Manually install from source

This topic is only for advanced users or developers and describes how to deploy tubee by installing from source.
If you are a developer please also continue reading [this](https://github.com/gyselroth/tubee/blob/master/CONTRIBUTING.md) article.

**Requirements**:

* posix based operating system (Basically every linux/unix)
* make
* [comoser](https://getcomposer.org/download/)
* git
* php >= 7.2
* php ext-mongodb
* php ext-curl
* php ext-mbstring
* php ext-posix
* php ext-pnctl
* php ext-apcu
* php ext-sysvmsg

**Optional requirements**:

* php ext-imagick (If you want to use The ImageEndpoint)
* php ext-ldap (If you want to use LDAP authentication and/or the LdapEndpoint)
* php ext-smb (If you want to use the SmbStorage)
* php ext-xml (If you want to use the XmlEndpoint)
* php ext-pdo (If you want to use the PdoEndpoint)
* php ext-mysql (If you want ot use the MysqlEndpoint)

This will only install the tubee server. Dependencies such as MongoDB do not get installed.
You can install those dependencies either by using distributed packages, see [Debian based distribution](#debian-based-distribution) or by installing them seperately from source.

### Install tubee server
```sh
git clone https://github.com/gyselroth/tubee.git
cd tubee
make install
```

>**Note** You can also create .deb or .tar packages using make. Just execute either `make deb` or `make tar` or `make dist` for both.
