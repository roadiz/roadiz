<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file UniqueTranslationOverrideLocaleValidator.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueTranslationOverrideLocaleValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /*
         * If value is already the node name
         * do nothing.
         */
        if (null !== $constraint->currentValue && $value == $constraint->currentValue) {
            return;
        }

        if (null === $value) {
            return;
        }

        if (null !== $constraint->entityManager) {
            if (true === $this->nameExists($value, $constraint->entityManager)) {
                $this->context->addViolation($constraint->message);
            }
        } else {
            $this->context->addViolation('UniqueTranslationOverrideLocaleValidator constraint requires a valid EntityManager');
        }
    }

    /**
     * @param string $name
     * @param \Doctrine\ORM\EntityManager $entityManager
     *
     * @return bool
     */
    protected function nameExists($name, $entityManager)
    {
        $entity = $entityManager->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                ->findOneBy([
                                    'overrideLocale' => $name,
                                ]);

        return (null !== $entity);
    }
}
