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

        $result = [
            'id' => $this->nodeSource->getNode()->getId(),
            'title' => $this->nodeSource->getTitle(),
            'nodeName' => $this->nodeSource->getNode()->getNodeName(),
            'isPublished' => $this->nodeSource->getNode()->isPublished(),
            'nodesEditPage' => $urlGenerator->generate('nodesEditSourcePage', [
                'nodeId' => $this->nodeSource->getNode()->getId(),
                'translationId' => $this->nodeSource->getTranslation()->getId(),
            ]),
            'nodeType' => [
                'color' => $this->nodeSource->getNode()->getNodeType()->getColor()
            ]
        ];

        $parent = $this->nodeSource->getParent();

        if ($parent) {
            $result['parent'] = [
                'title' => $parent->getTitle()
            ];

            if ($parent->getParent()) {
                $subparent = $parent->getParent();

                $result['subparent'] = [
                    'title' => $subparent->getTitle()
                ];
            }
        }

        return $result;
    }
}
