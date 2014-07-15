<?php 
namespace Themes\Rozier\Widgets;

use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;
use Themes\Rozier\Widgets\AbstractWidget;

/**
* 
*/
class NodeTreeWidget extends AbstractWidget
{
	
	/**
	 * @param  RZ\Renzo\Core\Entities\Node $parent Parent node or NULL to get from root
	 * @return array Twig assignation array
	 */
	public function getNodeTreeAssignationForParent( Node $parent = null, Translation $translation = null)
	{
		$translation = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Translation')
				->findOneBy(array('defaultTranslation'=>true));

		if ($translation === null) {
			return Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Node')
					->findByParentWithDefaultTranslation($parent);
		}
		else {
			return Kernel::getInstance()->em()
					->getRepository('RZ\Renzo\Core\Entities\Node')
					->findByParentWithTranslation($parent, $translation);
		}
		
	}
}