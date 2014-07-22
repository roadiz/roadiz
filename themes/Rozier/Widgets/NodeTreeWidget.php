<?php 
namespace Themes\Rozier\Widgets;

use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;
use Themes\Rozier\Widgets\AbstractWidget;

use Symfony\Component\HttpFoundation\Request;

/**
* 
*/
class NodeTreeWidget extends AbstractWidget
{
	protected $parentNode =  null;
	protected $nodes =       null;
	protected $translation = null;


	/**
	 * @param Request $request
	 * @param AppController  $refereeController 
	 * @param Node  $parent 
	 * @param Translation  $translation
	 */
	public function __construct(  Request $request, $refereeController, Node $parent = null, Translation $translation = null )
	{
		parent::__construct( $request, $refereeController );

		$this->parentNode = $parent;
		$this->translation = $translation;

		$this->getNodeTreeAssignationForParent();
	}

	/**
	 * @param  RZ\Renzo\Core\Entities\Node $parent Parent node or NULL to get from root
	 * @return array Twig assignation array
	 */
	protected function getNodeTreeAssignationForParent( )
	{
		if ($this->translation === null) {
			$this->translation = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Translation')
					->findOneBy(array('defaultTranslation'=>true));
		}

		$this->nodes = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Node')
				->findByParentWithTranslation($this->parentNode, $this->translation);
	}

	/**
	 * @param  RZ\Renzo\Core\Entities\Node $parent
	 * @return array
	 */
	public function getChildrenNodes( Node $parent )
	{
		if ($this->translation === null) {
			$this->translation = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Translation')
					->findOneBy(array('defaultTranslation'=>true));
		}
		if ($parent !== null) {
			return $this->nodes = Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Node')
					->findByParentWithTranslation($parent, $this->translation);
		}
		return null;
	}

	public function getTranslation()
	{
		return $this->translation;
	}
	public function getNodes()
	{
		return $this->nodes;
	}
}