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
 * @file FolderRepository.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\FolderTranslation;
use RZ\Roadiz\Core\Entities\Translation;

/**
 * {@inheritdoc}
 */
class FolderRepository extends EntityRepository
{

    /**
     * Find a folder according to the given path or create it.
     *
     * @param string $folderPath
     *
     * @return \RZ\Roadiz\Core\Entities\Folder
     */
    public function findOrCreateByPath($folderPath)
    {
        $folderPath = trim($folderPath);

        $folders = explode('/', $folderPath);
        $folders = array_filter($folders);

        $folderName = $folders[count($folders) - 1];
        $parentFolder = null;

        if (count($folders) > 1) {
            $parentName = $folders[count($folders) - 2];

            $parentFolder = $this->findOneByFolderName($parentName);
        }

        $folder = $this->findOneByFolderName($folderName);


        if (null === $folder) {
            /*
             * Creation of a new folder
             * before linking it to the node
             */
            $folder = new Folder();
            $folder->setFolderName($folderName);

            if (null !== $parentFolder) {
                $folder->setParent($parentFolder);
            }

            /*
             * Add folder translation
             * with given name
             */
            $translation = $this->_em->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                     ->findDefault();
            $folderTranslation = new FolderTranslation($folder, $translation);

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
     * @return \RZ\Roadiz\Core\Entities\Folder|null
     */
    public function findByPath($folderPath)
    {
        $folderPath = trim($folderPath);

        $folders = explode('/', $folderPath);
        $folders = array_filter($folders);

        $folderName = $folders[count($folders) - 1];

        return $this->findOneByFolderName($folderName);
    }

    /**
     * @param Folder $folder
     * @param Translation|null $translation
     * @return array
     */
    public function findAllChildrenFromFolder(Folder $folder, Translation $translation = null)
    {
        $ids = $this->findAllChildrenIdFromFolder($folder);
        if (count($ids) > 0) {
            $qb = $this->createQueryBuilder('f');
            $qb->addSelect('f')
                ->andWhere($qb->expr()->in('f.id', ':ids'))
                ->setParameter(':ids', $ids);

            if (null !== $translation && $translation instanceof Translation) {
                $qb->addSelect('tf')
                    ->innerJoin('f.translatedFolders', 'tf')
                    ->andWhere($qb->expr()->eq('tf.translation', ':translation'))
                    ->setParameter(':translation', $translation);
            }

            try {
                return $qb->getQuery()->getResult();
            } catch (NoResultException $e) {
                return [];
            }
        }
        return [];
    }

    /**
     * @param $folderName
     * @param Translation|null $translation
     * @return mixed|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByFolderName($folderName, Translation $translation = null)
    {
        $qb = $this->createQueryBuilder('f');
        $qb->addSelect('f')
            ->andWhere($qb->expr()->in('f.folderName', ':name'))
            ->setMaxResults(1)
            ->setParameter(':name', $folderName);

        if (null !== $translation && $translation instanceof Translation) {
            $qb->addSelect('tf')
                ->innerJoin('f.translatedFolders', 'tf')
                ->andWhere($qb->expr()->eq('tf.translation', ':translation'))
                ->setParameter(':translation', $translation);
        }

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
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

        try {
            $ids = $qb->getQuery()->getScalarResult();
            return array_map('current', $ids);
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * Create a Criteria object from a search pattern and additionnal fields.
     *
     * @param string $pattern Search pattern
     * @param QueryBuilder $qb QueryBuilder to pass
     * @param array $criteria Additionnal criteria
     * @param string $alias SQL query table alias
     *
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
        $metadatas = $this->_em->getClassMetadata('RZ\Roadiz\Core\Entities\FolderTranslation');
        $cols = $metadatas->getColumnNames();
        foreach ($cols as $col) {
            $field = $metadatas->getFieldName($col);
            $type = $metadatas->getTypeOfField($field);
            if (in_array($type, $this->searchableTypes)) {
                $criteriaFields[$field] = '%' . strip_tags(strtolower($pattern)) . '%';
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
     * @return null
     */
    public function countSearchBy($pattern, array $criteria = [])
    {
        $qb = $this->createQueryBuilder('f');
        $qb->add('select', 'count(f)')
            ->innerJoin('f.translatedFolders', 'obj');

        $qb = $this->createSearchBy($pattern, $qb, $criteria);

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (QueryException $e) {
            return null;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param Document $document
     * @param Translation|null $translation
     * @return array
     */
    public function findByDocumentAndTranslation(Document $document, Translation $translation = null)
    {
        $qb = $this->createQueryBuilder('f');
        $qb->innerJoin('f.documents', 'd')
            ->andWhere($qb->expr()->in('d', ':document'))
            ->setParameter(':document', $document);

        if (null !== $translation) {
            $qb->addSelect('tf')
                ->innerJoin(
                    'f.translatedFolders',
                    'tf',
                    Join::WITH,
                    'tf.translation = :translation'
                )
                ->setParameter(':translation', $translation);
        }

        try {
            return $qb->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }
}
