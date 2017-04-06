<?php
/**
 * Created by PhpStorm.
 * User: adrien
 * Date: 28/03/2017
 * Time: 19:38
 */

namespace Themes\Rozier\Models;


use Pimple\Container;
use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\Routing\Generator\UrlGenerator;

class NodeModel
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
            'isPusblished' => $this->node->isPublished(),
            'nodesEditPage' => $urlGenerator->generate('nodesEditPage', [
                'nodeId' => $this->node->getId()
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

                $result['subparent'] = [
                    'title' => $subparent->getNodeSources()->first()->getTitle()
                ];
            }
        }

        return $result;
    }
}
