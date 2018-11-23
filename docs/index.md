# About tubee

## What ist tubee?
tubee is a I/O data management engine with proxy capabilities for other services and features I/O synchronization support for those.
Create, Modify and Delete records in namespaced data collections. Import and export records from and to different technologies like databases, files, http services and more.
Access endpoint records via the same system (proxy) and much more.

## Licensing
This software is freely available under the terms of [GPL-3.0](https://github.com/gyselroth/tubee/LICENSE) including this documenation.

## Contribute
There are many ways to contribute. Also just reporting issues and features requests will help to make the software better!
Please continue reading in the [Contributing chapter](https://github.com/gyselroth/tubee/blob/master/CONTRIBUTING.md).

## Changelog
A changelog is available [here](https://github.com/gyselroth/tubee/blob/master/CHANGELOG.md).

{% for page in site.pages %}
{% include_relative page.url %}
{% endfor %}
