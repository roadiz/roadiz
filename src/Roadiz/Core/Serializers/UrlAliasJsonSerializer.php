<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

use RZ\Roadiz\Core\Entities\UrlAlias;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Json Serialization handler for UrlAlias.
 */
class UrlAliasJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with a UrlAlias.
     *
     * @param UrlAlias $urlAlias
     *
     * @return array
     */
    public function toArray($urlAlias)
    {
        $data = [];

        $data['alias'] = $urlAlias->getAlias();

        return $data;
    }

    /**
     * Deserializes a json file into a readable array of datas.
     *
     * @param string $jsonString
     *
     * @return UrlAlias
     * @throws \Exception
     */
    public function deserialize($jsonString)
    {
        if ($jsonString == "") {
            throw new \Exception('File is empty.');
        }
        $encoder = new JsonEncoder();
        $nameConverter = new CamelCaseToSnakeCaseNameConverter([
            'alias',
        ]);
        $normalizer = new GetSetMethodNormalizer(null, $nameConverter);

        $serializer = new Serializer([$normalizer], [$encoder]);

        return $serializer->deserialize($jsonString, UrlAlias::class, 'json');
    }
}
