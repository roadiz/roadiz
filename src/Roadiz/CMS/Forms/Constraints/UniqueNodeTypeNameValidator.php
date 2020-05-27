<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueNodeTypeNameValidator extends ConstraintValidator
{
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

        if (null !== $constraint->entityManager) {
            if (true === $this->nameExists($value, $constraint->entityManager)) {
                $this->context->addViolation($constraint->message);
            }
        } else {
            $this->context->addViolation('UniqueNodeTypeNameValidator constraint requires a valid EntityManager');
        }
    }

    /**
     * @param string $name
     * @param \Doctrine\ORM\EntityManager $entityManager
     *
     * @return bool
     */
    protected function nameExists($name, $entityManager)
    {
        $entity = $entityManager->getRepository(NodeType::class)
                             ->findOneBy([
                                 'name' => $name,
                             ]);

        return (null !== $entity);
    }
}
