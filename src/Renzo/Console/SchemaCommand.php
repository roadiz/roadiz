<?php 


namespace RZ\Renzo\Console;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

/**
* 
*/
class SchemaCommand extends Command {
	private $dialog;
	
	protected function configure()
	{
		$this
			->setName('schema')
			->setDescription('Manage database schema')
			->addOption(
			   'refresh',
			   null,
			   InputOption::VALUE_NONE,
			   'Refresh doctrine metadata cache'
			)
			/*->addOption(
			   'drop',
			   null,
			   InputOption::VALUE_NONE,
			   'Drop current database'
			)*/
			->addOption(
			   'update',
			   null,
			   InputOption::VALUE_NONE,
			   'Update current database schema'
			)
			->addOption(
			   'execute',
			   null,
			   InputOption::VALUE_NONE,
			   'Apply changes'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->dialog = $this->getHelperSet()->get('dialog');
		$text="";

		if ($input->getOption('refresh')) {
			static::refreshMetadata();
			$text .= '<info>Your database metadata cache has been purged…</info>'.PHP_EOL;
		}

		if ($input->getOption('update')) {
			
			$sql = static::getUpdateSchema();
			$count = count($sql);

			if ($count > 0) {
				/*
				 * Print changes
				 */
				for($i=0; $i<$count; $i++) {
				    $text .= $sql[$i].PHP_EOL;
				}
				$text .= '<info>'.$count.'</info> change(s) in your database schema… Use <info>--execute</info> to apply'.PHP_EOL;

				/*
				 * If execute option = Perform changes
				 */
				if ($input->getOption('execute')) {
					if ($this->dialog->askConfirmation(
							$output,
							'<question>Are you sure to update your database schema?</question> : ',
							false
						)) {
				
						if (static::updateSchema()) {
							$text .= '<info>Schema updated…</info>'.PHP_EOL;
						}
					}
				}
			}
			else {
				$text .= '<info>Your database schema is already up to date…</info>'.PHP_EOL;
			}
		}

		$output->writeln($text);
	}

	public static function refreshMetadata()
	{
		/*$cacheDriver = Kernel::getInstance()->em()->getMetadataFactory()->getCacheDriver();
        if ($cacheDriver !== null) {
            $cacheDriver->flushAll();
        }*/

        $meta = Kernel::getInstance()->em()->getMetadataFactory()->getAllMetadata();
		$proxyFactory = Kernel::getInstance()->em()->getProxyFactory();
		$proxyFactory->generateProxyClasses($meta, RENZO_ROOT . '/sources/Proxies');
	}

	/**
	 * Update database schema
	 * 
	 * @return 
	 */
	public static function updateSchema()
	{
		static::refreshMetadata();

		//Kernel::getInstance()->em()->getMetadataFactory()->setMetadataFor( 
		//	'RZ\Renzo\Core\Entities\NodesSources', 
		//	\RZ\Renzo\Inheritance\Doctrine\DataInheritanceEvent::getNodesSourcesMetadata() );

		$tool = new \Doctrine\ORM\Tools\SchemaTool( Kernel::getInstance()->em() );
		$meta = Kernel::getInstance()->em()->getMetadataFactory()->getAllMetadata();

		$proxyFactory = Kernel::getInstance()->em()->getProxyFactory();
		$proxyFactory->generateProxyClasses($meta, RENZO_ROOT . '/sources/Proxies');

		$sql = $tool->getUpdateSchemaSql($meta);

		foreach($sql as $statement) {
		    Kernel::getInstance()->em()->getConnection()->exec( $statement );
		}

		return true;
	}
	public static function getUpdateSchema()
	{
		static::refreshMetadata();

		$tool = new \Doctrine\ORM\Tools\SchemaTool( Kernel::getInstance()->em() );
		$meta = Kernel::getInstance()->em()->getMetadataFactory()->getAllMetadata();
		return $tool->getUpdateSchemaSql($meta);
	}
}