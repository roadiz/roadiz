<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\TwigExtensions;

use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Extension that allow render inner page part calling directly their
 * controller response instead of doing a simple include.
 */
class BlockRenderExtension extends AbstractExtension
{
    /**
     * @var FragmentHandler
     */
    protected $handler;

    /**
     * BlockRenderExtension constructor.
     *
     * @param FragmentHandler $handler
     */
    public function __construct(FragmentHandler $handler)
    {
        $this->handler = $handler;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('render', [$this, 'blockRender'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param NodesSources|null $nodeSource
     * @param string $themeName
     * @param array $assignation
     *
     * @return string
     * @throws RuntimeError
     */
    public function blockRender(NodesSources $nodeSource = null, $themeName = "DefaultTheme", $assignation = [])
    {
        if (null !== $nodeSource) {
            if (!empty($themeName)) {
                $class = $this->getNodeSourceControllerName($nodeSource, $themeName);
                if (class_exists($class) && method_exists($class, 'blockAction')) {
                    $controllerReference = new ControllerReference($class. '::blockAction', [
                        'source' => $nodeSource,
                        'assignation' => $assignation,
                    ]);
                    /*
                     * ignore_errors option MUST BE false in order to catch ForceResponseException
                     * from Master request render method and redirect users.
                     */
                    return $this->handler->render($controllerReference, 'inline', [
                        'ignore_errors' => false
                    ]);
                } else {
                    throw new RuntimeError($class . "::blockAction() action does not exist.");
                }
            } else {
                throw new RuntimeError("Invalid name formatting for your theme.");
            }
        }
        throw new RuntimeError("Invalid NodesSources.");
    }

    /**
     * @param NodesSources $nodeSource
     * @param string       $themeName
     *
     * @return string
     */
    protected function getNodeSourceControllerName(NodesSources $nodeSource, string $themeName): string
    {
        return '\\Themes\\' . $themeName . '\\Controllers\\Blocks\\' .
                $nodeSource->getNodeTypeName() . 'Controller';
    }
}
