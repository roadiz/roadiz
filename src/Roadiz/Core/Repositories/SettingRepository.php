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
 * @file SettingRepository.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use Doctrine\ORM\NoResultException;

/**
 * {@inheritdoc}
 */
class SettingRepository extends EntityRepository
{
    /**
     * Return Setting raw value.
     *
     * @param string $name
     *
     * @return string
     */
    public function getValue($name)
    {
        $query = $this->_em->createQuery('
            SELECT s.value FROM RZ\Roadiz\Core\Entities\Setting s
            WHERE s.name = :name')
                        ->setParameter('name', $name);

        $query->useResultCache(true, 3600, 'RZSettingValue_'.$name);

        try {
            return $query->getSingleScalarResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function exists($name)
    {
        $query = $this->_em->createQuery('
            SELECT COUNT(s.value) FROM RZ\Roadiz\Core\Entities\Setting s
            WHERE s.name = :name')
                        ->setParameter('name', $name);

        $query->useResultCache(true, 3600, 'RZSettingExists_'.$name);

        try {
            return (boolean) $query->getSingleScalarResult();
        } catch (NoResultException $e) {
            return false;
        }
    }

    /**
     * @return array|bool
     */
    public function findAllNames()
    {
        $query = $this->_em->createQuery('SELECT s.name FROM RZ\Roadiz\Core\Entities\Setting s');
        try {
            $result = $query->getScalarResult();

            $ids = [];
            foreach ($result as $item) {
                $ids[] = $item['name'];
            }

            return $ids;
        } catch (NoResultException $e) {
            return false;
        }
    }
}
