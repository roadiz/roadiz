<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
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
 * @file TagTranslationDocuments.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractPositioned;

/**
 * Describes a complex ManyToMany relation
 * between TagTranslation and Documents.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\TagTranslationDocumentsRepository")
 * @ORM\Table(name="tags_translations_documents", indexes={
 *     @ORM\Index(columns={"position"})
 * })
 */
class TagTranslationDocuments extends AbstractPositioned
{
    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\TagTranslation", inversedBy="tagTranslationDocuments", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(name="tag_translation_id", referencedColumnName="id", onDelete="CASCADE")
     * @var TagTranslation
     */
    protected $tagTranslation;

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Document", inversedBy="tagTranslations", fetch="EAGER", cascade={"persist"})
     * @ORM\JoinColumn(name="document_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Document
     */
    protected $document;

    /**
     * Create a new relation between NodeSource, a Document and a NodeTypeField.
     *
     * @param TagTranslation $tagTranslation
     * @param Document $document
     */
    public function __construct(TagTranslation $tagTranslation, Document $document)
    {
        $this->document = $document;
        $this->tagTranslation = $tagTranslation;
    }

    /**
     *
     */
    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->tagTranslation = null;
        }
    }

    /**
     * Gets the value of document.
     *
     * @return Document
     */
    public function getDocument(): Document
    {
        return $this->document;
    }

    /**
     * Sets the value of document.
     *
     * @param Document $document the document
     *
     * @return self
     */
    public function setDocument(Document $document): TagTranslationDocuments
    {
        $this->document = $document;

        return $this;
    }

    /**
     * @return TagTranslation
     */
    public function getTagTranslation(): TagTranslation
    {
        return $this->tagTranslation;
    }

    /**
     * @param TagTranslation $tagTranslation
     * @return TagTranslationDocuments
     */
    public function setTagTranslation(TagTranslation $tagTranslation): TagTranslationDocuments
    {
        $this->tagTranslation = $tagTranslation;
        return $this;
    }
}
