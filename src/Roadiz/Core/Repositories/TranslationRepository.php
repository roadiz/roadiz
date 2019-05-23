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

use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;

/**
 * {@inheritdoc}
 */
class TranslationRepository extends EntityRepository
{
    /**
     * Get single default translation.
     *
     * @return Translation|null
     */
    public function findDefault()
    {
        $qb = $this->createQueryBuilder('t');
        $qb->andWhere($qb->expr()->eq('t.available', ':available'))
            ->andWhere($qb->expr()->eq('t.defaultTranslation', ':defaultTranslation'))
            ->setParameter(':available', true)
            ->setParameter(':defaultTranslation', true)
            ->setMaxResults(1)
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query->useResultCache(true, 1800, 'RZTranslationDefault');

        return $query->getOneOrNullResult();
    }

    /**
     * Get all available translations.
     *
     * @return Translation[]
     */
    public function findAllAvailable()
    {
        $qb = $this->createQueryBuilder('t');
        $qb->andWhere($qb->expr()->eq('t.available', ':available'))
            ->setParameter(':available', true)
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query->useResultCache(true, 1800, 'RZTranslationAllAvailable');

        return $query->getResult();
    }

    /**
     * @param string $locale
     *
     * @return boolean
     */
    public function exists($locale)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select($qb->expr()->countDistinct('t.locale'))
            ->andWhere($qb->expr()->eq('t.locale', ':locale'))
            ->setParameter(':locale', $locale)
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query->useResultCache(true, 60, 'RZTranslationExists-' . $locale);

        return (boolean) $query->getSingleScalarResult();
    }

    /**
     * Get all available locales.
     *
     * @return array
     */
    public function getAvailableLocales()
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t.locale')
            ->andWhere($qb->expr()->eq('t.available', ':available'))
            ->setParameter(':available', true)
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query->useResultCache(true, 60, 'RZTranslationGetAvailableLocales');

        return array_map('current', $query->getScalarResult());
    }

    /**
     * Get all locales.
     *
     * @return array
     */
    public function getAllLocales()
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t.locale')
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query->useResultCache(true, 60, 'RZTranslationGetAllLocales');

        return array_map('current', $query->getScalarResult());
    }

    /**
     * Get all available locales.
     *
     * @return array
     */
    public function getAvailableOverrideLocales()
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t.overrideLocale')
            ->andWhere($qb->expr()->isNotNull('t.overrideLocale'))
            ->andWhere($qb->expr()->neq('t.overrideLocale', ':overrideLocale'))
            ->andWhere($qb->expr()->eq('t.available', ':available'))
            ->setParameter(':available', true)
            ->setParameter(':overrideLocale', '')
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query->useResultCache(true, 60, 'RZTranslationGetAvailableOverrideLocales');

        return array_map('current', $query->getScalarResult());
    }

    /**
     * Get all available locales.
     *
     * @return array
     */
    public function getAllOverrideLocales()
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t.overrideLocale')
            ->andWhere($qb->expr()->isNotNull('t.overrideLocale'))
            ->andWhere($qb->expr()->neq('t.overrideLocale', ':overrideLocale'))
            ->setParameter(':overrideLocale', '')
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query->useResultCache(true, 60, 'RZTranslationGetAllOverrideLocales');

        return array_map('current', $query->getScalarResult());
    }

    /**
     * Get all available translations by locale.
     *
     * @param $locale
     *
     * @return Translation[]
     */
    public function findByLocaleAndAvailable($locale)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->andWhere($qb->expr()->eq(static::TRANSLATION_ALIAS . '.available', ':available'))
            ->andWhere($qb->expr()->eq(static::TRANSLATION_ALIAS . '.locale', ':locale'))
            ->setParameter('available', true)
            ->setParameter('locale', $locale)
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query->useResultCache(
            true,
            60,
            'RZTranslationAllByLocaleAndAvailable-' .
            $locale
        );

        return $query->getResult();
    }

    /**
     * Get all available translations by overrideLocale.
     *
     * @param $overrideLocale
     * @return Translation[]
     */
    public function findByOverrideLocaleAndAvailable($overrideLocale)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->andWhere($qb->expr()->eq(static::TRANSLATION_ALIAS . '.available', ':available'))
            ->andWhere($qb->expr()->eq(static::TRANSLATION_ALIAS . '.overrideLocale', ':overrideLocale'))
            ->setParameter('available', true)
            ->setParameter('overrideLocale', $overrideLocale)
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query->useResultCache(
            true,
            60,
            'RZTranslationAllByOverrideAndAvailable-' . $overrideLocale
        );

        return $query->getResult();
    }

    /**
     * Get one translation by locale or override locqle.
     *
     * @param $locale
     *
     * @return Translation|null
     */
    public function findOneByLocaleOrOverrideLocale($locale)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->eq(static::TRANSLATION_ALIAS . '.locale', ':locale'),
            $qb->expr()->eq(static::TRANSLATION_ALIAS . '.overrideLocale', ':locale')
        ))
            ->setParameter('locale', $locale)
            ->setMaxResults(1)
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query->useResultCache(true, 60, 'findOneByLocaleOrOverrideLocale_' . $locale);

        return $query->getOneOrNullResult();
    }

    /**
     * Get one available translation by locale or override locqle.
     *
     * @param $locale
     *
     * @return Translation|null
     */
    public function findOneAvailableByLocaleOrOverrideLocale($locale)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->eq(static::TRANSLATION_ALIAS . '.locale', ':locale'),
            $qb->expr()->eq(static::TRANSLATION_ALIAS . '.overrideLocale', ':locale')
        ))
            ->andWhere($qb->expr()->eq(static::TRANSLATION_ALIAS . '.available', ':available'))
            ->setParameter('available', true)
            ->setParameter('locale', $locale)
            ->setMaxResults(1)
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query->useResultCache(true, 60, 'findOneAvailableByLocaleOrOverrideLocale_' . $locale);

        return $query->getOneOrNullResult();
    }

    /**
     * Get one available translation by locale.
     *
     * @param $locale
     *
     * @return Translation|null
     */
    public function findOneByLocaleAndAvailable($locale)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->andWhere($qb->expr()->eq(static::TRANSLATION_ALIAS . '.available', ':available'))
            ->andWhere($qb->expr()->eq(static::TRANSLATION_ALIAS . '.locale', ':locale'))
            ->setParameter('available', true)
            ->setParameter('locale', $locale)
            ->setMaxResults(1)
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query->useResultCache(true, 60, 'RZTranslationOneByLocaleAndAvailable-' . $locale);

        return $query->getOneOrNullResult();
    }

    /**
     * Get one available translation by overrideLocale.
     *
     * @param $overrideLocale
     *
     * @return Translation|null
     */
    public function findOneByOverrideLocaleAndAvailable($overrideLocale)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->andWhere($qb->expr()->eq(static::TRANSLATION_ALIAS . '.available', ':available'))
            ->andWhere($qb->expr()->eq(static::TRANSLATION_ALIAS . '.overrideLocale', ':overrideLocale'))
            ->setParameter('available', true)
            ->setParameter('overrideLocale', $overrideLocale)
            ->setMaxResults(1)
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query->useResultCache(
            true,
            60,
            'RZTranslationOneByOverrideAndAvailable-' . $overrideLocale
        );

        return $query->getOneOrNullResult();
    }

    /**
     * @param Node $node
     * @return Translation[]
     */
    public function findAvailableTranslationsForNode(Node $node)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->innerJoin('t.nodeSources', static::NODESSOURCES_ALIAS)
            ->andWhere($qb->expr()->eq(static::NODESSOURCES_ALIAS . '.node', ':node'))
            ->addOrderBy('t.defaultTranslation', 'DESC')
            ->addOrderBy('t.locale', 'ASC')
            ->setParameter('node', $node)
            ->setCacheable(true);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Tag $tag
     * @return Translation[]
     */
    public function findAvailableTranslationsForTag(Tag $tag)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->innerJoin('t.tagTranslations', 'tt')
            ->andWhere($qb->expr()->eq('tt.tag', ':tag'))
            ->addOrderBy('t.defaultTranslation', 'DESC')
            ->addOrderBy('t.locale', 'ASC')
            ->setParameter('tag', $tag);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Folder $folder
     * @return Translation[]
     */
    public function findAvailableTranslationsForFolder(Folder $folder)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->innerJoin('t.folderTranslations', 'ft')
            ->andWhere($qb->expr()->eq('ft.folder', ':folder'))
            ->addOrderBy('t.defaultTranslation', 'DESC')
            ->addOrderBy('t.locale', 'ASC')
            ->setParameter('folder', $folder);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find available node translations which are available too.
     *
     * @param Node $node
     * @return Translation[]
     */
    public function findStrictlyAvailableTranslationsForNode(Node $node)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->innerJoin('t.nodeSources', static::NODESSOURCES_ALIAS)
            ->andWhere($qb->expr()->eq(static::NODESSOURCES_ALIAS . '.node', ':node'))
            ->andWhere($qb->expr()->eq(static::TRANSLATION_ALIAS . '.available', ':available'))
            ->addOrderBy('t.defaultTranslation', 'DESC')
            ->addOrderBy('t.locale', 'ASC')
            ->setParameter('node', $node)
            ->setParameter('available', true)
            ->setCacheable(true);

        return $qb->getQuery()->getResult();
    }


    /**
     * @param Node $node
     * @return Translation[]
     */
    public function findUnavailableTranslationsForNode(Node $node)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->andWhere($qb->expr()->notIn('t.id', ':translationsId'))
            ->setParameter('translationsId', $this->findAvailableTranslationIdForNode($node))
            ->setCacheable(true);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Node $node
     * @return array
     */
    public function findAvailableTranslationIdForNode(Node $node)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->select(static::TRANSLATION_ALIAS . '.id')
            ->innerJoin('t.nodeSources', static::NODESSOURCES_ALIAS)
            ->andWhere($qb->expr()->eq(static::NODESSOURCES_ALIAS . '.node', ':node'))
            ->addOrderBy('t.defaultTranslation', 'DESC')
            ->addOrderBy('t.locale', 'ASC')
            ->setParameter('node', $node)
            ->setCacheable(true);

        return array_map('current', $qb->getQuery()->getScalarResult());
    }

    /**
     * @param Node $node
     * @return array
     */
    public function findUnavailableTranslationIdForNode(Node $node)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->select(static::TRANSLATION_ALIAS . '.id')
            ->andWhere($qb->expr()->notIn('t.id', ':translationsId'))
            ->setParameter('translationsId', $this->findAvailableTranslationIdForNode($node))
            ->setCacheable(true);

        return array_map('current', $qb->getQuery()->getScalarResult());
    }
}
