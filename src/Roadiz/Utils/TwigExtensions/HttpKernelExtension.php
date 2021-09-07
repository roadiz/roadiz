<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\TwigExtensions;

use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HttpKernelExtension extends AbstractExtension
{
    private FragmentHandler $handler;

    public function __construct(FragmentHandler $handler)
    {
        $this->handler = $handler;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('render', [$this, 'renderFragment'], ['is_safe' => ['html']]),
            new TwigFunction('render_*', [$this, 'renderFragmentStrategy'], ['is_safe' => ['html']]),
            new TwigFunction('controller', [$this, 'controller']),
        ];
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
