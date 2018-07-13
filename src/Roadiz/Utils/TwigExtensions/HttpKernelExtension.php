<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
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
 * @file HttpKernelExtension.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\TwigExtensions;

use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HttpKernelExtension extends AbstractExtension
{
    /**
     * @var FragmentHandler
     */
    private $handler;

    public function __construct(FragmentHandler $handler)
    {
        $this->handler = $handler;
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('render', [$this, 'renderFragment'], array('is_safe' => array('html'))),
            new TwigFunction('render_*', [$this, 'renderFragmentStrategy'], array('is_safe' => array('html'))),
            new TwigFunction('controller', [$this, 'controller']),
        );
    }
    /**
     * Renders a fragment.
     *
     * @param string|ControllerReference $uri     A URI as a string or a ControllerReference instance
     * @param array                      $options An array of options
     *
     * @return string The fragment content
     *
     * @see FragmentHandler::render()
     */
    public function renderFragment($uri, $options = [])
    {
        $strategy = isset($options['strategy']) ? $options['strategy'] : 'inline';
        unset($options['strategy']);
        return $this->handler->render($uri, $strategy, $options);
    }
    /**
     * Renders a fragment.
     *
     * @param string                     $strategy A strategy name
     * @param string|ControllerReference $uri      A URI as a string or a ControllerReference instance
     * @param array                      $options  An array of options
     *
     * @return string The fragment content
     *
     * @see FragmentHandler::render()
     */
    public function renderFragmentStrategy($strategy, $uri, $options = [])
    {
        return $this->handler->render($uri, $strategy, $options);
    }

    public function controller($controller, $attributes = [], $query = [])
    {
        return new ControllerReference($controller, $attributes, $query);
    }
}
