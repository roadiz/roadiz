<?php
/**
 * Created by PhpStorm.
 * User: adrien
 * Date: 28/03/2017
 * Time: 19:38
 */

namespace Themes\Rozier\Models;

use Pimple\Container;
use RZ\Roadiz\Core\Entities\CustomForm;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Translation\Translator;

class CustomFormModel
{
    public static $thumbnailArray;
    /**
     * @var CustomForm
     */
    private $customForm;
    /**
     * @var Container
     */
    private $container;

    /**
     * CustomFormModel constructor.
     * @param CustomForm $customForm
     * @param Container $container
     */
    public function __construct(CustomForm $customForm, Container $container)
    {
        $this->customForm = $customForm;
        $this->container = $container;
    }

    public function toArray()
    {
        /** @var UrlGenerator $urlGenerator */
        $urlGenerator = $this->container->offsetGet('urlGenerator');

        /** @var Translator $translator */
        $translator = $this->container->offsetGet('translator');

        $countFields = strip_tags($translator->transChoice(
            '{0} no.customFormField|{1} 1.customFormField|]1,Inf] %count%.customFormFields',
            $this->customForm->getFields()->count(),
            [
                '%count%' => $this->customForm->getFields()->count()
            ]
        ));

        $result = [
            'id' => $this->customForm->getId(),
            'name' => $this->customForm->getDisplayName(),
            'countFields' => $countFields,
            'customFormsEditPage' => $urlGenerator->generate('customFormsEditPage', [
                'customFormId' => $this->customForm->getId()
            ]),
        ];

        return $result;
    }
}
