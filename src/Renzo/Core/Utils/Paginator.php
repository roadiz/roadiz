<?php 
namespace RZ\Renzo\Core\Utils;


use Doctrine\ORM\EntityManager;
/**
 *  A simple paginator class to filter entities with limit and search				
 */
class Paginator 
{
	protected $itemsPerPage;
	protected $itemCount;
	protected $entityName;
	protected $em;
	
	/**
	 * 
	 * @param Doctrine\ORM\EntityManager  $em Entity manager
	 * @param string  $entityName Full qualified entity classname
	 * @param integer $itemPerPages
	 */
	function __construct( EntityManager $em, $entityName, $itemPerPages = 20)
	{
		$this->em = $em;
		$this->entityName = $entityName;
		$this->setItemsPerPage( $itemPerPages );

		if ($this->entityName == "") {
			throw new \RuntimeException("Entity name could not be empty", 1);
		}
		if ($this->itemsPerPage < 1) {
			throw new \RuntimeException("Items par page could not be lesser than 1.", 1);
		}
	}

	public function countAll( array $criteria = array() )
	{	
		$qb = $this->em->createQueryBuilder();

		$qb->select('count(item.id)');
		$qb->from($this->entityName, 'item');

		$query = $qb->getQuery();
        try {
            return $query->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return 0;
        }
	}

	public function findByAtPage( array $criteria = array(), array $order = array(), $page = 1 )
	{
		return $this->em->getRepository($this->entityName)
					->findBy( $criteria, $order, $this->getItemsPerPage(), $this->getItemsPerPage() * ($page - 1));
	}

	/**
	 * 
	 * @param integer $itemsPerPage 
	 */
	public function setItemsPerPage( $itemsPerPage )
	{
		$this->itemsPerPage = $itemsPerPage;
		return $this;
	}
	/**
	 * 
	 * @return integer $itemsPerPage 
	 */
	public function getItemsPerPage()
	{
		return $this->itemsPerPage;
	}

}