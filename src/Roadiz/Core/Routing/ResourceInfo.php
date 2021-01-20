<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\Translation;

/**
 * @package src\Roadiz\Core\Routing
 */
final class ResourceInfo
{
    protected ?AbstractEntity $resource;
    protected ?Translation $translation;
    protected string $_format;
    protected string $_locale;

    /**
     * @return AbstractEntity|null
     */
    public function getResource(): ?AbstractEntity
    {
        return $this->resource;
    }

    /**
     * @param AbstractEntity|null $resource
     * @return ResourceInfo
     */
    public function setResource(?AbstractEntity $resource): ResourceInfo
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return Translation|null
     */
    public function getTranslation(): ?Translation
    {
        return $this->translation;
    }

    /**
     * @param Translation|null $translation
     * @return ResourceInfo
     */
    public function setTranslation(?Translation $translation): ResourceInfo
    {
        $this->translation = $translation;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->_format;
    }

    /**
     * @param string $format
     * @return ResourceInfo
     */
    public function setFormat(string $format): ResourceInfo
    {
        $this->_format = $format;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->_locale;
    }

    /**
     * @param string $locale
     * @return ResourceInfo
     */
    public function setLocale(string $locale): ResourceInfo
    {
        $this->_locale = $locale;
        return $this;
    }
}
