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

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

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
     * @ORM\Column(type="text", nullable=true)
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
     * @ORM\ManyToOne(targetEntity="Tag", inversedBy="translatedTags")
     * @ORM\JoinColumn(name="tag_id", referencedColumnName="id", onDelete="CASCADE")
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
     * @ORM\ManyToOne(targetEntity="Translation", inversedBy="tagTranslations", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="translation_id", referencedColumnName="id", onDelete="CASCADE")
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

        $this->name = $original->getDirtyTagName() != '' ? $original->getDirtyTagName() : $original->getTagName();
    }
}
