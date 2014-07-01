<?php 

namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;
/**
* 	
*/
class TranslationHandler {
	private $translation = null;

	/**
	 * @return RZ\Renzo\Core\Entities\Translation
	 */
	public function getTranslation() {
	    return $this->translation;
	}
	
	/**
	 * @param RZ\Renzo\Core\Entities\Translation $newnode
	 */
	public function setTranslation($translation) {
	    $this->translation = $translation;
	
	    return $this;
	}

	public function __construct( Translation $translation )
	{
		$this->translation = $translation;
	}

	public function makeDefault()
	{
		$defaults = Kernel::getInstance()->em()
			->getRepository('RZ\Renzo\Core\Entities\Translation')
			->findBy(array('defaultTranslation'=>true));

		foreach ($defaults as $default) {
			$default->setDefaultTranslation(false);
		}
		$this->getTranslation()->setDefaultTranslation(true);
		Kernel::getInstance()->em()->flush();
	}
}