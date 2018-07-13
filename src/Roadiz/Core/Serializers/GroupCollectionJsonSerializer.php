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
 * @file GroupCollectionJsonSerializer.php
 * @author Thomas Aufresne
 */
namespace RZ\Roadiz\Core\Serializers;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Group;
use RZ\Roadiz\Core\Entities\Role;

/**
 * Serialization class for Group.
 */
class GroupCollectionJsonSerializer extends AbstractJsonSerializer
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
     * an ArrayCollection of Group.
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $groups
     *
     * @return array
     */
    public function toArray($groups)
    {
        $data = [];

        $groupSerializer = new GroupJsonSerializer($this->em);
        foreach ($groups as $group) {
            $data[] = $groupSerializer->toArray($group);
        }

        return $data;
    }

    /**
     * Deserializes a Json into readable datas
     * @param string $string
     * @return ArrayCollection
     * @throws \Exception
     */
    public function deserialize($string)
    {
        if ($string == "") {
            throw new \Exception('File is empty.');
        }
        $collection = new ArrayCollection();
        $array = json_decode($string, true);

        foreach ($array as $groupAssoc) {
            if (!empty($groupAssoc["roles"]) &&
                !empty($groupAssoc["name"])) {
                $group = new Group();
                $group->setName($groupAssoc['name']);

                foreach ($groupAssoc["roles"] as $role) {
                    $role = $this->em->getRepository(Role::class)->findOneByName($role['name']);
                    $group->addRole($role);
                }

                $collection[] = $group;
            }
        }

        return $collection;
    }
}
