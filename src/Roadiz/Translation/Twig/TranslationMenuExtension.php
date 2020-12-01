<?php
declare(strict_types=1);

namespace RZ\Roadiz\Translation\Twig;

use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Viewers\TranslationViewer;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TranslationMenuExtension extends AbstractExtension
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslationViewer
     */
    private $translationViewer;

    /**
     * @param RequestStack $requestStack
     * @param TranslationViewer $translationViewer
     */
    public function __construct(RequestStack $requestStack, TranslationViewer $translationViewer)
    {
        $this->requestStack = $requestStack;
        $this->translationViewer = $translationViewer;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('menu', [$this, 'getMenuAssignation']),
        ];
    }

    /**
     * @param Translation|null $translation
     * @param bool             $absolute
     *
     * @return array
     * @throws \Doctrine\ORM\ORMException
     */
    public function getMenuAssignation(Translation $translation = null, $absolute = false)
    {
        if (null !== $translation) {
            $this->translationViewer->setTranslation($translation);
            return $this->translationViewer->getTranslationMenuAssignation($this->requestStack->getCurrentRequest(), $absolute);
        } else {
            return [];
        }
    }
}
