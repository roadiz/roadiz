<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidAccountEmailValidator extends ConstraintValidator
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
        $user = $this->entityManager
                           ->getRepository(User::class)
                           ->findOneByEmail($value);

        if (null === $user) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%email%', $this->formatValue($value))
                ->setInvalidValue($value)
                ->addViolation();
        }
    }
}
