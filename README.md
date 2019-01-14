# tubee

[![Build Status](https://travis-ci.org/gyselroth/tubee.svg)](https://travis-ci.org/gyselroth/tubee)
 [![GitHub license](https://img.shields.io/badge/license-GPL3-blue.svg)](https://raw.githubusercontent.com/gyselroth/tubee/master/LICENSE)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gyselroth/tubee/badges/quality-score.png)](https://scrutinizer-ci.com/g/gyselroth/tubee)
[![Code Coverage](https://scrutinizer-ci.com/g/gyselroth/tubee/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/gyselroth/tubee/?branch=master)
[![GitHub release](https://img.shields.io/github/release/gyselroth/tubee.svg)](https://github.com/gyselroth/tubee/releases)
[ ![Download](https://api.bintray.com/packages/gyselroth/tubee/tubee/images/download.svg)](https://bintray.com/gyselroth/tubee/tubee/_latestVersion) 
[![Documentation Status](https://readthedocs.org/projects/tubee/badge/?version=latest)](https://tubee.readthedocs.io/en/latest/?badge=latest)

tubee is a data management engine with proxy capabilities for other services and its core feature is the possibility to synchronize data between multiple services (endpoints) such as databases, ldap server, file formats, web applications and more. Everything can be fully automated using tubee. You may specify different synchronization workflows and defined custom attribute mappings. Create scripted attributes, conditions, synchronization jobs and more. tubee can be used to automatically synchronize your objecs between multiple endpoints. This can be everything in its nature, for example synchronize user accounts from an XML file to Active Directory and MongoDB. Do whatever you have to do.

## Features

* Namespace support
* Supports Import/Export to and from various different technologies
* Resource versioning
* Full asynchronous sync jobs
* Time triggered sync jobs
* RBAC
* Proxy for supported endpoints (Access endpoints via the tubee layer)
* Query rewriting for different endpoints (Query data from endpoints with the same query language)
* Attribute mapping between tubee and endpoints
* Attribtue scripting, rewriting and more
* Attribute map workflows
* Full featured OpenAPI v2 REST API
* SDK's for 3rt party software
* Published as debian package, tar archive and docker image
* Full support for a cloud native deployment like on Kubernetes
* Perfectly scalable for your needs
* Console client for Linux, Windows and OSX

## Endpoints
* Endpoints
    * LDAP (OpenLDAP, ActiveDirectory and other LDAP server)
    * Various SQL Databases (PDO, All relational SQL database engines)
    * Native MySQL/MariaDB
    * MongoDB
    * Moodle 
    * balloon
    * ODataRest (Like Microsoft online (Office365 and more))
    * XML (via different storage backends, see Storage drivers)
    * CSV (via different storage backends, see Storage drivers)
    * JSON (via different storage backends, see Storage drivers)
    * Images (via different storage backends, see Storage drivers)
    * Ucs (Univention Corporate Server)
* Storage drivers for data formats:
    * LocalFilesystem
    * balloon cloud server
    * SMB (Windows/Samba share via smb)
    * Stream (HTTP,FTP and more)

## Documentation
Visit [https://tubee.readthedocs.io](https://tubee.readthedocs.io) to get started!

## Changelog
A changelog is available [here](https://github.com/gyselroth/tubee/CHANGELOG.md).

## Contribute
We are glad that you would like to contribute to this project. Please follow the given [terms](https://github.com/gyselroth/tubee/blob/master/CONTRIBUTING.md).
