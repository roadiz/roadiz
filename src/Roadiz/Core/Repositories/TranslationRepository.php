<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;

/**
 * @package RZ\Roadiz\Core\Repositories
 * @extends EntityRepository<\RZ\Roadiz\Core\Entities\Translation>
 */
class TranslationRepository extends EntityRepository
{
    /**
     * Get single default translation.
     *
     * @return TranslationInterface|null
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
        $query
            ->enableResultCache(1800, 'RZTranslationDefault')
            ->setQueryCacheLifetime(1800)
        ;

        return $query->getOneOrNullResult();
    }

    /**
     * Get all available translations.
     *
     * @return TranslationInterface[]
     */
    public function findAllAvailable()
    {
        $qb = $this->createQueryBuilder('t');
        $qb->andWhere($qb->expr()->eq('t.available', ':available'))
            // Default translation should be first
            ->addOrderBy('t.defaultTranslation', 'DESC')
            ->setParameter(':available', true)
            ->setCacheable(true);

        return $qb->getQuery()
            ->enableResultCache(1800, 'RZTranslationAllAvailable')
            ->setQueryCacheLifetime(1800)
            ->getResult();
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
        $query
            ->enableResultCache(120, 'RZTranslationExists-' . $locale)
            ->setQueryCacheLifetime(120)
        ;

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
            // Default translation should be first
            ->addOrderBy('t.defaultTranslation', 'DESC')
            ->setParameter(':available', true)
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query
            ->enableResultCache(120, 'RZTranslationGetAvailableLocales')
            ->setQueryCacheLifetime(120)
        ;

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
            // Default translation should be first
            ->addOrderBy('t.defaultTranslation', 'DESC')
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query
            ->enableResultCache(120, 'RZTranslationGetAllLocales')
            ->setQueryCacheLifetime(120)
        ;

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
            // Default translation should be first
            ->addOrderBy('t.defaultTranslation', 'DESC')
            ->setParameter(':available', true)
            ->setParameter(':overrideLocale', '')
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query
            ->enableResultCache(120, 'RZTranslationGetAvailableOverrideLocales')
            ->setQueryCacheLifetime(120)
        ;

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
            // Default translation should be first
            ->addOrderBy('t.defaultTranslation', 'DESC')
            ->setParameter(':overrideLocale', '')
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query
            ->enableResultCache(120, 'RZTranslationGetAllOverrideLocales')
            ->setQueryCacheLifetime(120)
        ;

        return array_map('current', $query->getScalarResult());
    }

    /**
     * Get all available translations by locale.
     *
     * @param string $locale
     *
     * @return TranslationInterface[]
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
        $query->enableResultCache(
            120,
            'RZTranslationAllByLocaleAndAvailable-' . $locale
        )->setQueryCacheLifetime(120);

        return $query->getResult();
    }

    /**
     * Get all available translations by overrideLocale.
     *
     * @param string $overrideLocale
     * @return TranslationInterface[]
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
        $query->enableResultCache(
            120,
            'RZTranslationAllByOverrideAndAvailable-' . $overrideLocale
        )->setQueryCacheLifetime(120);

        return $query->getResult();
    }

    /**
     * Get one translation by locale or override locale.
     *
     * @param string $locale
     * @param string $alias
     *
     * @return TranslationInterface|null
     */
    public function findOneByLocaleOrOverrideLocale(
        $locale,
        string $alias = TranslationRepository::TRANSLATION_ALIAS
    ): ?TranslationInterface {
        $qb = $this->createQueryBuilder($alias);
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->eq($alias . '.locale', ':locale'),
            $qb->expr()->eq($alias . '.overrideLocale', ':locale')
        ))
            ->setParameter('locale', $locale)
            ->setMaxResults(1)
            ->setCacheable(true);

        $query = $qb->getQuery();
        $query
            ->enableResultCache(120, 'findOneByLocaleOrOverrideLocale_' . $locale)
            ->setQueryCacheLifetime(120)
        ;

        return $query->getOneOrNullResult();
    }

    /**
     * Get one available translation by locale or override locale.
     *
     * @param string $locale
     *
     * @return TranslationInterface|null
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
        $query
            ->enableResultCache(120, 'findOneAvailableByLocaleOrOverrideLocale_' . $locale)
            ->setQueryCacheLifetime(120)
        ;

        return $query->getOneOrNullResult();
    }

    /**
     * Get one available translation by locale.
     *
     * @param string $locale
     *
     * @return TranslationInterface|null
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
        $query
            ->enableResultCache(120, 'RZTranslationOneByLocaleAndAvailable-' . $locale)
            ->setQueryCacheLifetime(120)
        ;

        return $query->getOneOrNullResult();
    }

    /**
     * Get one available translation by overrideLocale.
     *
     * @param string $overrideLocale
     *
     * @return TranslationInterface|null
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
        $query->enableResultCache(
            120,
            'RZTranslationOneByOverrideAndAvailable-' . $overrideLocale
        )->setQueryCacheLifetime(120);

        return $query->getOneOrNullResult();
    }

    /**
     * @param Node $node
     * @return TranslationInterface[]
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

        return $qb->getQuery()->setQueryCacheLifetime(60)->getResult();
    }

    /**
     * @param Tag $tag
     * @return TranslationInterface[]
     */
    public function findAvailableTranslationsForTag(Tag $tag)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->innerJoin('t.tagTranslations', 'tt')
            ->andWhere($qb->expr()->eq('tt.tag', ':tag'))
            ->addOrderBy('t.defaultTranslation', 'DESC')
            ->addOrderBy('t.locale', 'ASC')
            ->setParameter('tag', $tag);

        return $qb->getQuery()->setQueryCacheLifetime(60)->getResult();
    }

    /**
     * @param Folder $folder
     * @return TranslationInterface[]
     */
    public function findAvailableTranslationsForFolder(Folder $folder)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->innerJoin('t.folderTranslations', 'ft')
            ->andWhere($qb->expr()->eq('ft.folder', ':folder'))
            ->addOrderBy('t.defaultTranslation', 'DESC')
            ->addOrderBy('t.locale', 'ASC')
            ->setParameter('folder', $folder);

        return $qb->getQuery()->setQueryCacheLifetime(60)->getResult();
    }

    /**
     * Find available node translations which are available too.
     *
     * @param Node $node
     * @return TranslationInterface[]
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

        return $qb->getQuery()->setQueryCacheLifetime(60)->getResult();
    }


    /**
     * @param Node $node
     * @return TranslationInterface[]
     */
    public function findUnavailableTranslationsForNode(Node $node)
    {
        $qb = $this->createQueryBuilder(static::TRANSLATION_ALIAS);
        $qb->andWhere($qb->expr()->notIn('t.id', ':translationsId'))
            ->setParameter('translationsId', $this->findAvailableTranslationIdForNode($node))
            ->setCacheable(true);

        return $qb->getQuery()->setQueryCacheLifetime(60)->getResult();
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

        return array_map('current', $qb->getQuery()->setQueryCacheLifetime(60)->getScalarResult());
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

        return array_map('current', $qb->getQuery()->setQueryCacheLifetime(60)->getScalarResult());
    }
}
