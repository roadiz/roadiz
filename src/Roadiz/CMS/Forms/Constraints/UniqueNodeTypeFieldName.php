<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueNodeTypeFieldName extends Constraint
{
    public $entityManager = null;
    public $nodeType = null;
    public $currentValue = null;

    public $message = 'nodeTypeField.name.alreadyExists';
}
