<?php 
namespace Themes\Rozier\Widgets;

use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;
use Themes\Rozier\Widgets\AbstractWidget;

use Symfony\Component\HttpFoundation\Request;

/**
* 
*/
class TagTreeWidget extends AbstractWidget
{
	protected $parentTag =  null;
	protected $tags =       null;
	protected $translation = null;


	/**
	 * @param Request $request
	 * @param AppController  $refereeController 
	 * @param Tag  $parent 
	 * @param Translation  $translation
	 */
	public function __construct(  Request $request, $refereeController, Tag $parent = null, Translation $translation = null )
	{
		parent::__construct( $request, $refereeController );

		$this->parentTag = $parent;
		$this->translation = $translation;

		$this->getTagTreeAssignationForParent();
	}

	/**
	 * @param  RZ\Renzo\Core\Entities\Tag $parent Parent tag or NULL to get from root
	 * @return array Twig assignation array
	 */
	protected function getTagTreeAssignationForParent( )
	{
		if ($this->translation === null) {
			$this->translation = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Translation')
					->findOneBy(array('defaultTranslation'=>true));
		}

		$this->tags = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Tag')
				->findByParentWithTranslation($this->parentTag, $this->translation);
	}

	/**
	 * @param  RZ\Renzo\Core\Entities\Tag $parent
	 * @return array
	 */
	public function getChildrenTags( Tag $parent )
	{
		if ($this->translation === null) {
			$this->translation = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Translation')
					->findOneBy(array('defaultTranslation'=>true));
		}
		if ($parent !== null) {
			return $this->tags = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Tag')
					->findByParentWithTranslation($parent, $this->translation);
		}
		return null;
	}

	public function getRootTag()
	{
		return $this->parentTag;
	}
	public function getTranslation()
	{
		return $this->translation;
	}
	public function getTags()
	{
		return $this->tags;
	}
}