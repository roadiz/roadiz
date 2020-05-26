<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueTagNameValidator extends ConstraintValidator
{
    /**
     * @param string $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if ($this->isMulti($value)) {
            $names = explode(',', $value);
            foreach ($names as $name) {
                $name = strip_tags(trim($name));
                $this->testSingleValue($name, $constraint);
            }
        } else {
            $this->testSingleValue($value, $constraint);
        }
    }

    /**
     * @param string $value
     * @param Constraint $constraint
     */
    protected function testSingleValue($value, Constraint $constraint)
    {
        $value = StringHandler::slugify($value);

        /*
         * If value is already the node name
         * do nothing.
         */
        if (null !== $constraint->currentValue && $value == $constraint->currentValue) {
            return;
        }

        if (null !== $constraint->entityManager) {
            if (true === $this->tagNameExists($value, $constraint->entityManager)) {
                $this->context->addViolation($constraint->message, [
                    '%name%' => $value,
                ]);
            }
        } else {
            $this->context->addViolation('UniqueTagNameValidator constraint requires a valid EntityManager');
        }
    }

    /**
     * @param string $name
     * @param \Doctrine\ORM\EntityManager $entityManager
     *
     * @return bool
     */
    protected function tagNameExists($name, $entityManager)
    {
        $entity = $entityManager->getRepository(Tag::class)->findOneByTagName($name);

        return (null !== $entity);
    }

    /**
     * @param $value
     * @return bool
     */
    protected function isMulti($value)
    {
        return (boolean) strpos($value, ',');
    }
}
