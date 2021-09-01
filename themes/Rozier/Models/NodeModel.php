<?php
declare(strict_types=1);

namespace Themes\Rozier\Models;

use JMS\Serializer\Annotation as Serializer;
use Pimple\Container;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodesSourcesDocuments;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * @package Themes\Rozier\Models
 * @Serializer\ExclusionPolicy("all")
 */
final class NodeModel implements ModelInterface
{
    private Node $node;
    private Container $container;

    /**
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
        /** @var NodesSources|false $nodeSource */
        $nodeSource = $this->node->getNodeSources()->first();
        $thumbnail = null;
        if (false !== $nodeSource) {
            /** @var NodesSourcesDocuments|false $thumbnail */
            $thumbnail = $nodeSource->getDocumentsByFields()->first();
        }

        $result = [
            'id' => $this->node->getId(),
            'title' => $nodeSource ? $nodeSource->getTitle() : $this->node->getNodeName(),
            'thumbnail' => $thumbnail ? $thumbnail->getDocument() : null,
            'nodeName' => $this->node->getNodeName(),
            'isPublished' => $this->node->isPublished(),
            'nodesEditPage' => $urlGenerator->generate('nodesEditSourcePage', [
                'nodeId' => $this->node->getId(),
                'translationId' => $nodeSource->getTranslation()->getId(),
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
