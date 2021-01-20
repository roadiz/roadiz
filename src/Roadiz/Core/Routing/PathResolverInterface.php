<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

interface PathResolverInterface
{
    /**
     * Resolve a ResourceInfo containing AbstractEntity, format and translation using a unique path.
     *
     * @param string $path
     * @param array<string> $supportedFormatExtensions
     * @return ResourceInfo
     */
    public function resolvePath(string $path, array $supportedFormatExtensions = ['html']): ResourceInfo;
}
