<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

/**
 * Json Serialization handler for NodeSource.
 */
class TagTranslationJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with a NodeSource.
     *
     * @param mixed $tt
     *
     * @return array
     */
    public function toArray($tt)
    {
        $data = [];

        $data['translation'] = $tt->getTranslation()->getLocale();
        $data['title'] = $tt->getname();
        $data['description'] = $tt->getDescription();

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function deserialize($string)
    {
        throw new \RuntimeException(
            "Cannot simply deserialize a TagTranslation entity. ",
            1
        );
    }
}
