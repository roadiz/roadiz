<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\DebugBar;

use DebugBar\Bridge\DoctrineCollector;
use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DebugBar;
use DebugBar\DebugBarException;
use Pimple\Container;
use RZ\Roadiz\Utils\DebugBar\DataCollector\AccessMapCollector;
use RZ\Roadiz\Utils\DebugBar\DataCollector\AuthCollector;
use RZ\Roadiz\Utils\DebugBar\DataCollector\DispatcherCollector;
use RZ\Roadiz\Utils\DebugBar\DataCollector\LocaleCollector;
use RZ\Roadiz\Utils\DebugBar\DataCollector\ThemesCollector;
use RZ\Roadiz\Utils\DebugBar\DataCollector\VersionsCollector;

final class RoadizDebugBar extends DebugBar
{
    private Container $container;

    /**
     * @param Container $container
     * @throws DebugBarException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->addCollector($container[MessagesCollector::class]);
        $this->addCollector(new ThemesCollector($container['themeResolver'], $container['requestStack']));
        $this->addCollector(new VersionsCollector());
        $this->addCollector(new StopwatchDataCollector($container['stopwatch'], $container['twig.profile']));
        $this->addCollector(new MemoryCollector());
        $this->addCollector(new DoctrineCollector($container['doctrine.debugstack']));
        $this->addCollector(new ConfigCollector($container['config']));
        $this->addCollector(new AuthCollector($container['securityTokenStorage']));
        $this->addCollector(new DispatcherCollector($container['dispatcher']));
        $this->addCollector(new AccessMapCollector($container['accessMap'], $container['requestStack']));
        $this->addCollector(new LocaleCollector($container['requestStack']));
    }

    /**
     * Returns a JavascriptRenderer for this instance.
     *
     * @param string $baseUrl
     * @param string $basePath
     * @return JavascriptRenderer
     */
    public function getJavascriptRenderer($baseUrl = null, $basePath = null)
    {
        if ($this->jsRenderer === null) {
            $this->jsRenderer = new JavascriptRenderer($this, $baseUrl, $basePath);
        }
        return $this->jsRenderer;
    }
}
