# tubee

[![Build Status](https://travis-ci.org/gyselroth/tubee.svg)](https://travis-ci.org/gyselroth/tubee)
 [![GitHub license](https://img.shields.io/badge/license-GPL3-blue.svg)](https://raw.githubusercontent.com/gyselroth/tubee/master/LICENSE)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gyselroth/tubee/badges/quality-score.png)](https://scrutinizer-ci.com/g/gyselroth/tubee)
[![Code Coverage](https://scrutinizer-ci.com/g/gyselroth/tubee/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/gyselroth/tubee/?branch=master)
[![GitHub release](https://img.shields.io/github/release/gyselroth/tubee.svg)](https://github.com/gyselroth/tubee/releases)
[ ![Download](https://api.bintray.com/packages/gyselroth/tubee/tubee/images/download.svg)](https://bintray.com/gyselroth/tubee/tubee/_latestVersion) 

tubee is a I/O data management engine with proxy capabilities for other services and also features I/O synchronization support for those.
Create, Modify and Delete records in namespaced data collections. Import and export records from and to different technologies like databases, files, http services and more.
Access endpoint records via the same system (data proxy) and much more.

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
* Storage drivers for data formats:
    * LocalFilesystem
    * balloon cloud server
    * SMB (Windows/Samba share via smb)
    * Stream (HTTP,FTP and more)

## Changelog
A changelog is available [here](https://github.com/gyselroth/tubee/CHANGELOG.md).

## Contribute
We are glad that you would like to contribute to this project. Please follow the given [terms](https://github.com/gyselroth/tubee/blob/master/CONTRIBUTING.md).
