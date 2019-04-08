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
 * @file DocumentTranslation.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Models\DocumentInterface;
use JMS\Serializer\Annotation as Serializer;

/**
 * DocumentTranslation.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\DocumentTranslationRepository")
 * @ORM\Table(name="documents_translations", uniqueConstraints={@ORM\UniqueConstraint(columns={"document_id", "translation_id"})})
 */
class DocumentTranslation extends AbstractEntity
{
    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    protected $name = null;
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
    public function setName(?string $name): DocumentTranslation
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;
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
    public function setDescription(?string $description): DocumentTranslation
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $copyright;
    /**
     * @return string
     */
    public function getCopyright(): ?string
    {
        return $this->copyright;
    }
    /**
     * @param string $copyright
     *
     * @return $this
     */
    public function setCopyright(?string $copyright): DocumentTranslation
    {
        $this->copyright = $copyright;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Translation", inversedBy="documentTranslations", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="translation_id", referencedColumnName="id", onDelete="CASCADE")
     * @var  Translation
     */
    protected $translation;

    /**
     * @return Translation
     */
    public function getTranslation(): Translation
    {
        return $this->translation;
    }

    /**
     * @param Translation $translation
     * @return $this
     */
    public function setTranslation(Translation $translation): DocumentTranslation
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Document", inversedBy="documentTranslations", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="document_id", referencedColumnName="id", onDelete="CASCADE")
     * @var DocumentInterface
     * @Serializer\Exclude
     */
    protected $document;

    /**
     * @return Document
     */
    public function getDocument(): DocumentInterface
    {
        return $this->document;
    }

    /**
     * @param DocumentInterface $document
     * @return $this
     */
    public function setDocument(DocumentInterface $document)
    {
        $this->document = $document;

        return $this;
    }
}
