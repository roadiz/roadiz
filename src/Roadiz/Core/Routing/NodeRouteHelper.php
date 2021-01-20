<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use RZ\Roadiz\CMS\Controllers\DefaultController;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use RZ\Roadiz\Utils\StringHandler;

class NodeRouteHelper
{
    private Node $node;

    private ?Theme $theme;
    /**
     * @var class-string|null
     */
    private ?string $controller = null;

    private PreviewResolverInterface $previewResolver;
    /**
     * @var class-string
     */
    private string $defaultControllerClass;

    /**
     * @param Node $node
     * @param Theme|null $theme
     * @param PreviewResolverInterface $previewResolver
     * @param class-string $defaultControllerClass
     */
    public function __construct(
        Node $node,
        ?Theme $theme,
        PreviewResolverInterface $previewResolver,
        string $defaultControllerClass = DefaultController::class
    ) {
        $this->node = $node;
        $this->theme = $theme;
        $this->previewResolver = $previewResolver;
        $this->defaultControllerClass = $defaultControllerClass;
    }

    /**
     * Get controller class path for a given node.
     *
     * @return string
     * @throws \ReflectionException
     */
    public function getController(): string
    {
        if (null === $this->controller) {
            if (null !== $this->theme) {
                $refl = new \ReflectionClass($this->theme->getClassName());
                $namespace = $refl->getNamespaceName() . '\\Controllers';

                $this->controller = $namespace . '\\' .
                    StringHandler::classify($this->node->getNodeType()->getName()) .
                    'Controller';

                /*
                 * Use a default controller if no controller was found in Theme.
                 */
                if (!class_exists($this->controller) && $this->node->getNodeType()->isReachable()) {
                    $this->controller = $this->defaultControllerClass;
                }
            } else {
                $this->controller = $this->defaultControllerClass;
            }
        }

        return $this->controller;
    }

    public function getMethod(): string
    {
        return 'indexAction';
    }

    /**
     * Return FALSE or TRUE if node is viewable.
     *
     * @return boolean
     * @throws \ReflectionException
     */
    public function isViewable(): bool
    {
        if (!class_exists($this->getController())) {
            return false;
        }
        if (!method_exists($this->getController(), $this->getMethod())) {
            return false;
        }
        /*
         * For archived and deleted nodes
         */
        if ($this->node->getStatus() > Node::PUBLISHED) {
            /*
             * Not allowed to see deleted and archived nodes
             * even for Admins
             */
            return false;
        }

        /*
         * For unpublished nodes
         */
        if ($this->node->getStatus() < Node::PUBLISHED) {
            if (true === $this->previewResolver->isPreview()) {
                return true;
            }
            /*
             * Not allowed to see unpublished nodes
             */
            return false;
        }

        /*
         * Everyone can view published nodes.
         */
        return true;
    }
}
