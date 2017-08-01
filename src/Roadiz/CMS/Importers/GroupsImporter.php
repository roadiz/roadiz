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
 * @file GroupsImporter.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\CMS\Importers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Group;
use RZ\Roadiz\Core\Serializers\GroupCollectionJsonSerializer;

/**
 * {@inheritdoc}
 */
class GroupsImporter implements ImporterInterface
{
    /**
     * Import a Json file (.rzt) containing group.
     *
     * @param string $serializedData
     * @param EntityManager $em
     *
     * @return bool
     */
    public static function importJsonFile($serializedData, EntityManager $em)
    {
        $serializer = new GroupCollectionJsonSerializer($em);
        /** @var \RZ\Roadiz\Core\Entities\Group[] $groups */
        $groups = $serializer->deserialize($serializedData);
        foreach ($groups as $group) {
            /** @var Group $existingGroup */
            $existingGroup = $em->getRepository('RZ\Roadiz\Core\Entities\Group')
                                ->findOneByName($group->getName());

            if (null === $existingGroup) {
                $em->persist($group);
                // Flush before creating group's roles.
                $em->flush($group);
            } else {
                $existingGroup->getHandler()->diff($group);
                $em->flush($existingGroup);
            }
        }

        return true;
    }
}
