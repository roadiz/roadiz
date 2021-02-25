<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\DebugBar\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\AccessMap;

final class AccessMapCollector extends DataCollector implements Renderable
{
    private AccessMap $accessMap;
    private RequestStack $requestStack;

    /**
     * @param AccessMap $accessMap
     * @param RequestStack $requestStack
     */
    public function __construct(AccessMap $accessMap, RequestStack $requestStack)
    {
        $this->accessMap = $accessMap;
        $this->requestStack = $requestStack;
    }

    /**
     * @inheritDoc
     */
    public function collect()
    {
        list($role, $channel) = $this->accessMap->getPatterns($this->requestStack->getMasterRequest());
        return [
            'map' => [
                'roles' => $role,
                'channel' => $channel,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'access_map';
    }

    /**
     * @inheritDoc
     */
    public function getWidgets()
    {
        $widgets = [
            'access_map' => [
                'icon' => 'lock',
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'access_map.map',
                'default' => '{}'
            ]
        ];

        return $widgets;
    }
}
