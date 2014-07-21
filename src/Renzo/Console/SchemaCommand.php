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

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

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
			$text .= static::refreshMetadata();
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

	/**
	 * Refresh doctrine caches and proxies
	 * @return void
	 */
	public static function refreshMetadata()
	{
		$text = '';
		// Empty result cache
		$cacheDriver = Kernel::getInstance()->em()->getConfiguration()->getResultCacheImpl();
        if ($cacheDriver !== null) {
        	$text .= 'Result cache: '.$cacheDriver->getNamespace().' — ';
            $text .= $cacheDriver->deleteAll() ? 'OK' : 'FAIL';
            $text .= PHP_EOL;
        }
        else {
	        // Empty hydratation cache
			$cacheDriver = Kernel::getInstance()->em()->getConfiguration()->getHydrationCacheImpl();
	        if ($cacheDriver !== null) {
	        	$text .= 'Hydratation cache: '.$cacheDriver->getNamespace().' — ';
	            $text .= $cacheDriver->deleteAll() ? 'OK' : 'FAIL';
	            $text .= PHP_EOL;
	        }else {

		        // Empty query cache
				$cacheDriver = Kernel::getInstance()->em()->getConfiguration()->getQueryCacheImpl();
		        if ($cacheDriver !== null) {
		        	$text .= 'Query cache: '.$cacheDriver->getNamespace().' — ';
		            $text .= $cacheDriver->deleteAll() ? 'OK' : 'FAIL';
		            $text .= PHP_EOL;
		        }
		        else {

			        // Empty metadata cache
					$cacheDriver = Kernel::getInstance()->em()->getConfiguration()->getMetadataCacheImpl();
			        if ($cacheDriver !== null) {
			        	$text .= 'Metadata cache: '.$cacheDriver->getNamespace().' — ';
			            $text .= $cacheDriver->deleteAll() ? 'OK' : 'FAIL';
			            $text .= PHP_EOL;
			        }
		        }
	        }
        }

        /*
         * Recreate proxies files
         */
		$fs = new Filesystem();
		$finder = new Finder();
		$finder->files()->in(RENZO_ROOT . '/sources/Proxies');
		$fs->remove($finder);

        $meta = Kernel::getInstance()->em()->getMetadataFactory()->getAllMetadata();
		$proxyFactory = Kernel::getInstance()->em()->getProxyFactory();
		$proxyFactory->generateProxyClasses($meta, RENZO_ROOT . '/sources/Proxies');
		$text .= '<info>Doctrine proxiy classes has been purged…</info>'.PHP_EOL;
	}

	/**
	 * Update database schema
	 * 
	 * @return boolean
	 */
	public static function updateSchema()
	{
		static::refreshMetadata();

		$tool = new \Doctrine\ORM\Tools\SchemaTool( Kernel::getInstance()->em() );
		$meta = Kernel::getInstance()->em()->getMetadataFactory()->getAllMetadata();
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