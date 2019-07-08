<?php
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file ThemesCollector.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\DebugBar\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use RZ\Roadiz\Core\HttpFoundation\Request;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ThemesCollector extends DataCollector implements Renderable
{
    /**
     * @var RequestStack
     */
    protected $requestStack;
    /**
     * @var ThemeResolverInterface
     */
    private $themeResolver;

    /**
     * ThemesCollector constructor.
     *
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
