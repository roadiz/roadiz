<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Controllers;

use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Controller abstract class for global CMS util actions.
 */
abstract class CmsController extends AppController
{
    /**
     * {@inheritdoc}
     */
    public static function getThemeFolder(): string
    {
        $class_info = new \ReflectionClass(get_called_class());
        return realpath(dirname($class_info->getFileName()) . '/../');
    }

    /**
     * {@inheritdoc}
     */
    public static function getRoutes(): RouteCollection
    {
        $locator = static::getFileLocator();
        $loader = new YamlFileLoader($locator);
        return $loader->load('routes.yml');
    }
}
