<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @package RZ\Roadiz\CMS\Forms\Constraints
 * @deprecated Use UniqueEntity constraint instead with "username" field
 */
class UniqueUsernameValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /*
         * If value is already the node name
         * do nothing.
         */
        if (null !== $value && null !== $constraint->currentValue && strtolower($value) == strtolower($constraint->currentValue)) {
            return;
        }

        if (null !== $constraint->entityManager) {
            if (true === $this->userNameExists($value, $constraint->entityManager)) {
                $this->context->addViolation($constraint->message);
            }
        } else {
            $this->context->addViolation('UniqueUsernameValidator constraint requires a valid EntityManager');
        }
    }

    /**
     * @param string $username
     * @param EntityManagerInterface $entityManager
     *
     * @return bool
     */
    protected function userNameExists($username, EntityManagerInterface $entityManager)
    {
        $user = $entityManager->getRepository(User::class)->findOneByUsername($username);

        return (null !== $user);
    }
}
