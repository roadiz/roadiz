<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file TranslationRepository.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NoResultException;
use RZ\Roadiz\Core\Entities\Node;

/**
 * {@inheritdoc}
 */
class TranslationRepository extends EntityRepository
{
    /**
     * Get single default translation.
     *
     * @return \RZ\Roadiz\Core\Entities\Translation
     */
    public function findDefault()
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Roadiz\Core\Entities\Translation t
            WHERE t.defaultTranslation = true
            AND t.available = true
        ');

        $query->useResultCache(true, 1800, 'RZTranslationDefault');

        try {
            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * Get all available translations.
     *
     * @return ArrayCollection
     */
    public function findAllAvailable()
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Roadiz\Core\Entities\Translation t
            WHERE t.available = true
        ');

        $query->useResultCache(true, 1800, 'RZTranslationAllAvailable');

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $locale
     *
     * @return boolean
     */
    public function exists($locale)
    {
        $query = $this->_em->createQuery('
            SELECT COUNT(t.locale) FROM RZ\Roadiz\Core\Entities\Translation t
            WHERE t.locale = :locale
        ')->setParameter('locale', $locale);

        $query->useResultCache(true, 60, 'RZTranslationExists-' . $locale);

        try {
            return (boolean) $query->getSingleScalarResult();
        } catch (NoResultException $e) {
            return false;
        }
    }

    /**
     * Get all available locales.
     *
     * @return array
     */
    public function getAvailableLocales()
    {
        $query = $this->_em->createQuery('
        SELECT t.locale FROM RZ\Roadiz\Core\Entities\Translation t
        WHERE t.available = true');

        $query->useResultCache(true, 60, 'RZTranslationGetAvailableLocales');

        try {
            return array_map('current', $query->getScalarResult());
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * Get all available locales.
     *
     * @return array
     */
    public function getAvailableOverrideLocales()
    {
        $query = $this->_em->createQuery("
        SELECT t.overrideLocale FROM RZ\Roadiz\Core\Entities\Translation t
        WHERE t.available = true
        AND t.overrideLocale IS NOT NULL
        AND t.overrideLocale <> ''");

        $query->useResultCache(true, 60, 'RZTranslationGetAvailableOverrideLocales');

        try {
            return array_map('current', $query->getScalarResult());
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * Get all available translations by locale.
     *
     * @param $locale
     *
     * @return ArrayCollection
     */
    public function findByLocaleAndAvailable($locale)
    {
        $query = $this->_em->createQuery('
        SELECT t FROM RZ\Roadiz\Core\Entities\Translation t
        WHERE t.available = true
        AND t.locale = :locale
        ')->setParameter('locale', $locale);

        $query->useResultCache(true, 60, 'RZTranslationAllByLocaleAndAvailable-' . $locale);

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * Get all available translations by overrideLocale.
     *
     * @param $overrideLocale
     *
     * @return ArrayCollection
     */
    public function findByOverrideLocaleAndAvailable($overrideLocale)
    {
        $query = $this->_em->createQuery('
        SELECT t FROM RZ\Roadiz\Core\Entities\Translation t
        WHERE t.available = true
        AND t.overrideLocale = :overrideLocale
        ')->setParameter('overrideLocale', $overrideLocale);

        $query->useResultCache(true, 60, 'RZTranslationAllByOverrideAndAvailable-' . $overrideLocale);

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * Get one available translation by locale.
     *
     * @param $locale
     *
     * @return \RZ\Roadiz\Core\Entities\Translation
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByLocaleAndAvailable($locale)
    {
        $query = $this->_em->createQuery('
        SELECT t FROM RZ\Roadiz\Core\Entities\Translation t
        WHERE t.available = true
        AND t.locale = :locale
        ')->setParameter('locale', $locale)
        ->setMaxResults(1);

        $query->useResultCache(true, 60, 'RZTranslationOneByLocaleAndAvailable-' . $locale);

        try {
            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * Get one available translation by overrideLocale.
     *
     * @param $overrideLocale
     *
     * @return \RZ\Roadiz\Core\Entities\Translation
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByOverrideLocaleAndAvailable($overrideLocale)
    {
        $query = $this->_em->createQuery('
        SELECT t FROM RZ\Roadiz\Core\Entities\Translation t
        WHERE t.available = true
        AND t.overrideLocale = :overrideLocale
        ')->setParameter('overrideLocale', $overrideLocale)
        ->setMaxResults(1);

        $query->useResultCache(true, 60, 'RZTranslationOneByOverrideAndAvailable-' . $overrideLocale);

        try {
            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }
}
