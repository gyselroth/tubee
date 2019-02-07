# Official client development (tubectl)

Did you find a bug or would you like to contribute a feature? You are certainly welcome to do so.
Please always fill an [issue](https://github.com/gyselroth/tubee-client-cli/issues/new) first to discuss the matter.
Do not start development without an open issue otherwise we do not know what you are working on. 

>**Note** The notes for this CONTRIBUTING guide are also available in the root of the project, [here](https://github.com/gyselroth/tubee-client-cli/blob/master/CONTRIBUTING.md).

## Bug
If you just want to fill a bug report, please open your [issue](https://github.com/gyselroth/tubee-client-cli/issues/new).
We are encouraged to fix your bug to provide best software in the opensource community.

## Development

Requirements:

* nodejs (At least v8.0.0) including npm 
* nvm (Recommended)

## Get the base
```
git clone https://github.com/gyselroth/tubee-client-cli.git
```

install dependencies:
```
npm install
```

## Start tubectl in dev
```
npm start -- get ns
```

Note the `--` are important, otherwise npm interprets tubectl arguments as npm arguments. 

## Build binary
tubctl must be build on each plattform separately:

* Linux: npm run dist-linux
* Win32: npm run dist-windows
* OSX: npm run dist-osx

## Git commit 
Please make sure that you always specify the number of your issue starting with a hastag (#) within any git commits.

## Pull Request
You are absolutely welcome to submit a pull request which references an open issue. Please make sure you're follwing coding standards 
and be sure all your modifications pass the build.
[![Build Status](https://travis-ci.org/gyselroth/tubee-client-cli.svg)](https://travis-ci.org/gyselroth/tubee-client-cli)

## Code of Conduct
Please note that this project is released with a [Contributor Code of Conduct](https://github.com/gyselroth/tubee-client-cli/blob/master/CODE_OF_CONDUCT.md). By participating in this project you agree to abide by its terms.

## License
This software is freely available under the terms of [MIT](https://github.com/gyselroth/tubee-client-cli/blob/master/LICENSE), please respect this license
and do not contribute software parts which are not compatible with MIT.

## Editor config
This repository gets shipped with an .editorconfig configuration. For more information on how to configure your editor please visit [editorconfig](https://github.com/editorconfig).
