<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
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
 * @file BlockRenderExtension.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\TwigExtensions;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\NodesSources;

/**
 * Extension that allow render inner page part calling directly their
 * controller response instead of doing a simple include.
 */
class BlockRenderExtension extends \Twig_Extension
{
    protected $container;
    protected $kernel;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'blockRenderExtension';
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('render', array($this, 'blockRender'), ['is_safe' => ['html']]),
        );
    }

    /**
     * @param NodesSources $nodeSource
     * @param string $themeName
     * @param array $assignation
     *
     * @return string
     * @throws \Twig_Error_Runtime
     */
    public function blockRender(NodesSources $nodeSource = null, $themeName = "DefaultTheme", $assignation = [])
    {
        if (null !== $nodeSource) {
            if (!empty($themeName)) {
                $class = '\\Themes\\' . $themeName .
                '\\Controllers\\Blocks\\' .
                $nodeSource->getNode()->getNodeType()->getName() .
                'Controller';
                if (class_exists($class) &&
                    method_exists($class, 'blockAction')) {
                    $ctrl = new $class();
                    $ctrl->setContainer($this->container);
                    $ctrl->__init();

                    $response = $ctrl->blockAction(
                        $this->container['request'],
                        $nodeSource,
                        $assignation
                    );

                    return $response->getContent();
                } else {
                    throw new \Twig_Error_Runtime($class . "::blockAction() action does not exist.");
                }
            } else {
                throw new \Twig_Error_Runtime("Invalid name formatting for your theme.");
            }
        }
        throw new \Twig_Error_Runtime("Invalid NodesSources.");
    }
}
