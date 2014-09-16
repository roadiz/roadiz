<?php
/*
 * Copyright REZO ZERO 2014
 *
 * @file TagTranslation.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractEntity;

/**
 * Translated representation of Tags.
 *
 * It stores their name and description.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Utils\EntityRepository")
 * @Table(name="tags_translations", uniqueConstraints={@UniqueConstraint(columns={"tag_id", "translation_id"})})
 */
class TagTranslation extends AbstractEntity
{
    /**
     * @Column(type="string")
     */
    private $name;
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @Column(type="text", nullable=true)
     */
    private $description;
    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @ManyToOne(targetEntity="Tag", inversedBy="translatedTags")
     * @JoinColumn(name="tag_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Tag
     */
    private $tag = null;
    /**
     * @return Tag
     */
    public function getTag()
    {
        return $this->tag;
    }
    /**
     * @param Tag $tag
     *
     * @return $this
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * @ManyToOne(targetEntity="Translation", fetch="EXTRA_LAZY")
     * @JoinColumn(name="translation_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Translation
     */
    private $translation = null;
    /**
     * @return Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }
    /**
     * @param Translation $translation
     *
     * @return $this
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * Create a new TagTranslation with its origin Tag and Translation.
     *
     * @param Tag         $original
     * @param Translation $translation
     */
    public function __construct(Tag $original, Translation $translation)
    {
        $this->setTag($original);
        $this->setTranslation($translation);
    }
}
