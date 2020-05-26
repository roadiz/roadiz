<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Role;

/**
 * Serialization class for Role.
 *
 * @deprecated Use Serializer service.
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
     * @param \Doctrine\Common\Collections\ArrayCollection $roles
     * @deprecated Use Serializer service.
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
     * @deprecated Use Serializer service.
     * @throws \Exception
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
                $tmp = $this->em->getRepository(Role::class)->findOneByName($role['name']);
                $data[] = $tmp;
            }
        }
        return $data;
    }
}
