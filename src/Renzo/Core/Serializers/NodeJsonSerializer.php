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
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodesSources;
use RZ\Renzo\Core\Entities\Translation;
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
        $data['status'] =                   $node->getStatus();
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

    private static function makeNodeRec($data) {
        $nodetype = Kernel::getInstance()->getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\NodeType')
                    ->findOneByName($data["node_type"]);

        $node = new Node($nodetype);
        $node->setNodeName($data['node_name']);
        $node->setHome($data['home']);
        $node->setVisible($data['visible']);
        $node->setStatus($data['status']);
        $node->setLocked($data['locked']);
        $node->setPriority($data['priority']);
        $node->setHidingChildren($data['hiding_children']);
        $node->setArchived($data['archived']);
        $node->setSterile($data['sterile']);
        $node->setChildrenOrder($data['children_order']);
        $node->setChildrenOrderDirection($data['children_order_direction']);

        foreach ($data["nodes_sources"] as $source) {
            $trans = new Translation();
            $trans->setLocale($source['translation']);
            $trans->setName(Translation::$availableLocales[$source['translation']]);

            $namespace = NodeType::getGeneratedEntitiesNamespace();
            $classname = $nodetype->getSourceEntityClassName();
            $class = $namespace."\\".$classname;

            $nodeSource = new $class($node, $trans);
            $nodeSource->setTitle($source["title"]);
            $nodeSource->setMetaTitle($source["meta_title"]);
            $nodeSource->setMetaKeywords($source["meta_keywords"]);
            $nodeSource->setMetaDescription($source["meta_description"]);

            $fields = $nodetype->getFields();

            foreach ($fields as $field) {
                if (!$field->isVirtual()) {
                    $setter = $field->getSetterName();
                    $nodeSource->$setter($source[$field->getName()]);
                }
            }

            foreach ($source['url_aliases'] as $url) {
                $alias = new UrlAlias();
                $alias->setAlias($url['alias']);
                $nodeSource->addUrlAlias($alias);
            }
            $node->getNodeSources()->add($nodeSource);
        }
        foreach ($data['children'] as $child) {
            $tmp = static::makeNodeRec($child);
            $node->addChild($tmp);
        }
        return $node;
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
        $data = json_decode($string, true);

        return static::makeNodeRec($data);
    }
}
