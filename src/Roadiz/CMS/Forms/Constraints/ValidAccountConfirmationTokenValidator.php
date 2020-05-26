<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidAccountConfirmationTokenValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (null !== $constraint->entityManager) {
            $user = $constraint->entityManager
                               ->getRepository(User::class)
                               ->findOneByConfirmationToken($value);

            if (null === $user) {
                $this->context->addViolation($constraint->message);
            } elseif (!$user->isPasswordRequestNonExpired($constraint->ttl)) {
                $this->context->addViolation($constraint->expiredMessage);
            }
        } else {
            $this->context->addViolation('ValidAccountConfirmationToken constraint requires a valid EntityManager');
        }
    }
}
