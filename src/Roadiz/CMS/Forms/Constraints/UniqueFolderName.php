<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueFolderName extends Constraint
{
    public $entityManager = null;
    public $currentValue = null;

    public $message = 'folder.alreadyExists';
}
