<?php
declare(strict_types=1);

namespace Themes\Rozier\Widgets;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;
use Symfony\Component\HttpFoundation\Request;

/**
 * A widget always has to be created and called from a valid AppController
 * in order to get Twig renderer engine, security context and request context.
 */
abstract class AbstractWidget
{
    protected Request $request;
    private EntityManagerInterface $entityManager;
    protected ?Translation $defaultTranslation = null;

    /**
     * @return Request
     */
    protected function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(Request $request, EntityManagerInterface $entityManager)
    {
        $this->request = $request;
        $this->entityManager = $entityManager;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    protected function getTranslation(): Translation
    {
        if (null === $this->defaultTranslation) {
            $this->defaultTranslation = $this->getEntityManager()
                ->getRepository(Translation::class)
                ->findOneBy(['defaultTranslation' => true]);

            if (null === $this->defaultTranslation) {
                throw new NoTranslationAvailableException();
            }
        }

        return $this->defaultTranslation;
    }
}
