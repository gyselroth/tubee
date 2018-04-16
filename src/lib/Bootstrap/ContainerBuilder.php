<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Bootstrap;

use Composer\Autoload\ClassLoader as Composer;
use Micro\Container\Container;
use Noodlehaus\Config;

class ContainerBuilder
{
    /**
     * Init bootstrap.
     *
     * @param Composer $composer
     */
    public static function get(Composer $composer)
    {
        $config = self::loadConfig();
        $container = new Container($config);
        $container->add(get_class($composer), $composer);

        return $container;
    }

    /**
     * Load config.
     *
     * @return Config
     */
    protected static function loadConfig(): Config
    {
        $configs = [constant('TUBEE_PATH').DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'.container.config.php'];
        foreach (glob(constant('TUBEE_CONFIG_DIR').DIRECTORY_SEPARATOR.'*.yaml') as $path) {
            $configs[] = $path;
        }

        return new Config($configs);
    }
}
