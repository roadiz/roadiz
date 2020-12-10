<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine\Generators;

use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\String\UnicodeString;

/**
 * @package RZ\Roadiz\Utils\Doctrine\Generators
 */
class ProxiedManyToManyFieldGenerator extends AbstractFieldGenerator
{
    /**
     * @var array|null
     */
    protected $configuration;

    /**
     * @return array
     */
    protected function getConfiguration(): array
    {
        if (null === $this->configuration) {
            $this->configuration = Yaml::parse($this->field->getDefaultValues() ?? '');
        }
        return $this->configuration;
    }

    /**
     * Generate PHP property declaration block.
     */
    protected function getFieldDeclaration(): string
    {
        /*
         * Buffer var to get referenced entities (documents, nodes, cforms, doctrine entities)
         */
        return '    private $'.$this->getProxiedVarName().';'.PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function getFieldAnnotation(): string
    {
        /*
         * Many Users have Many Groups.
         * @ManyToMany(targetEntity="Group")
         * @JoinTable(name="users_groups",
         *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
         *      inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id")}
         */
        $configuration = $this->getConfiguration();
        $orderByClause = '';
        if (isset($configuration['proxy']['orderBy']) && count($configuration['proxy']['orderBy']) > 0) {
            // use default order for Collections
            $orderBy = [];
            foreach ($configuration['proxy']['orderBy'] as $order) {
                $orderBy[$order['field']] = $order['direction'];
            }
            $orderByClause = '@ORM\OrderBy(value='.json_encode($orderBy).')';
        }
        $ormParams = [
            'targetEntity' => '"' . $this->getProxyClassname() . '"',
            'mappedBy' => '"' . $configuration['proxy']['self'] . '"',
            'orphanRemoval' => 'true',
            'cascade' => '{"persist", "remove"}'
        ];

        return '
    /**
     * ' . $this->field->getLabel() .'
     *
     * @Serializer\Exclude()
     * @var \Doctrine\Common\Collections\ArrayCollection<' . $this->getProxyClassname() . '>
     * @ORM\OneToMany(' . static::flattenORMParameters($ormParams) . ')
     * ' . $orderByClause . '
     */'.PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function getFieldGetter(): string
    {
        return '
    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function '.$this->getProxiedGetterName().'()
    {
        return $this->'.$this->getProxiedVarName().';
    }
    /**
     * @Serializer\Groups({"nodes_sources", "nodes_sources_'.($this->field->getGroupNameCanonical() ?: 'default').'"})
     * @Serializer\VirtualProperty()
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function '.$this->field->getGetterName().'()
    {
        return $this->'.$this->getProxiedVarName().'->map(function ('.$this->getProxyClassname().' $proxyEntity) {
            return $proxyEntity->'.$this->getProxyRelationGetterName().'();
        });
    }'.PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function getFieldSetter(): string
    {
        return '
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection $'.$this->getProxiedVarName().'
     * @Serializer\VirtualProperty()
     * @return $this
     */
    public function '.$this->getProxiedSetterName().'($'.$this->getProxiedVarName().' = null)
    {
        $this->'.$this->getProxiedVarName().' = $'.$this->getProxiedVarName().';

        return $this;
    }
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|null $'.$this->field->getVarName().'
     * @return $this
     */
    public function '.$this->field->getSetterName().'($'.$this->field->getVarName().' = null)
    {
        foreach ($this->'.$this->getProxiedGetterName().'() as $item) {
            $item->'.$this->getProxySelfSetterName().'(null);
        }
        $this->'.$this->getProxiedVarName().'->clear();
        if (null !== $'.$this->field->getVarName().') {
            $position = 0;
            foreach ($'.$this->field->getVarName().' as $single'.ucwords($this->field->getVarName()).') {
                $proxyEntity = new '.$this->getProxyClassname().'();
                $proxyEntity->'.$this->getProxySelfSetterName().'($this);
                if ($proxyEntity instanceof \RZ\Roadiz\Core\AbstractEntities\PositionedInterface) {
                    $proxyEntity->setPosition(++$position);
                }
                $proxyEntity->'.$this->getProxyRelationSetterName().'($single'.ucwords($this->field->getVarName()).');
                $this->'.$this->getProxiedVarName().'->add($proxyEntity);
            }
        }

        return $this;
    }'.PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function getFieldConstructorInitialization(): string
    {
        return '$this->' . $this->getProxiedVarName() . ' = new \Doctrine\Common\Collections\ArrayCollection();';
    }

    /**
     * @return string
     */
    protected function getProxiedVarName(): string
    {
        return $this->field->getVarName(). 'Proxy';
    }
    /**
     * @return string
     */
    protected function getProxiedSetterName(): string
    {
        return $this->field->getSetterName(). 'Proxy';
    }
    /**
     * @return string
     */
    protected function getProxiedGetterName(): string
    {
        return $this->field->getGetterName(). 'Proxy';
    }
    /**
     * @return string
     */
    protected function getProxySelfSetterName(): string
    {
        $configuration = $this->getConfiguration();
        return 'set' . ucwords($configuration['proxy']['self']);
    }
    /**
     * @return string
     */
    protected function getProxyRelationSetterName(): string
    {
        $configuration = $this->getConfiguration();
        return 'set' . ucwords($configuration['proxy']['relation']);
    }
    /**
     * @return string
     */
    protected function getProxyRelationGetterName(): string
    {
        $configuration = $this->getConfiguration();
        return 'get' . ucwords($configuration['proxy']['relation']);
    }

    /**
     * @return string
     */
    protected function getProxyClassname(): string
    {
        $configuration = $this->getConfiguration();

        return (new UnicodeString($configuration['proxy']['classname']))->startsWith('\\') ?
            $configuration['proxy']['classname'] :
            '\\' . $configuration['proxy']['classname'];
    }
}
