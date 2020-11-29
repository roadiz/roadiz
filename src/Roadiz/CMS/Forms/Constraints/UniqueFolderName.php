<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @package RZ\Roadiz\CMS\Forms\Constraints
 * @deprecated Use UniqueEntity constraint instead with "folderName" field
 */
class UniqueFolderName extends Constraint
{
    public $entityManager = null;
    public $currentValue = null;

    public $message = 'folder.alreadyExists';
}
