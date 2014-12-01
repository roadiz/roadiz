<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file TranslationRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use RZ\Roadiz\Core\Entities\Translation;

/**
 * {@inheritdoc}
 */
class TranslationRepository extends EntityRepository
{
    /**
     * Get single default translation.
     *
     * @return Translation
     */
    public function findDefault()
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Roadiz\Core\Entities\Translation t
            WHERE t.defaultTranslation = true
            AND t.available = true
        ');

        $query->useResultCache(true, 60, 'RZTranslationDefault');

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
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

        $query->useResultCache(true, 60, 'RZTranslationAllAvailable');

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
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

        try {
            return (boolean) $query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return false;
        }
    }
}
