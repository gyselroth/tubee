# tubee.io

[![Build Status](https://travis-ci.org/gyselroth/tubee.svg)](https://travis-ci.org/gyselroth/tubee)
[![GitHub release](https://img.shields.io/github/release/gyselroth/tubee.svg)](https://github.com/gyselroth/tubee/releases)
[ ![Download](https://api.bintray.com/packages/gyselroth/tubee/tubee/images/download.svg) ](https://bintray.com/gyselroth/tubee/tubee/_latestVersion) 
 [![GitHub license](https://img.shields.io/badge/license-GPL-blue.svg)](https://raw.githubusercontent.com/gyselroth/tubee/master/LICENSE)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gyselroth/tubee/badges/quality-score.png)](https://scrutinizer-ci.com/g/gyselroth/tubee)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fgyselroth%2Ftubee.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2Fgyselroth%2Ftubee?ref=badge_shield)

## Description 

tubee.io is a data management engine which supports import and export from and to various other systems.
It comes with a full featured console interface and also provides a rest api.

It can manage multiple data streams which get through manually defined workflows and attribute maps.
Data can also be inserted manually via console or the rest api.

## Features

* Endpoints
    * LDAP
    * SQL Databases via ODBC (PDO, All relational SQL database engines)
    * Native MySQL/MariaDB
    * MongoDB
    * Moodle 
    * balloon
    * XML (via different storage backends, see Storage drivers)
    * CSV (via different storage backends, see Storage drivers)
    * JSON (via different storage backends, see Storage drivers)
    * Images (via different storage backends, see Storage drivers)
* Storage drivers for file based endpoints
    * LocalFilesystem
    * balloon cloud server
    * SMB (Windows/Samba share via smb)

## Changelog
A changelog is available [here](https://github.com/gyselroth/tubee/CHANGELOG.md).

## Contribute
We are glad that you would like to contribute to this project. Please follow the given [terms](https://github.com/gyselroth/tubee/blob/master/CONTRIBUTING.md).
