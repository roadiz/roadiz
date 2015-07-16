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
 * @file RoleCollectionJsonSerializer.php
 * @author Thomas Aufresne
 */
namespace RZ\Roadiz\Core\Serializers;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

/**
 * Serialization class for Role.
 */
class RoleCollectionJsonSerializer extends AbstractJsonSerializer
{
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Create a simple associative array with
     * an ArrayCollection of Role.
     *
     * @param Doctrine\Common\Collections\ArrayCollection $roles
     *
     * @return array
     */
    public function toArray($roles)
    {
        $roleSerializer = new RoleJsonSerializer();
        $data = [];

        foreach ($roles as $role) {
            $data[] = $roleSerializer->toArray($role);
        }

        return $data;
    }

    /**
     * Deserializes a json file into a readable array of datas.
     *
     * @param string $jsonString
     *
     * @return ArrayCollection
     */
    public function deserialize($jsonString)
    {
        if ($jsonString == "") {
            throw new \Exception('File is empty.');
        }
        $roles = json_decode($jsonString, true);
        $data = new ArrayCollection();
        foreach ($roles as $role) {
            if (!empty($role['name'])) {
                $tmp = $this->em->getRepository('RZ\Roadiz\Core\Entities\Role')->findOneByName($role['name']);
                $data[] = $tmp;
            }
        }
        return $data;
    }
}
