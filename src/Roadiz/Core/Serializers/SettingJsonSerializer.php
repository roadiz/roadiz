<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

use RZ\Roadiz\Core\Entities\Setting;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Serialization class for Setting.
 * @deprecated Use Serializer service.
 */
class SettingJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with Setting
     * entity.
     *
     * @param Setting $setting
     *
     * @return array
     */
    public function toArray($setting)
    {
        $data = [];

        $data['name'] = $setting->getName();
        $data['value'] = $setting->getValue();
        $data['type'] = $setting->getType();
        $data['visible'] = $setting->isVisible();
        $data['default_values'] = $setting->getDefaultValues();
        $data['description'] = $setting->getDescription();

        return $data;
    }

    /**
     * Deserializes a json file into a readable array of datas.
     *
     * @param string $jsonString
     *
     * @return Setting
     * @throws \Exception
     */
    public function deserialize($jsonString)
    {
        if ($jsonString == "") {
            throw new \Exception('File is empty.');
        }
        $encoder = new JsonEncoder();
        $nameConverter = new CamelCaseToSnakeCaseNameConverter([
            'name',
            'value',
            'type',
            'visible',
            'defaultValues',
            'description',
        ]);
        $normalizer = new GetSetMethodNormalizer(null, $nameConverter);
        $serializer = new Serializer([$normalizer], [$encoder]);

        return $serializer->deserialize($jsonString, Setting::class, 'json');
    }
}
