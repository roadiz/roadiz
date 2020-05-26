<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use RZ\Roadiz\Utils\StringHandler;

/**
 * Route handling methods.
 */
class RouteHandler
{
    public static function getBaseRoute($path): string
    {
        if (StringHandler::endsWith($path, "Locale")) {
            $path = StringHandler::replaceLast("Locale", "", $path);
        }
        return $path;
    }
}
