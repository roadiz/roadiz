<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class RoleNormalizer
 *
 * @package RZ\Roadiz\Core\Serializers
 * @deprecated Use JMS Serializer component
 */
class RoleNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /** @var SerializerInterface */
    private $serializer;

    /**
     * @inheritDoc
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function normalize($role, $format = null, array $context = [])
    {
        return [
            'name' => $role->getRole(),
        ];
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Role;
    }

    /**
     * @inheritDoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return new Role($data['name']);
    }

    /**
     * @inheritDoc
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type == Role::class && !empty($data['name']);
    }
}
