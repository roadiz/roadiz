<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

use RuntimeException;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Json Serialization handler for NodeSource.
 */
class NodeSourceJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with a NodeSource.
     *
     * @param NodesSources $nodeSource
     *
     * @return array
     */
    public function toArray($nodeSource)
    {
        $urlAliasSerializer = new UrlAliasJsonSerializer();
        $data = [];

        $data['translation'] = $nodeSource->getTranslation()->getLocale();
        $data['title'] = $nodeSource->getTitle();
        $data['meta_title'] = $nodeSource->getMetaTitle();
        $data['meta_keywords'] = $nodeSource->getMetaKeywords();
        $data['meta_description'] = $nodeSource->getMetaDescription();
        $data['published_at'] = $nodeSource->getPublishedAt();

        $data = array_merge($data, $this->getSourceFields($nodeSource));

        $data['url_aliases'] = [];

        foreach ($nodeSource->getUrlAliases() as $alias) {
            $data['url_aliases'][] = $urlAliasSerializer->toArray($alias);
        }

        return $data;
    }

    /**
     * @param NodesSources $nodeSource
     *
     * @return array
     */
    protected function getSourceFields($nodeSource)
    {
        $fields = $nodeSource->getNode()->getNodeType()->getFields();

        /*
         * Create nodeSource default values
         */
        $sourceDefaults = [];
        foreach ($fields as $field) {
            if (!$field->isVirtual()) {
                $getter = $field->getGetterName();
                $sourceDefaults[$field->getName()] = $nodeSource->$getter();
            }
        }

        return $sourceDefaults;
    }

    /**
     * {@inheritDoc}
     */
    public function deserialize($string)
    {
        throw new RuntimeException(
            "Cannot simply deserialize a NodesSources entity. " .
            "Use 'deserializeWithNodeType' method instead.",
            1
        );
    }

    /**
     * @param string   $string
     * @param NodeType $type
     *
     * @return NodesSources
     */
    public function deserializeWithNodeType($string, NodeType $type)
    {
        $fields = $type->getFields();
        /*
         * Create source default values
         */
        $sourceDefaults = [
            "title",
            "meta_title",
            "meta_keywords",
            "meta_description",
            "published_at",
        ];

        foreach ($fields as $field) {
            if (!$field->isVirtual()) {
                $sourceDefaults[] = $field->getName();
            }
        }

        $encoder = new JsonEncoder();
        $nameConverter = new CamelCaseToSnakeCaseNameConverter($sourceDefaults);
        $normalizer = new GetSetMethodNormalizer(null, $nameConverter);

        $serializer = new Serializer([$normalizer], [$encoder]);

        /** @var NodesSources $nodeSource */
        $nodeSource = $serializer->deserialize(
            $string,
            NodeType::getGeneratedEntitiesNamespace() . '\\' . $type->getSourceEntityClassName(),
            'json'
        );

        return $nodeSource;
    }
}
