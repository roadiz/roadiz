<?php
declare(strict_types=1);

namespace Themes\Rozier\Models;

use JMS\Serializer\Annotation as Serializer;
use Pimple\Container;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodesSourcesDocuments;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * @package Themes\Rozier\Models
 * @Serializer\ExclusionPolicy("all")
 */
final class NodeSourceModel implements ModelInterface
{
    private NodesSources $nodeSource;
    private Container $container;

    /**
     * @param NodesSources $nodeSource
     * @param Container $container
     */
    public function __construct(NodesSources $nodeSource, Container $container)
    {
        $this->nodeSource = $nodeSource;
        $this->container = $container;
    }

    public function toArray()
    {
        /** @var UrlGenerator $urlGenerator */
        $urlGenerator = $this->container->offsetGet('urlGenerator');
        $node = $this->nodeSource->getNode();
        if (null === $node) {
            throw new \RuntimeException('Node-source does not have a Node.');
        }

        /** @var NodesSourcesDocuments|false $thumbnail */
        $thumbnail = $this->nodeSource->getDocumentsByFields()->first();

        $result = [
            'id' => $node->getId(),
            'title' => $this->nodeSource->getTitle(),
            'nodeName' => $node->getNodeName(),
            'thumbnail' => $thumbnail ? $thumbnail->getDocument() : null,
            'isPublished' => $node->isPublished(),
            'nodesEditPage' => $urlGenerator->generate('nodesEditSourcePage', [
                'nodeId' => $node->getId(),
                'translationId' => $this->nodeSource->getTranslation()->getId(),
            ]),
            'nodeType' => [
                'color' => $node->getNodeType()->getColor()
            ]
        ];

        $parent = $this->nodeSource->getParent();

        if ($parent) {
            $result['parent'] = [
                'title' => $parent->getTitle()
            ];

            if ($parent->getParent()) {
                $subparent = $parent->getParent();
                if (null !== $subparent) {
                    $result['subparent'] = [
                        'title' => $subparent->getTitle()
                    ];
                }
            }
        }

        return $result;
    }
}
