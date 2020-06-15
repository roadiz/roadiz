<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\UrlGenerators;

use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class DocumentUrlGenerator
 *
 * @package RZ\Roadiz\Utils\UrlGenerators
 */
class DocumentUrlGenerator extends AbstractDocumentUrlGenerator
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * DocumentUrlGenerator constructor.
     *
     * @param Packages               $packages
     * @param UrlGeneratorInterface  $urlGenerator
     */
    public function __construct(
        Packages $packages,
        UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct($packages);
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return string
     */
    protected function getRouteName()
    {
        return 'interventionRequestProcess';
    }

    protected function getProcessedDocumentUrlByArray(bool $absolute = false): string
    {
        if (null === $this->getDocument()) {
            throw new \InvalidArgumentException('Cannot get URL from a NULL document');
        }

        $referenceType = $absolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;
        $compiler = new OptionsCompiler();

        $routeParams = [
            'queryString' => $compiler->compile($this->options),
            'filename' => $this->getDocument()->getRelativePath(),
        ];

        return $this->urlGenerator->generate(
            $this->getRouteName(),
            $routeParams,
            $referenceType
        );
    }
}
