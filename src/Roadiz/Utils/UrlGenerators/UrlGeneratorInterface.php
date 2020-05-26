<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\UrlGenerators;

interface UrlGeneratorInterface
{
    /**
     * Get a resource Url.
     *
     * @param bool $absolute Use Url with domain name [default: false]
     * @return string
     */
    public function getUrl(bool $absolute = false): string;
}
