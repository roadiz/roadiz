<?php
/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file NodeSerializer.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */

namespace RZ\Renzo\Core\Serializers;

use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Serializers\EntitySerializer;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Json Serialization handler for Node.
 */
class NodeJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with a Node.
     *
     * @param RZ\Renzo\Core\Entities\Node $node
     *
     * @return array
     */
    public static function toArray($node)
    {
        $data = array();

        $data['node_name'] =                $node->getNodeName();
        $data['node_type'] =                $node->getNodeType()->getName();
        $data['home'] =                     $node->isHome();
        $data['visible'] =                  $node->isVisible();
        $data['published'] =                $node->isPublished();
        $data['locked'] =                   $node->isLocked();
        $data['priority'] =                 $node->getPriority();
        $data['hiding_children'] =          $node->isHidingChildren();
        $data['archived'] =                 $node->isArchived();
        $data['sterile'] =                  $node->isSterile();
        $data['children_order'] =           $node->getChildrenOrder();
        $data['children_order_direction'] = $node->getChildrenOrderDirection();

        $data['children'] =  array();
        $data['nodes_sources'] = array();

        foreach ($node->getNodeSources() as $source) {
            $data['nodes_sources'][] = NodeSourceJsonSerializer::toArray($source);
        }

        /*
         * Recursivity !! Be careful
         */
        foreach ($node->getChildren() as $child) {
            $data['children'][] = static::toArray($child);
        }

        return $data;
    }

    /**
     * Deserializes a Json into readable datas.
     *
     * @param string $string
     *
     * @return RZ\Renzo\Core\Entities\Node
     */
    public static function deserialize($string)
    {
        $encoder = new JsonEncoder();
        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setCamelizedAttributes(array(
            'node_name',
            'home',
            'visible',
            'published',
            'locked',
            'priority',
            'hiding_children',
            'archived',
            'sterile',
            'children_order',
            'children_order_direction'
        ));

        $serializer = new Serializer(array($normalizer), array($encoder));
        $node = $serializer->deserialize($string, 'RZ\Renzo\Core\Entities\Node', 'json');

        return $node;
    }
}
