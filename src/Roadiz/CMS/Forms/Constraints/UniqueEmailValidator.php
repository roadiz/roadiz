<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @package RZ\Roadiz\CMS\Forms\Constraints
 * @deprecated Use UniqueEntityValidator constraint instead with "email" field
 */
class UniqueEmailValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($constraint instanceof UniqueEmail) {
            /*
             * If value is already the node name
             * do nothing.
             */
            if (null !== $value && null !== $constraint->currentValue && strtolower($value) == strtolower($constraint->currentValue)) {
                return;
            }

            if (null !== $constraint->entityManager) {
                if (null !== $value && true === $this->emailExists($value, $constraint->entityManager)) {
                    $this->context->addViolation($constraint->message);
                }
            } else {
                $this->context->addViolation('UniqueEmailValidator constraint requires a valid EntityManager');
            }
        }
    }

    /**
     * @param string $email
     * @param EntityManager $entityManager
     *
     * @return bool
     */
    protected function emailExists(string $email, EntityManager $entityManager)
    {
        $user = $entityManager->getRepository(User::class)->findOneByEmail(strtolower($email));
        return (null !== $user);
    }
}
