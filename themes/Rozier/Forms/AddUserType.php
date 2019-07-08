<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file AddUserType.php
 * @author Ambroise Maupate
 *
 */
namespace Themes\Rozier\Forms;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\CMS\Forms\GroupsType;
use RZ\Roadiz\Core\Entities\Group;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 *
 */
class AddUserType extends UserType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('groups', GroupsType::class, [
                'label' => 'user.groups',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'authorizationChecker' => $options['authorizationChecker'],
                'entityManager' => $options['em'],
            ])
        ;

        $builder->get('groups')->addModelTransformer(new CallbackTransformer(function ($modelToForm) {
            if ($modelToForm instanceof Collection) {
                $modelToForm = $modelToForm->toArray();
            }
            return array_map(function (Group $group) {
                return $group->getId();
            }, $modelToForm);
        }, function ($formToModels) use ($options) {
            if (count($formToModels) === 0) {
                return [];
            }
            return $options['em']->getRepository(Group::class)->findBy([
                'id' => $formToModels
            ]);
        }));
    }

    public function getBlockPrefix()
    {
        return 'add_user';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired('authorizationChecker');
        $resolver->setAllowedTypes('authorizationChecker', [AuthorizationCheckerInterface::class]);
    }
}
