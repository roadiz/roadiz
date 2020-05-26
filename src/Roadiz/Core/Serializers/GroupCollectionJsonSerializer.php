<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Group;
use RZ\Roadiz\Core\Entities\Role;

/**
 * Serialization class for Group.
 * @deprecated Use Serializer service.
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
     * @deprecated Use Serializer service.
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
