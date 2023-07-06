<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Repositories;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\FolderTranslation;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Utils\StringHandler;

/**
 * @package RZ\Roadiz\Core\Repositories
 * @extends EntityRepository<Folder>
 */
class FolderRepository extends EntityRepository
{
    /**
     * Find a folder according to the given path or create it.
     *
     * @param string           $folderPath
     * @param TranslationInterface|null $translation
     *
     * @return Folder|null
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function findOrCreateByPath(string $folderPath, ?TranslationInterface $translation = null)
    {
        $folderPath = trim($folderPath);
        $folders = explode('/', $folderPath);
        $folders = array_filter($folders);

        if (count($folders) === 0) {
            return null;
        }

        $folderName = $folders[count($folders) - 1];
        $folder = $this->findOneByFolderName($folderName);

        if (null === $folder) {
            /*
             * Creation of a new folder
             * before linking it to the node
             */
            $parentFolder = null;

            if (count($folders) > 1) {
                $parentName = $folders[count($folders) - 2];
                $parentFolder = $this->findOneByFolderName($parentName);
            }
            $folder = new Folder();
            $folder->setFolderName($folderName);

            if (null !== $parentFolder) {
                $folder->setParent($parentFolder);
            }

            /*
             * Add folder translation
             * with given name
             */
            if (null === $translation) {
                $translation = $this->_em->getRepository(Translation::class)->findDefault();
            }
            $folderTranslation = new FolderTranslation($folder, $translation);
            $folderTranslation->setName($folderName);

            $this->_em->persist($folder);
            $this->_em->persist($folderTranslation);
            $this->_em->flush();
        }

        return $folder;
    }

    /**
     * Find a folder according to the given path.
     *
     * @param string $folderPath
     *
     * @return Folder|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByPath(string $folderPath)
    {
        $folderPath = trim($folderPath);

        $folders = explode('/', $folderPath);
        $folders = array_filter($folders);
        $folderName = $folders[count($folders) - 1];

        return $this->findOneByFolderName($folderName);
    }

    /**
     * @param Folder $folder
     * @param TranslationInterface|null $translation
     * @return array
     */
    public function findAllChildrenFromFolder(Folder $folder, TranslationInterface $translation = null)
    {
        $ids = $this->findAllChildrenIdFromFolder($folder);
        if (count($ids) > 0) {
            $qb = $this->createQueryBuilder('f');
            $qb->addSelect('f')
                ->andWhere($qb->expr()->in('f.id', ':ids'))
                ->setParameter(':ids', $ids);

            if (null !== $translation) {
                $qb->addSelect('tf')
                    ->leftJoin('f.translatedFolders', 'tf')
                    ->andWhere($qb->expr()->eq('tf.translation', ':translation'))
                    ->setParameter(':translation', $translation);
            }
            return $qb->getQuery()->setQueryCacheLifetime(0)->getResult();
        }
        return [];
    }

    /**
     * @param string $folderName
     * @param TranslationInterface|null $translation
     *
     * @return mixed|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByFolderName(string $folderName, TranslationInterface $translation = null)
    {
        $qb = $this->createQueryBuilder('f');
        $qb->addSelect('f')
            ->andWhere($qb->expr()->in('f.folderName', ':name'))
            ->setMaxResults(1)
            ->setParameter(':name', StringHandler::slugify($folderName));

        if (null !== $translation) {
            $qb->addSelect('tf')
                ->leftJoin('f.translatedFolders', 'tf')
                ->andWhere($qb->expr()->eq('tf.translation', ':translation'))
                ->setParameter(':translation', $translation);
        }

        return $qb->getQuery()->setQueryCacheLifetime(0)->getOneOrNullResult();
    }

    /**
     * @param Folder $folder
     * @return array
     */
    public function findAllChildrenIdFromFolder(Folder $folder)
    {
        $idsArray = $this->findChildrenIdFromFolder($folder);

        foreach ($folder->getChildren() as $child) {
            $idsArray = array_merge($idsArray, $this->findAllChildrenIdFromFolder($child));
        }

        return $idsArray;
    }

    /**
     * @param Folder $folder
     * @return array
     */
    public function findChildrenIdFromFolder(Folder $folder)
    {
        $qb = $this->createQueryBuilder('f');
        $qb->select('f.id')
            ->where($qb->expr()->eq('f.parent', ':parent'))
            ->setParameter(':parent', $folder);

        return array_map('current', $qb->getQuery()->setQueryCacheLifetime(120)->getScalarResult());
    }

    /**
     * Create a Criteria object from a search pattern and additionnal fields.
     *
     * @param string $pattern Search pattern
     * @param QueryBuilder $qb QueryBuilder to pass
     * @param array $criteria Additional criteria
     * @param string $alias SQL query table alias
     * @return QueryBuilder
     */
    protected function createSearchBy(
        $pattern,
        QueryBuilder $qb,
        array &$criteria = [],
        $alias = "obj"
    ) {
        $this->classicLikeComparison($pattern, $qb, $alias);

        /*
         * Search in translations
         */
        $qb->leftJoin('obj.translatedFolders', 'tf');
        $criteriaFields = [];
        $metadata = $this->_em->getClassMetadata(FolderTranslation::class);
        $cols = $metadata->getColumnNames();
        foreach ($cols as $col) {
            $field = $metadata->getFieldName($col);
            $type = $metadata->getTypeOfField($field);
            if (in_array($type, $this->searchableTypes)) {
                $criteriaFields[$field] = '%' . strip_tags((string) $pattern) . '%';
            }
        }
        foreach ($criteriaFields as $key => $value) {
            $fullKey = sprintf('LOWER(%s)', 'tf.' . $key);
            $qb->orWhere($qb->expr()->like($fullKey, $qb->expr()->literal($value)));
        }

        $qb = $this->prepareComparisons($criteria, $qb, $alias);

        return $qb;
    }

    /**
     * @param string $pattern
     * @param array $criteria
     * @param string $alias
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countSearchBy($pattern, array $criteria = [], $alias = "obj")
    {
        $qb = $this->createQueryBuilder($alias);
        $qb->select($qb->expr()->countDistinct($alias));
        $qb = $this->createSearchBy($pattern, $qb, $criteria, $alias);

        return (int) $qb->getQuery()->setQueryCacheLifetime(0)->getSingleScalarResult();
    }

    /**
     * @param Document $document
     * @param TranslationInterface|null $translation
     * @return array
     */
    public function findByDocumentAndTranslation(Document $document, TranslationInterface $translation = null)
    {
        $qb = $this->createQueryBuilder('f');
        $qb->innerJoin('f.documents', 'd')
            ->andWhere($qb->expr()->eq('d.id', ':documentId'))
            ->setParameter(':documentId', $document->getId());

        if (null !== $translation) {
            $qb->addSelect('tf')
                ->leftJoin(
                    'f.translatedFolders',
                    'tf',
                    Join::WITH,
                    'tf.translation = :translation'
                )
                ->setParameter(':translation', $translation);
        }

        return $qb->getQuery()->setQueryCacheLifetime(120)->getResult();
    }

    /**
     * @param Folder|null $parent
     * @param TranslationInterface|null $translation
     * @return array
     */
    public function findByParentAndTranslation(Folder $parent = null, TranslationInterface $translation = null)
    {
        $qb = $this->createQueryBuilder('f');
        $qb->addOrderBy('f.position', 'ASC');

        if (null === $parent) {
            $qb->andWhere($qb->expr()->isNull('f.parent'));
        } else {
            $qb->andWhere($qb->expr()->eq('f.parent', ':parent'))
                ->setParameter(':parent', $parent);
        }

        if (null !== $translation) {
            $qb->addSelect('tf')
                ->leftJoin(
                    'f.translatedFolders',
                    'tf',
                    Join::WITH,
                    'tf.translation = :translation'
                )
                ->setParameter(':translation', $translation);
        }

        return $qb->getQuery()->setQueryCacheLifetime(120)->getResult();
    }
}
