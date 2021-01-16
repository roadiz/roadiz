<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class LoginType extends AbstractType
{
    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param RequestStack $requestStack
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, RequestStack $requestStack)
    {
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('_username', TextType::class, [
            'label' => 'username',
            'attr' => [
                'autocomplete' => 'username'
            ],
            'constraints' => [
                new NotNull(),
                new NotBlank(),
            ],
        ])
        ->add('_password', PasswordType::class, [
            'label' => 'password',
            'attr' => [
                'autocomplete' => 'current-password'
            ],
            'constraints' => [
                new NotNull(),
                new NotBlank(),
            ],
        ])
        ->add('_remember_me', CheckboxType::class, [
            'label' => 'keep_me_logged_in',
            'required' => false,
            'attr' => [
                'checked' => true
            ],
        ]);

        if ($this->requestStack->getMasterRequest()->query->has('_home')) {
            $builder->add('_target_path', HiddenType::class, [
                'data' => $this->urlGenerator->generate('adminHomePage')
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizer('action', function (Options $options) {
            return $this->urlGenerator->generate('loginCheckPage');
        });
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        /*
         * No prefix for firewall to catch username and password from request.
         */
        return '';
    }
}
