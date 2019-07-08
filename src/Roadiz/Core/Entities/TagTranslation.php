<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file TagTranslation.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use JMS\Serializer\Annotation as Serializer;

/**
 * Translated representation of Tags.
 *
 * It stores their name and description.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="tags_translations", uniqueConstraints={
 *      @ORM\UniqueConstraint(columns={"tag_id", "translation_id"})
 * })
 */
class TagTranslation extends AbstractEntity
{
    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"tag", "node", "nodes_sources"})
     * @Serializer\Type("string")
     */
    protected $name;
    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"tag", "node", "nodes_sources"})
     * @Serializer\Type("string")
     */
    protected $description;
    /**
     * @ORM\ManyToOne(targetEntity="Tag", inversedBy="translatedTags")
     * @ORM\JoinColumn(name="tag_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Tag
     * @Serializer\Exclude()
     */
    protected $tag = null;
    /**
     * @ORM\ManyToOne(targetEntity="Translation", inversedBy="tagTranslations", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="translation_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Translation|null
     * @Serializer\Groups({"tag", "node", "nodes_sources"})
     * @Serializer\Type("RZ\Roadiz\Core\Entities\Translation")
     */
    protected $translation = null;
    /**
     * @ORM\OneToMany(
     *     targetEntity="RZ\Roadiz\Core\Entities\TagTranslationDocuments",
     *     mappedBy="tagTranslation",
     *     orphanRemoval=true,
     *     cascade={"persist", "merge"}
     * )
     * @ORM\OrderBy({"position" = "ASC"})
     * @var ArrayCollection|null
     * @Serializer\Groups({"tag"})
     * @Serializer\Type("ArrayCollection<RZ\Roadiz\Core\Entities\TagTranslationDocuments>")
     */
    protected $tagTranslationDocuments = null;

    /**
     * Create a new TagTranslation with its origin Tag and Translation.
     *
     * @param Tag         $original
     * @param Translation $translation
     */
    public function __construct(Tag $original = null, Translation $translation = null)
    {
        $this->setTag($original);
        $this->setTranslation($translation);
        $this->tagTranslationDocuments = new ArrayCollection();

        if (null !== $original) {
            $this->name = $original->getDirtyTagName() != '' ? $original->getDirtyTagName() : $original->getTagName();
        }
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name): TagTranslation
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description): TagTranslation
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets the value of tag.
     *
     * @return Tag
     */
    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    /**
     * Sets the value of tag.
     *
     * @param Tag $tag the tag
     *
     * @return self
     */
    public function setTag(?Tag $tag): TagTranslation
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Gets the value of translation.
     *
     * @return Translation
     */
    public function getTranslation(): ?Translation
    {
        return $this->translation;
    }

    /**
     * Sets the value of translation.
     *
     * @param Translation $translation the translation
     *
     * @return self
     */
    public function setTranslation(?Translation $translation): TagTranslation
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * After clone method.
     *
     * Be careful not to persist nor flush current entity after
     * calling clone as it empties its relations.
     */
    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $documents = $this->getDocuments();
            if ($documents !== null) {
                $this->tagTranslationDocuments = new ArrayCollection();
                /** @var TagTranslationDocuments $document */
                foreach ($documents as $document) {
                    $cloneDocument = clone $document;
                    $this->tagTranslationDocuments->add($cloneDocument);
                    $cloneDocument->setTagTranslation($this);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getDocuments(): array
    {
        return array_map(function (TagTranslationDocuments $tagTranslationDocument) {
            return $tagTranslationDocument->getDocument();
        }, $this->getTagTranslationDocuments()->toArray());
    }

    /**
     * @return Collection
     */
    public function getTagTranslationDocuments(): Collection
    {
        return $this->tagTranslationDocuments;
    }

    /**
     * @param ArrayCollection|null $tagTranslationDocuments
     * @return TagTranslation
     */
    public function setTagTranslationDocuments($tagTranslationDocuments): TagTranslation
    {
        $this->tagTranslationDocuments = $tagTranslationDocuments;
        return $this;
    }
}
