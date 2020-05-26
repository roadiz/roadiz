<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * Serialization class for Role.
 * @deprecated Use Serializer service.
 */
class RoleJsonSerializer extends AbstractJsonSerializer
{

    /**
     * Create a simple associative array with Role entity.
     *
     * @param \RZ\Roadiz\Core\Entities\Role $role
     * @deprecated Use Serializer service.
     * @return array
     */
    public function toArray($role)
    {
        $data = [];
        $data['name'] = $role->getRole();

        return $data;
    }

    /**
     * Deserialize a json file into a readable array of data.
     *
     * @param string $jsonString
     * @return \RZ\Roadiz\Core\Entities\Role
     * @deprecated Use Serializer service.
     * @throws \Exception
     */
    public function deserialize($jsonString)
    {
        if ($jsonString == "") {
            throw new \Exception('File is empty.');
        }

        $serializer = new Serializer([
            new RoleNormalizer()
        ], [new JsonEncoder()]);

        return $serializer->deserialize($jsonString, Role::class, 'json');
    }
}
