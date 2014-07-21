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
	protected $parentNode = null;
	protected $nodes = null;
	protected $translation = null;


	/**
	 * @param Symfony\Component\HttpFoundation\Request
	 * @param RZ\Renzo\CMS\Controller\AppController Referee controller to get Twig, security context from.
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

	public function getTranslation()
	{
		return $this->translation;
	}
	public function getNodes()
	{
		return $this->nodes;
	}
}