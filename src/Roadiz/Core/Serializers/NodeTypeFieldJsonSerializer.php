<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Serialization class for NodeTypeField.
 * @deprecated Use Serializer service.
 */
class NodeTypeFieldJsonSerializer extends AbstractJsonSerializer
{

    /**
     * Create a simple associative array with NodeTypeField
     * entity.
     *
     * @param NodeTypeField $nodeTypeField
     *
     * @return array
     * @deprecated Use Serializer service.
     */
    public function toArray($nodeTypeField)
    {
        $data = [];

        $data['name'] = $nodeTypeField->getName();
        $data['label'] = $nodeTypeField->getLabel();
        $data['description'] = $nodeTypeField->getDescription();
        $data['visible'] = $nodeTypeField->isVisible();
        $data['type'] = $nodeTypeField->getType();
        $data['indexed'] = $nodeTypeField->isIndexed();
        $data['virtual'] = $nodeTypeField->isVirtual();
        $data['universal'] = $nodeTypeField->isUniversal();
        $data['default_values'] = $nodeTypeField->getDefaultValues();
        $data['group_name'] = $nodeTypeField->getGroupName();
        $data['group_name_canonical'] = $nodeTypeField->getGroupNameCanonical();
        $data['expanded'] = $nodeTypeField->isExpanded();
        $data['placeholder'] = $nodeTypeField->getPlaceholder();
        $data['min_length'] = $nodeTypeField->getMinLength();
        $data['max_length'] = $nodeTypeField->getMaxLength();

        return $data;
    }

    /**
     * Deserializes a json file into a readable array of datas.
     *
     * @param string $jsonString
     *
     * @return NodeTypeField
     * @deprecated Use Serializer service.
     */
    public function deserialize($jsonString)
    {
        $encoder = new JsonEncoder();

        $nameConverter = new CamelCaseToSnakeCaseNameConverter([
            'name',
            'label',
            'description',
            'visible',
            'type',
            'indexed',
            'virtual',
            'universal',
            'defaultValues',
            'groupName',
            'groupNameCanonical',
            'expanded',
            'placeholder',
            'minLength',
            'maxLength'
        ]);
        $normalizer = new GetSetMethodNormalizer(null, $nameConverter);
        $serializer = new Serializer([$normalizer], [$encoder]);
        return $serializer->deserialize($jsonString, NodeTypeField::class, 'json');
    }
}
