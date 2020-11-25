<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for the Unique Entity validator.
 *
 * @Annotation
 * @Target({"CLASS"})
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @see https://github.com/symfony/doctrine-bridge/blob/master/Validator/Constraints/UniqueEntity.php
 */
class UniqueEntity extends Constraint
{
    const NOT_UNIQUE_ERROR = '23bd9dbf-6b9b-41cd-a99e-4844bcf3077f';

    public $message = 'value.is.already.used';
    public $entityClass = null;
    public $repositoryMethod = 'findBy';
    public $errorPath = null;
    public $fields = [];
    public $ignoreNull = true;

    public function getRequiredOptions()
    {
        return ['fields'];
    }

    public function getDefaultOption()
    {
        return 'fields';
    }
}
