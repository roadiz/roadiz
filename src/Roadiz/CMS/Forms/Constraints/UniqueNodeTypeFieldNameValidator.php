<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Utils\StringHandler;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueNodeTypeFieldNameValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $value = StringHandler::variablize($value);

        if ($constraint instanceof UniqueNodeTypeFieldName) {
            /*
             * If value is already the node name
             * do nothing.
             */
            if (null !== $constraint->currentValue && $value == $constraint->currentValue) {
                return;
            }

            if (null !== $constraint->entityManager &&
                null !== $constraint->nodeType) {
                if (true === $this->nameExists($value, $constraint->nodeType, $constraint->entityManager)) {
                    $this->context->addViolation($constraint->message);
                }
            } else {
                $this->context->addViolation('UniqueNodeTypeFieldNameValidator constraint requires a valid EntityManager');
            }
        }
    }

    /**
     * @param string $name
     * @param NodeType $nodeType
     * @param EntityManager $entityManager
     *
     * @return bool
     */
    protected function nameExists($name, $nodeType, $entityManager)
    {
        $entity = $entityManager->getRepository(NodeTypeField::class)
                             ->findOneBy([
                                 'name' => $name,
                                 'nodeType' => $nodeType,
                             ]);

        return (null !== $entity);
    }
}
