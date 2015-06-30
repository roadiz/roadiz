<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodeJsonSerializer.php
 * @author Thomas Aufresne
 */
namespace RZ\Roadiz\Core\Serializers;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\UrlAlias;
use RZ\Roadiz\Core\Kernel;

/**
 * Json Serialization handler for Node.
 */
class NodeJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with a Node.
     *
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return array
     */
    public static function toArray($nodes)
    {
        $array = [];

        foreach ($nodes as $node) {
            $data = [];

            $data['node_name'] = $node->getNodeName();
            $data['node_type'] = $node->getNodeType()->getName();
            $data['home'] = $node->isHome();
            $data['visible'] = $node->isVisible();
            $data['status'] = $node->getStatus();
            $data['locked'] = $node->isLocked();
            $data['priority'] = $node->getPriority();
            $data['hiding_children'] = $node->isHidingChildren();
            $data['archived'] = $node->isArchived();
            $data['sterile'] = $node->isSterile();
            $data['children_order'] = $node->getChildrenOrder();
            $data['children_order_direction'] = $node->getChildrenOrderDirection();

            $data['children'] = [];
            $data['nodes_sources'] = [];
            $data['tags'] = [];

            foreach ($node->getNodeSources() as $source) {
                $data['nodes_sources'][] = NodeSourceJsonSerializer::toArray($source);
            }

            foreach ($node->getTags() as $tag) {
                $data['tags'][] = $tag->getTagName();
            }
            /*
             * Recursivity !! Be careful
             */
            foreach ($node->getChildren() as $child) {
                $data['children'][] = static::toArray([$child])[0];
            }
            $array[] = $data;
        }
        return $array;
    }

    protected static function makeNodeRec($data)
    {
        $nodetype = Kernel::getInstance()->getService('em')
                                         ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
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
            $class = $namespace . "\\" . $classname;

            $nodeSource = new $class($node, $trans);
            $nodeSource->setTitle($source["title"]);
            $nodeSource->setMetaTitle($source["meta_title"]);
            $nodeSource->setMetaKeywords($source["meta_keywords"]);
            $nodeSource->setMetaDescription($source["meta_description"]);

            $fields = $nodetype->getFields();

            foreach ($fields as $field) {
                if (!$field->isVirtual()) {
                    if ($field->getType() == NodeTypeField::DATETIME_T
                        || $field->getType() == NodeTypeField::DATE_T) {
                        $date = new \DateTime(
                            $source[$field->getName()]['date'],
                            new \DateTimeZone($source[$field->getName()]['timezone'])
                        );
                        $setter = $field->getSetterName();
                        $nodeSource->$setter($date);
                    } else {
                        $setter = $field->getSetterName();
                        $nodeSource->$setter($source[$field->getName()]);
                    }
                }
            }
            if (!empty($source['url_aliases'])) {
                foreach ($source['url_aliases'] as $url) {
                    $alias = new UrlAlias($nodeSource);
                    $alias->setAlias($url['alias']);
                    $nodeSource->addUrlAlias($alias);
                }
            }
            $node->getNodeSources()->add($nodeSource);
        }
        if (!empty($data['tags'])) {
            foreach ($data["tags"] as $tag) {
                $tmp = Kernel::getInstance()->getService('em')
                                            ->getRepository('RZ\Roadiz\Core\Entities\Tag')
                                            ->findOneBy(["tagName" => $tag]);
                $node->getTags()->add($tmp);
            }
        }
        if (!empty($data['children'])) {
            foreach ($data['children'] as $child) {
                $tmp = static::makeNodeRec($child);
                $node->addChild($tmp);
            }
        }
        return $node;
    }

    /**
     * Deserializes a Json into readable datas.
     *
     * @param string $string
     *
     * @return RZ\Roadiz\Core\Entities\Node
     */
    public static function deserialize($string)
    {
        $datas = json_decode($string, true);
        $array = [];

        foreach ($datas as $data) {
            if (!empty($data)) {
                $array[] = static::makeNodeRec($data);
            }
        }

        return $array;
    }
}
