<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\UrlAlias;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Repositories\NodeRepository;

/**
 * Json Serialization handler for Node.
 */
class NodeJsonSerializer extends AbstractJsonSerializer
{
    protected EntityManagerInterface $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Create a simple associative array with a Node.
     *
     * @param Node[] $nodes
     * @return array
     */
    public function toArray($nodes)
    {
        $array = [];
        $nsSerializer = new NodeSourceJsonSerializer();

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
            $data['stack_types'] = $node->getStackTypes()->map(function (NodeType $nodeType) {
                return $nodeType->getName();
            })->toArray();
            $data['archived'] = $node->isArchived();
            $data['sterile'] = $node->isSterile();
            $data['ttl'] = $node->getTtl();
            $data['children_order'] = $node->getChildrenOrder();
            $data['children_order_direction'] = $node->getChildrenOrderDirection();
            $data['position'] = $node->getPosition();
            $data['dynamic_node_name'] = $node->isDynamicNodeName();
            $data['children'] = [];
            $data['nodes_sources'] = [];
            $data['tags'] = [];

            foreach ($node->getNodeSources() as $source) {
                $data['nodes_sources'][] = $nsSerializer->toArray($source);
            }

            foreach ($node->getTags() as $tag) {
                $data['tags'][] = $tag->getTagName();
            }
            /*
             * Recursivity !! Be careful
             */
            foreach ($node->getChildren() as $child) {
                $data['children'][] = $this->toArray([$child])[0];
            }
            $array[] = $data;
        }
        return $array;
    }

    /**
     * @return bool
     */
    protected function hasHome()
    {
        /** @var NodeRepository $repository */
        $repository = $this->em->getRepository(Node::class);
        $repository->setDisplayingNotPublishedNodes(true);
        if (null !== $repository->findHomeWithDefaultTranslation()) {
            return true;
        }

        return false;
    }

    /**
     * @param array $data
     * @return Node
     * @throws EntityAlreadyExistsException
     * @throws EntityNotFoundException
     */
    protected function makeNodeRec($data)
    {
        /** @var NodeType|null $nodetype */
        $nodetype = $this->em->getRepository(NodeType::class)->findOneByName($data["node_type"]);

        /*
         * Check if node-type exists before importing nodes
         */
        if (null === $nodetype) {
            throw new EntityNotFoundException(
                'NodeType "' . $data["node_type"] . '" is not found on your website. Please import it before.'
            );
        }
        /*
         * Check if home already exists
         */
        if ($data['home'] === true && $this->hasHome()) {
            throw new EntityAlreadyExistsException(
                'Node "' . $data["node_name"] . '" cannot be imported, your website already defines a home node.'
            );
        }

        $node = new Node($nodetype);
        $node->setNodeName((string) $data['node_name']);
        $node->setHome((boolean) $data['home']);
        $node->setVisible((boolean) $data['visible']);
        $node->setStatus($data['status']);
        $node->setLocked((boolean) $data['locked']);
        $node->setPriority($data['priority']);
        $node->setHidingChildren((boolean) $data['hiding_children']);
        $node->setSterile((boolean) $data['sterile']);
        $node->setChildrenOrder($data['children_order']);
        $node->setChildrenOrderDirection($data['children_order_direction']);
        if (isset($data['position'])) {
            $node->setPosition($data['position']);
        }
        if (isset($data['dynamic_node_name'])) {
            $node->setDynamicNodeName((boolean) $data['dynamic_node_name']);
        }
        if (isset($data['ttl'])) {
            $node->setTtl((int) $data['ttl']);
        }
        if (key_exists('stack_types', $data) && is_array($data['stack_types'])) {
            foreach ($data['stack_types'] as $nodeTypeName) {
                $nodeType = $this->em->getRepository(NodeType::class)
                    ->findOneByName($nodeTypeName);
                if (null !== $nodeType) {
                    $node->addStackType($nodeType);
                }
            }
        }

        foreach ($data["nodes_sources"] as $source) {
            $trans = new Translation();
            $trans->setLocale((string) $source['translation']);
            $trans->setName(Translation::$availableLocales[$source['translation']]);
            $class = $nodetype->getSourceEntityFullQualifiedClassName();

            /** @var NodesSources $nodeSource */
            $nodeSource = new $class($node, $trans);
            $nodeSource->setTitle((string) $source["title"]);
            if (isset($source["published_at"]) && $source["published_at"] instanceof \DateTime) {
                $nodeSource->setPublishedAt($source["published_at"]);
            }
            $nodeSource->setMetaTitle((string) $source["meta_title"]);
            $nodeSource->setMetaKeywords((string) $source["meta_keywords"]);
            $nodeSource->setMetaDescription((string) $source["meta_description"]);

            $fields = $nodetype->getFields();

            /** @var NodeTypeField $field */
            foreach ($fields as $field) {
                if (!$field->isVirtual() && isset($source[$field->getName()])) {
                    if ($field->getType() == NodeTypeField::DATETIME_T || $field->getType() == NodeTypeField::DATE_T) {
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
                    $alias->setAlias((string) $url['alias']);
                    $nodeSource->addUrlAlias($alias);
                }
            }
            $node->addNodeSources($nodeSource);
        }
        if (!empty($data['tags'])) {
            foreach ($data["tags"] as $tag) {
                $tmp = $this->em->getRepository(Tag::class)->findOneBy(["tagName" => $tag]);

                if (null === $tmp) {
                    throw new EntityNotFoundException('Tag "' . $tag . '" is not found on your website. Please import it before.');
                }

                $node->getTags()->add($tmp);
            }
        }
        if (!empty($data['children'])) {
            foreach ($data['children'] as $child) {
                $tmp = $this->makeNodeRec($child);
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
     * @return Node[]
     * @throws EntityNotFoundException
     */
    public function deserialize($string)
    {
        $datas = json_decode($string, true);
        $array = [];

        foreach ($datas as $data) {
            if (!empty($data)) {
                $array[] = $this->makeNodeRec($data);
            }
        }

        return $array;
    }
}
