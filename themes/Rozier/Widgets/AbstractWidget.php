<?php
declare(strict_types=1);

namespace Themes\Rozier\Widgets;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A widget always has to be created and called from a valid AppController
 * in order to get Twig renderer engine, security context and request context.
 */
abstract class AbstractWidget
{
    private RequestStack $requestStack;
    private ManagerRegistry $managerRegistry;
    protected ?Translation $defaultTranslation = null;

    /**
     * @param RequestStack $requestStack
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(RequestStack $requestStack, ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        $this->requestStack = $requestStack;
    }

    /**
     * @return Request
     */
    protected function getRequest(): Request
    {
        return $this->requestStack->getCurrentRequest() ?? $this->requestStack->getMasterRequest();
    }

    /**
     * @return ManagerRegistry
     */
    protected function getManagerRegistry(): ManagerRegistry
    {
        return $this->managerRegistry;
    }

    protected function getTranslation(): Translation
    {
        if (null === $this->defaultTranslation) {
            $this->defaultTranslation = $this->getManagerRegistry()
                ->getRepository(Translation::class)
                ->findOneBy(['defaultTranslation' => true]);

            if (null === $this->defaultTranslation) {
                throw new NoTranslationAvailableException();
            }
        }

        return $this->defaultTranslation;
    }
}
