<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidAccountEmailValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (null !== $constraint->entityManager) {
            $user = $constraint->entityManager
                               ->getRepository(User::class)
                               ->findOneByEmail($value);

            if (null === $user) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('%email%', $this->formatValue($value))
                    ->setInvalidValue($value)
                    ->addViolation();
            }
        } else {
            $this->context->addViolation('“ValidAccountEmail” constraint requires a valid EntityManager');
        }
    }
}
