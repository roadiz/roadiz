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
 * @file GroupJsonSerializer.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */
namespace RZ\Renzo\Core\Serializers;

use RZ\Renzo\Core\Entities\Group;
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Serializers\EntitySerializer;
use RZ\Renzo\Core\Kernel;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

/**
 * Serialization class for Group.
 */
class GroupJsonSerializer extends AbstractJsonSerializer
{
    /**
     * Create a simple associative array with Group entity.
     *
     * @param RZ\Renzo\Core\Entities\Group $group
     *
     * @return array
     */
    public static function toArray($group)
    {
        $data = array();

        $data['name'] = $group->getName();
        $data['roles'] = array();

        foreach ($group->getRolesEntities() as $role) {
            $data['roles'][] = RoleJsonSerializer::toArray($role);
        }

        return $data;
    }

    /**
     * Deserializes a Json into readable datas
     * @param string $string
     *
     * @return RZ\Renzo\Core\Entities\Group
     */
    public static function deserialize($string)
    {
        if ($string == "") {
            throw new \Exception('File is empty.');
        }
        $encoder = new JsonEncoder();
        $normalizer = new GetSetMethodNormalizer();
        $normalizer->setCamelizedAttributes(array(
            'name',
        ));

        $serializer = new Serializer(array($normalizer), array($encoder));
        $group = $serializer->deserialize($string, 'RZ\Renzo\Core\Entities\Group', 'json');

        /*
         * Importing Roles.
         *
         * We need to extract roles from group and to re-encode them
         * to pass to RoleJsonSerializer.
         */
        $tempArray = json_decode($string, true);

        foreach ($tempArray['roles'] as $roleAssoc) {
            $role = RoleJsonSerializer::deserialize(json_encode($roleAssoc));
            $role = Kernel::getService('em')
                                         ->getRepository('RZ\Renzo\Core\Entities\Role')
                                         ->findOneByName($role->getName());
            $group->addRole($role);
        }
        $data = array();
        $data[] = $group;
        return $data;
    }
}
