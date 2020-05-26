<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\DataTransformer;

use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Themes\Rozier\Explorer\ExplorerProviderInterface;

class ProviderDataTransformer implements DataTransformerInterface
{
    /**
     * @var NodeTypeField
     */
    protected $nodeTypeField;

    /**
     * @var ExplorerProviderInterface
     */
    protected $provider;

    /**
     * ProviderDataTransformer constructor.
     *
     * @param NodeTypeField             $nodeTypeField
     * @param ExplorerProviderInterface $provider
     */
    public function __construct(NodeTypeField $nodeTypeField, ExplorerProviderInterface $provider)
    {
        $this->nodeTypeField = $nodeTypeField;
        $this->provider = $provider;
    }

    /**
     * @param mixed $entitiesToForm
     * @return mixed
     */
    public function transform($entitiesToForm)
    {
        if ($this->nodeTypeField->isMultiProvider() && is_array($entitiesToForm)) {
            if (count($entitiesToForm) > 0) {
                return $this->provider->getItemsById($entitiesToForm);
            }
            return [];
        } elseif ($this->nodeTypeField->isSingleProvider()) {
            if (isset($entitiesToForm)) {
                return $this->provider->getItemsById($entitiesToForm);
            }
            return null;
        }
        throw new TransformationFailedException('Provider entities cannot be transformed to form model.');
    }

    /**
     * @param mixed $formToEntities
     * @return mixed
     */
    public function reverseTransform($formToEntities)
    {
        if (is_array($formToEntities) &&
            $this->nodeTypeField->isSingleProvider() &&
            isset($formToEntities[0])) {
            return $formToEntities[0];
        }

        return $formToEntities;
    }
}
