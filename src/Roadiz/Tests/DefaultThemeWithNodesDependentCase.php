<?php
declare(strict_types=1);

namespace RZ\Roadiz\Tests;

use Doctrine\ORM\Tools\ToolsException;

abstract class DefaultThemeWithNodesDependentCase extends DefaultThemeDependentCase
{
    /**
     * @throws ToolsException
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::runCommand('themes:install --nodes "/Themes/DefaultTheme/DefaultThemeApp"');
    }
}
