<?php
declare(strict_types=1);

namespace Themes\Rozier\Models;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Class NodeModel.
 *
 * @package Themes\Rozier\Models
 */
class NodeModel implements ModelInterface
{
    public static $thumbnailArray;
    /**
     * @var Node
     */
    private $node;
    /**
     * @var Container
     */
    private $container;

    /**
     * NodeModel constructor.
     * @param Node $node
     * @param Container $container
     */
    public function __construct(Node $node, Container $container)
    {
        $this->node = $node;
        $this->container = $container;
    }

    public function toArray()
    {
        /** @var UrlGenerator $urlGenerator */
        $urlGenerator = $this->container->offsetGet('urlGenerator');

        $result = [
            'id' => $this->node->getId(),
            'title' => $this->node->getNodeSources()->first()->getTitle(),
            'nodeName' => $this->node->getNodeName(),
            'isPublished' => $this->node->isPublished(),
            'nodesEditPage' => $urlGenerator->generate('nodesEditSourcePage', [
                'nodeId' => $this->node->getId(),
                'translationId' => $this->node->getNodeSources()->first()->getTranslation()->getId(),
            ]),
            'nodeType' => [
                'color' => $this->node->getNodeType()->getColor()
            ]
        ];

        $parent = $this->node->getParent();

        if ($parent) {
            $result['parent'] = [
                'title' => $parent->getNodeSources()->first()->getTitle()
            ];

            if ($parent->getParent()) {
                $subparent = $parent->getParent();
                if (null !== $subparent) {
                    $result['subparent'] = [
                        'title' => $subparent->getNodeSources()->first()->getTitle()
                    ];
                }
            }
        }

        return $result;
    }
}
