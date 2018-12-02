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
 * @file GroupJsonSerializer.php
 * @author Thomas Aufresne
 */
namespace RZ\Roadiz\Core\Serializers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Group;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Serialization class for Group.
 */
class GroupJsonSerializer extends AbstractJsonSerializer
{
    protected $em;
    protected $roleSerializer;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->roleSerializer = new RoleJsonSerializer();
    }

    /**
     * Create a simple associative array with Group entity.
     *
     * @param Group $group
     *
     * @return array
     */
    public function toArray($group)
    {
        $data = [];

        $data['name'] = $group->getName();
        $data['roles'] = [];

        foreach ($group->getRolesEntities() as $role) {
            $data['roles'][] = $this->roleSerializer->toArray($role);
        }

        return $data;
    }

    /**
     * Deserializes a Json into readable datas
     *
     * @param string $string
     *
     * @return Group[]
     * @throws \Exception
     */
    public function deserialize($string)
    {
        if ($string == "") {
            throw new \Exception('File is empty.');
        }
        $encoder = new JsonEncoder();
        $nameConverter = new CamelCaseToSnakeCaseNameConverter([
            'name',
        ]);
        $normalizer = new GetSetMethodNormalizer(null, $nameConverter);

        $serializer = new Serializer([$normalizer], [$encoder]);
        $group = $serializer->deserialize($string, Group::class, 'json');

        /*
         * Importing Roles.
         *
         * We need to extract roles from group and to re-encode them
         * to pass to RoleJsonSerializer.
         */
        $tempArray = json_decode($string, true);
        $data = [];

        if (!empty($tempArray['roles'])) {
            foreach ($tempArray['roles'] as $roleAssoc) {
                $role = $this->roleSerializer->deserialize(json_encode($roleAssoc));
                $role = $this->em->getRepository(Role::class)
                             ->findOneByName($role->getName());
                $group->addRole($role);
            }
            $data[] = $group;
        }

        return $data;
    }
}
