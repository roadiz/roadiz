<?php 
namespace RZ\Renzo\Core\ListManagers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Doctrine\ORM\EntityManager;
/**
 * Perform basic filtering and search over entity listings.
 * 
 */
class TagListManager extends EntityListManager
{

	/**
	 * 
	 * @param Symfony\Component\HttpFoundation\Request $request
	 * @param Doctrine\ORM\EntityManager $_em 
	 * @param string $entityName
	 * @param array $preFilters Initial filters
	 * @param array $preOrdering Initial order
	 */
	function __construct( Request $request, EntityManager $_em, $preFilters = array(), $preOrdering = array() )
	{
		parent::__construct($request, $_em, 'RZ\Renzo\Core\Entities\Tag', $preFilters, $preOrdering);
	}

	/**
	 * Return filtered entities.
	 * 
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getEntities()
	{
		try {
			if ($this->searchPattern != '') {
				return $this->_em
					->getRepository('RZ\Renzo\Core\Entities\TagTranslation')
					->searchBy($this->searchPattern, $this->filteringArray, $this->orderingArray);
			}
			else {
				return $this->paginator->findByAtPage($this->filteringArray, $this->currentPage);
			}
		}
		catch(\Exception $e){
			return null;
		}
	}
}