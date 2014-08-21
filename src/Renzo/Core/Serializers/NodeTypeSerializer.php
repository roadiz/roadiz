<?php 

namespace RZ\Renzo\Core\Serializers;

use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Serializers\EntitySerializer;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class NodeTypeSerializer implements EntitySerializer {

    protected $nodeType;

    function __construct(NodeType $nodeType) {
        $this->nodeType = $nodeType;
    }

    /**
     * Serializes data into Json.
     * @return string         
     */
    public function serializeToJson() {
        $data = array();

        $data['name'] = $this->getNodeType()->getName();
        $data['displayName'] = $this->getNodeType()->getDisplayName();
        $data['description'] = $this->getNodeType()->getDescription();
        $data['visible'] = $this->getNodeType()->isVisible();
        $data['newsletterType'] = $this->getNodeType()->isNewsletterType();
        $data['hidingNodes'] = $this->getNodeType()->isHidingNodes();
        $data['fields'] = array();

        foreach ($this->getNodeType()->getFields() as $nodeTypeField) {
            $data['node_type_fields'][] = $nodeTypeField->getHandler()->serialize();
        }

        if (defined(JSON_PRETTY_PRINT)) {
            return json_encode($data, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        else {
            return json_encode($data, JSON_NUMERIC_CHECK);
        }
    }

    /**
     * Deserializes a Json into readable datas
     * @param  string  $jsonString
     * @return RZ\Renzo\Core\Entities\NodeType
     */
    public static function deserializeFromJson( $jsonString ) {
        $encoder = new JsonEncoder();
        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setCamelizedAttributes(array(
            'name',
            'displayName',
            'display_name',
            'description',
            'visible',
            'newsletterType',
            'hidingNodes'
        ));

        $serializer = new Serializer(array($normalizer), array($encoder));
        $nodeType = $serializer->deserialize($jsonString, 'RZ\Renzo\Core\Entities\NodeType', 'json');

        /*
         * Importing Fields
         */
        return $nodeType;
    }

    /**
     * Update an existing Node Type.
     * @param RZ\Renzo\Core\Entities\NodeType
     * @return bool
     */
    public function updateFromJson(NodeType $importedNT) {
        return null;
    }
}