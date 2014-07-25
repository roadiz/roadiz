<?php 


namespace RZ\Renzo\Console;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Theme;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Console\SchemaCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
* 
*/
class RequirementsCommand extends Command {
	private $dialog;
	
	protected function configure()
	{
		$this
			->setName('requirements')
			->setDescription('Test server requirements.')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->dialog = $this->getHelperSet()->get('dialog');
		$text="";
		
		$text .= $this->testPHPVersion('5.4');
		$text .= $this->testExtension('intl');
		$text .= $this->testExtension('ereg');
		$text .= $this->testExtension('session');
		$text .= $this->testExtension('json');
		$text .= $this->testExtension('zip');
		$text .= $this->testExtension('date');
		$text .= $this->testExtension('gd');
		$text .= $this->testExtension('imap');
		$text .= $this->testExtension('curl');

		$text .= $this->testPHPIntValue('memory_limit', '64');
		$text .= $this->testPHPIntValue('post_max_size', '16');
		$text .= $this->testPHPIntValue('upload_max_filesize', '16');

		$text .= $this->methodExists('gettext');
		$text .= $this->folderWritable(RENZO_ROOT);

		$output->writeln($text);
	}


	protected function testPHPIntValue($name, $expected) {

		$intValue = (int)(str_replace(array('s','K','M','G'), array('','','',''), ini_get($name)));

		if ($intValue < $expected) {
			return '<info>'.$name.'</info> : '.ini_get($name).'  Excepted : '.$expected.' — <error>Fail</error>'.PHP_EOL;
		}

		return '<info>'.$name.'</info> : '.ini_get($name).' — Excepted : '.$expected.''.PHP_EOL;
	}
	protected function methodExists($name, $mandatory = true)
	{
		return '<info>Method '.$name.'()</info> — '.(function_exists($name) == true && $mandatory == true ? 'OK' : '<error>Fail</error>').''.PHP_EOL;
	}
	protected function folderWritable($filename)
	{
		return '<info>Folder “'.$filename.'”</info> — '.(is_writable($filename) == true ? 'Writable' : '<error>Not writable</error>').''.PHP_EOL;
	}

	protected function testExtension($name)
	{
		if (!extension_loaded($name)) {
		    return '<info>Extension '.$name.'</info> is not installed — <error>Fail</error>'.PHP_EOL;
		}
		else {
			return '<info>Extension '.$name.'</info> is installed — OK'.PHP_EOL;
		}
	}

	protected function testPHPVersion($version)
	{
		if (version_compare(phpversion(), $version, '<')) {
		    return '<info>PHP</info> version is too old — <error>Fail</error>'.PHP_EOL;
		}
		else {
			return '<info>PHP</info> version (v'.phpversion().') — OK'.PHP_EOL;
		}
	}
}