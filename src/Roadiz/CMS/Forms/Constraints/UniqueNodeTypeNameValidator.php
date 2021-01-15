<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class UniqueNodeTypeNameValidator extends ConstraintValidator
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate($value, Constraint $constraint)
    {
        if (null !== $value) {
            $value = StringHandler::classify($value);
        }

        /*
         * If value is already the node name
         * do nothing.
         */
        if (null !== $constraint->currentValue && $value == $constraint->currentValue) {
            return;
        }

        if (true === $this->nameExists($value)) {
            $this->context->addViolation($constraint->message);
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    protected function nameExists(string $name)
    {
        $entity = $this->entityManager->getRepository(NodeType::class)
                             ->findOneBy([
                                 'name' => $name,
                             ]);

        return (null !== $entity);
    }
}
