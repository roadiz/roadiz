<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\UrlGenerators;

use Doctrine\Common\Cache\CacheProvider;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @package RZ\Roadiz\Utils\UrlGenerators
 */
final class DocumentUrlGenerator extends AbstractDocumentUrlGenerator
{
    private UrlGeneratorInterface $urlGenerator;

    /**
     * @param Packages              $packages
     * @param UrlGeneratorInterface $urlGenerator
     * @param CacheProvider|null    $optionsCacheProvider
     */
    public function __construct(
        Packages $packages,
        UrlGeneratorInterface $urlGenerator,
        ?CacheProvider $optionsCacheProvider = null
    ) {
        parent::__construct($packages, null, [], $optionsCacheProvider);
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return string
     */
    protected function getRouteName(): string
    {
        return 'interventionRequestProcess';
    }

    protected function getProcessedDocumentUrlByArray(bool $absolute = false): string
    {
        if (null === $this->getDocument()) {
            throw new \InvalidArgumentException('Cannot get URL from a NULL document');
        }

        $referenceType = $absolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;

        $routeParams = [
            'queryString' => $this->optionCompiler->compile($this->options),
            'filename' => $this->getDocument()->getRelativePath(),
        ];

        return $this->urlGenerator->generate(
            $this->getRouteName(),
            $routeParams,
            $referenceType
        );
    }
}
