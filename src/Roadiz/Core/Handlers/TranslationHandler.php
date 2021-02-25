<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Translation;

/**
 * Handle operations with translations entities.
 */
class TranslationHandler extends AbstractHandler
{
    private ?Translation $translation = null;

    /**
     * @return Translation
     */
    public function getTranslation()
    {
        if (null === $this->translation) {
            throw new \BadMethodCallException('Translation is null');
        }
        return $this->translation;
    }

    /**
     * @param Translation $translation
     *
     * @return $this
     */
    public function setTranslation(Translation $translation)
    {
        $this->translation = $translation;
        return $this;
    }

    /**
     * Set current translation as default one.
     *
     * @return $this
     */
    public function makeDefault()
    {
        $defaults = $this->objectManager
            ->getRepository(Translation::class)
            ->findBy(['defaultTranslation'=>true]);

        /** @var Translation $default */
        foreach ($defaults as $default) {
            $default->setDefaultTranslation(false);
        }
        $this->objectManager->flush();
        $this->translation->setDefaultTranslation(true);
        $this->objectManager->flush();

        if ($this->objectManager instanceof EntityManagerInterface) {
            $cacheDriver = $this->objectManager->getConfiguration()->getResultCacheImpl();
            if ($cacheDriver !== null && $cacheDriver instanceof CacheProvider) {
                $cacheDriver->deleteAll();
            }
        }

        return $this;
    }
}
