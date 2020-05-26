<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\DebugBar\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use RZ\Roadiz\Core\Kernel;

class VersionsCollector extends DataCollector implements Renderable
{
    /**
     * @inheritDoc
     */
    public function collect()
    {
        return [
            'roadiz_version' => Kernel::CMS_VERSION . ' v' . Kernel::$cmsVersion,
            'php_version' => 'PHP '.explode('-', PHP_VERSION)[0],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'versions';
    }

    /**
     * @inheritDoc
     */
    public function getWidgets()
    {
        return [
            'current.roadiz_version' => [
                'icon' => 'roadiz',
                'map' => 'versions.roadiz_version',
                'default' => '',
            ],
            'current.php_version' => [
                'icon' => 'php',
                'map' => 'versions.php_version',
                'default' => '',
            ]
        ];
    }
}
