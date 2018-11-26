#!/usr/bin/env php
<?php
/**
 * Tubee.
 *
 * @copyright copryright (c) 2017 gyselroth GmbH
 */
define('TUBEE_PATH', (getenv('TUBEE_PATH') ? getenv('TUBEE_PATH') : realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..')));

define('TUBEE_CONFIG_DIR', (getenv('TUBEE_CONFIG_DIR') ? getenv('TUBEE_CONFIG_DIR') : constant('TUBEE_PATH').DIRECTORY_SEPARATOR.'config'));
!getenv('TUBEE_CONFIG_DIR') ? putenv('TUBEE_CONFIG_DIR='.constant('TUBEE_CONFIG_DIR')) : null;

define('TUBEE_LOG_DIR', (getenv('TUBEE_LOG_DIR') ? getenv('TUBEE_LOG_DIR') : constant('TUBEE_PATH').DIRECTORY_SEPARATOR.'log'));
!getenv('TUBEE_LOG_DIR') ? putenv('TUBEE_LOG_DIR='.constant('TUBEE_LOG_DIR')) : null;

set_include_path(implode(PATH_SEPARATOR, [
    constant('TUBEE_PATH').DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'lib',
    constant('TUBEE_PATH').DIRECTORY_SEPARATOR,
    get_include_path(),
]));

$composer = require 'vendor/autoload.php';
$dic = Tubee\Bootstrap\ContainerBuilder::get($composer);
$dic->get(Tubee\Bootstrap\Cli::class)->process();
