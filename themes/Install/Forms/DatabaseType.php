<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file DatabaseType.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace Themes\Install\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DatabaseType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('driver', ChoiceType::class, [
                'choices' => [
                    'pdo_mysql' => 'pdo_mysql',
                    'pdo_pgsql' => 'pdo_pgsql',
                    'pdo_sqlite' => 'pdo_sqlite',
                ],
                'choices_as_values' => true,
                'label' => 'driver',
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    "id" => "choice",
                ],
            ])
            ->add('host', TextType::class, [
                "required" => false,
                'label' => 'host',
                'attr' => [
                    "autocomplete" => "off",
                    'id' => "host",
                ],
            ])
            ->add('port', IntegerType::class, [
                "required" => false,
                'label' => 'port',
                'attr' => [
                    "autocomplete" => "off",
                    'id' => "port",
                ],
            ])
            ->add('unix_socket', TextType::class, [
                "required" => false,
                'label' => 'unix_socket',
                'attr' => [
                    "autocomplete" => "off",
                    'id' => "unix_socket",
                ],
            ])
            ->add('path', TextType::class, [
                "required" => false,
                'label' => 'path',
                'attr' => [
                    "autocomplete" => "off",
                    'id' => "path",
                ],
            ])
            ->add('user', TextType::class, [
                'attr' => [
                    "autocomplete" => "off",
                    'id' => "user",
                ],
                'label' => 'username',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('password', PasswordType::class, [
                "required" => false,
                'label' => 'password',
                'attr' => [
                    "autocomplete" => "off",
                    'id' => 'password',
                ],
            ])
            ->add('dbname', TextType::class, [
                "required" => false,
                'label' => 'dbname',
                'attr' => [
                    "autocomplete" => "off",
                    'id' => 'dbname',
                ],
            ])
        ;
    }
}
