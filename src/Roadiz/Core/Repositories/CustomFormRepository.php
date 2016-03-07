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
 * @file CustomFormRepository.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

use Doctrine\ORM\NoResultException;
use RZ\Roadiz\Core\Entities\NodeTypeField;

/**
 * {@inheritdoc}
 */
class CustomFormRepository extends EntityRepository
{
    /**
     * Get all custom-form names from PARTIAL objects.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findAllNames()
    {
        $query = $this->_em->createQuery('
            SELECT partial nt.{id,name} FROM RZ\Roadiz\Core\Entities\CustomForm nt');

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\Node          $node
     * @param \RZ\Roadiz\Core\Entities\NodeTypeField $field
     *
     * @return array
     */
    public function findByNodeAndField($node, NodeTypeField $field)
    {
        $query = $this->_em->createQuery('
            SELECT cf FROM RZ\Roadiz\Core\Entities\CustomForm cf
            INNER JOIN cf.nodes ncf
            WHERE ncf.field = :field AND ncf.node = :node
            ORDER BY ncf.position ASC')
                        ->setParameter('field', $field)
                        ->setParameter('node', $node);
        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\Node $node
     * @param string                      $fieldName
     *
     * @return array
     */
    public function findByNodeAndFieldName($node, $fieldName)
    {
        $query = $this->_em->createQuery('
            SELECT cf FROM RZ\Roadiz\Core\Entities\CustomForm cf
            INNER JOIN cf.nodes ncf
            INNER JOIN ncf.field f
            WHERE f.name = :name AND ncf.node = :node
            ORDER BY ncf.position ASC')
                        ->setParameter('name', (string) $fieldName)
                        ->setParameter('node', $node);
        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return null;
        }
    }
}
