<?php
declare(strict_types=1);

namespace Themes\Rozier\Models;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Class NodeModel.
 *
 * @package Themes\Rozier\Models
 */
class NodeSourceModel implements ModelInterface
{
    public static $thumbnailArray;
    /**
     * @var NodesSources
     */
    private $nodeSource;
    /**
     * @var Container
     */
    private $container;

    /**
     * NodeModel constructor.
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
        $result = [
            'id' => $node->getId(),
            'title' => $this->nodeSource->getTitle(),
            'nodeName' => $node->getNodeName(),
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
