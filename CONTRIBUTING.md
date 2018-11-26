# Contribute to tubee
Did you find a bug or would you like to contribute a feature? You are certainly welcome to do so.
Please always fill an [issue](https://github.com/gyselroth/tubee/issues/new) first to discuss the matter.
Do not start development without an open issue otherwise we do not know what you are working on. 

## Bug
If you just want to fill a bug report, please open your [issue](https://github.com/gyselroth/tubee/issues/new).
We are encouraged to fix your bug to provide best software in the opensource community.

## Security flaw
Do not open an issue for a possible security vulnerability, to protect yourself and others please contact <opensource@gyselroth.net>
to report your concern.

### Get the base
```
git clone https://github.com/gyselroth/tubee.git
```

### Development
The recomended way to get started in development is to use the available docker images.
You need (docker)[https://docs.docker.com/engine/installation/linux/docker-ce/debian/] and (docker-compose)[https://docs.docker.com/compose/install/] installed on your local machine.

For starters you can use the full stack development composing configuration `docker-compose-dev.yml`.
Start the development stack `docker-compose -c docker-compose-dev.yml up` and you are ready to go.

>**Note:** You need the entire git base on your host to run the dev server since the base gets mounted in the dev container.

Your tubee server is now available at `https://localhost:8090`.
>**Note:** The dev server gets started with a self-signed ssl certificate.


#### Make
Always execute make via `docker exec` if your are developing with the tubee docker image.

Update depenencies:
```
docker exec INSTANCE make -C /srv/www/tubee deps
```
(You do not need to install dependencies manually, the dev container automatically installs all depencies during start)

See Building bellow for other make targets.

## Building
Besides npm scripts like build and start you can use make to build this software. The following make targets are supported:

* `build` Build software, but do not package
* `clean` Clear build and dependencies
* `deb` Create debian packages
* `deps` Install dependencies
* `dist` Distribute (Create tar and deb packages)
* `tar` Create tar package
* `test` Execute testsuite
* `phpcs` Execute phpcs check
* `phpstan` Execute phpstan

## Docs
Documentation is written in /docs and generated with [mkdocs](https://www.mkdocs.org).

## Git commit 
Please make sure that you always specify the number of your issue starting with a hastag (#) within any git commits.

## Pull Request
You are absolutely welcome to submit a pull request which references an open issue. Please make sure you're follwing coding standards 
and be sure all your modifications pass the build.
[![Build Status](https://travis-ci.org/gyselroth/tubee.svg?branch=dev)](https://travis-ci.org/gyselroth/tubee)

## Code of Conduct
Please note that this project is released with a [Contributor Code of Conduct](https://github.com/gyselroth/tubee/CODE_OF_CONDUCT.md). By participating in this project you agree to abide by its terms.

## License
This software is freely available under the terms of [GPL-3.0](https://github.com/gyselroth/tubee/LICENSE), please respect this license
and do not contribute software parts which are not compatible with GPL-3.0.

## Editor config
This repository gets shipped with an .editorconfig configuration. For more information on how to configure your editor please visit [editorconfig](https://github.com/editorconfig).

## Git pre commit hook
Add the following lines to your git pre-commit hook file, otherwise your build will fail if you do not following code style:
Note that you will need to install swagger-markdown on your host to compile the OpenAPI spec into markdown doc.

```
swagger-markdown -i src/lib/Rest/v1/swagger.yml -o docs/11-api.md
./vendor/bin/php-cs-fixer fix --config=.php_cs.dist -v
```

This automatically converts your code into the code style guidelines of this project otherwise your build will fail!

Install swagger-markdown:
```
npm install -g swagger-markdown
```
