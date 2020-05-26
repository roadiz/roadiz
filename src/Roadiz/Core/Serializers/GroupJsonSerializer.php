<?php
declare(strict_types=1);

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
 * @deprecated Use Serializer service.
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
     * @deprecated Use Serializer service.
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
     * @deprecated Use Serializer service.
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
        /** @var Group $group */
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
                /** @var Role|null $role */
                $role = $this->em->getRepository(Role::class)
                             ->findOneByName($role->getRole());
                $group->addRole($role);
            }
            $data[] = $group;
        }

        return $data;
    }
}
