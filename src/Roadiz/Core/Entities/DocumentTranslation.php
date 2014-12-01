<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file DocumentTranslation.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * DocumentTranslation.
 *
 * @Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @Table(name="documents_translations", uniqueConstraints={@UniqueConstraint(columns={"document_id", "translation_id"})})
 */
class DocumentTranslation extends AbstractEntity
{
    /**
     * @Column(type="string", nullable=true)
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
     * @Column(type="text", nullable=true)
     */
    private $copyright;
    /**
     * @return string
     */
    public function getCopyright()
    {
        return $this->copyright;
    }
    /**
     * @param string $copyright
     *
     * @return $this
     */
    public function setCopyright($copyright)
    {
        $this->copyright = $copyright;

        return $this;
    }

    /**
     * @ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Translation", inversedBy="documentTranslations", fetch="EXTRA_LAZY")
     * @JoinColumn(name="translation_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $translation;

    /**
     * @return RZ\Roadiz\Core\Entities\Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }
    /**
     * @param RZ\Roadiz\Core\Entities\Translation $translation
     */
    public function setTranslation(Translation $translation)
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * @ManyToOne(targetEntity="Document", inversedBy="documentTranslations", fetch="EXTRA_LAZY")
     * @JoinColumn(name="document_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $document;

    /**
     * @return RZ\Roadiz\Core\Entities\Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Document $document
     */
    public function setDocument($document)
    {
        $this->document = $document;

        return $this;
    }
}
