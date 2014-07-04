<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\PersistableObject;

/**
 * @Entity
 * @Table(name="tags_translations", uniqueConstraints={@UniqueConstraint(columns={"tag_id", "translation_id"})})
 */
class TagTranslation extends PersistableObject
{
	
	/**
	 * @Column(type="string")
	 */
	private $name;
	/**
	 * @return
	 */
	public function getName() {
	    return $this->name;
	}
	/**
	 * @param $newnodeName 
	 */
	public function setName($name) {
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
	public function getDescription() {
	    return $this->description;
	}
	/**
	 * @param string $newnodeName
	 */
	public function setDescription($description) {
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
	public function getTag() {
	    return $this->tag;
	}
	/**
	 * @param Tag $newtag
	 */
	public function setTag($tag) {
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
	public function getTranslation() {
	    return $this->translation;
	}
	/**
	 * @param Translation $newtranslation
	 */
	public function setTranslation($translation) {
	    $this->translation = $translation;
	    return $this;
	}
	

	public function __construct( Tag $original, Translation $translation )
    {
    	parent::__construct();

    	$this->setTag($original);
    	$this->setTranslation($translation);
    }
}