<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file RolesType.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Forms;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Roles selector form field type.
 */
class RolesType extends AbstractType
{
    protected $roles;

    /**
     * {@inheritdoc}
     * @param Doctrine\Common\Collections\ArrayCollection $roles Existing roles name array (used to display only available roles to parent entity)
     */
    public function __construct($roles = null)
    {
        $this->roles = $roles;
    }
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $roles = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\Role')
            ->findAll();

        $choices = array();

        foreach ($roles as $role) {
            if (!$this->roles->contains($role)) {
                $choices[$role->getId()] = $role->getName();
            }
        }

        $resolver->setDefaults(array(
            'choices' => $choices
        ));
    }
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'roles';
    }
}
