<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\DebugBar\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use RZ\Roadiz\Core\HttpFoundation\Request;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class ThemesCollector extends DataCollector implements Renderable
{
    private RequestStack $requestStack;
    private ThemeResolverInterface $themeResolver;

    /**
     * @param ThemeResolverInterface $themeResolver
     * @param RequestStack           $requestStack
     */
    public function __construct(ThemeResolverInterface $themeResolver, RequestStack $requestStack)
    {
        $this->themeResolver = $themeResolver;
        $this->requestStack = $requestStack;
    }

    /**
     * @inheritDoc
     */
    public function collect()
    {
        $request = $this->requestStack->getMasterRequest();
        $data = [
            'list' => []
        ];
        if ($request instanceof Request && null !== $request->getTheme()) {
            $themeClassReflection = new \ReflectionClass($request->getTheme()->getClassName());
            $path = explode('\\', $themeClassReflection->getName());
            $data['current'] = $path[count($path)-1];
        }
        foreach ($this->themeResolver->findAll() as $theme) {
            $themeClassReflection = new \ReflectionClass($theme->getClassName());
            $priority = $themeClassReflection->getStaticPropertyValue('priority');
            $path = explode('\\', $themeClassReflection->getName());
            $classData = [
                'name' => $themeClassReflection->getName(),
                'extends' => '(' .$themeClassReflection->getParentClass()->getName() . ')',
                'priority' => '['.$priority.']',
            ];
            $data['list'][$path[count($path)-1]] = implode(' ', $classData);
        }

        $data['nb_themes'] = count($data['list']);

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'themes';
    }

    /**
     * @inheritDoc
     */
    public function getWidgets()
    {
        return [
            "themes" => [
                "icon" => "th-large",
                "widget" => "PhpDebugBar.Widgets.KVListWidget",
                "map" => "themes.list",
                "default" => "[]"
            ],
            "themes:badge" => [
                "map" => "themes.nb_themes",
                "default" => 0
            ],
            'current.themes' => [
                'icon' => 'th-large',
                'map' => 'themes.current',
                'default' => '',
            ]
        ];
    }
}
