<?php 

namespace Themes\Install\Controllers;

/**
* 	
*/
class Requirements
{
	protected $totalChecks = 0;
	protected $successChecks = 0;
	
	public function isTotalSuccess()
	{
		return $this->totalChecks <= $this->successChecks;
	}

	public function getRequirements()
	{
		$checks = array();


		$checks['php_version'] = array(
			'status'=>$this->testPHPVersion('5.4'),
			'version_minimum' => '5.4',
			'found' => phpversion(),
			'message' => 'Your PHP version is outdated, you must update it.'
		);
		if ($checks['php_version']['status']) { $this->successChecks++; }
		$this->totalChecks++;

		$checks['intl'] = array(
			'status'=>$this->testExtension('intl'),
			'extension' => true,
			'message' => 'Intl extension is needed for translations.'
		);
		if ($checks['intl']['status']) { $this->successChecks++; }
		$this->totalChecks++;

		$checks['session'] = array(
			'status'=>$this->testExtension('session'),
			'extension' => true,
			'message' => 'You must enable PHP sessions.'
		);
		if ($checks['session']['status']) { $this->successChecks++; }
		$this->totalChecks++;

		$checks['json'] = array(
			'status'=>$this->testExtension('json'),
			'extension' => true,
			'message' => 'JSON library is needed for configuration handling.'
		);
		if ($checks['json']['status']) { $this->successChecks++; }
		$this->totalChecks++;

		$checks['zip'] = array(
			'status'=>$this->testExtension('zip'),
			'extension' => true,
			'message' => 'ZIP extension is needed.'
		);
		if ($checks['zip']['status']) { $this->successChecks++; }
		$this->totalChecks++;

		$checks['date'] = array(
			'status'=>$this->testExtension('date'),
			'extension' => true,
			'message' => 'Date extension is needed.'
		);
		if ($checks['date']['status']) { $this->successChecks++; }
		$this->totalChecks++;

		$checks['gd'] = array(
			'status'=>$this->testExtension('gd'),
			'extension' => true,
			'message' => 'GD library must be installed.'
		);
		if ($checks['gd']['status']) { $this->successChecks++; }
		$this->totalChecks++;

		$checks['imap'] = array(
			'status'=>$this->testExtension('imap'),
			'extension' => true,
			'message' => 'Imap extension is needed for Newsletter handling.'
		);
		if ($checks['imap']['status']) { $this->successChecks++; }
		$this->totalChecks++;

		$checks['curl'] = array(
			'status'=>$this->testExtension('curl'),
			'extension' => true,
			'message' => 'cUrl extension is needed for API requests.'
		);
		if ($checks['curl']['status']) { $this->successChecks++; }
		$this->totalChecks++;

		$checks['memory_limit'] = array(
			'status'=>$this->testPHPIntValue('memory_limit', '64M'),
			'value_minimum' => '64M',
			'found' => ini_get('memory_limit'),
			'message' => 'Your PHP configuration has a too low value for “upload_max_filesize”'
		);
		if ($checks['memory_limit']['status']) { $this->successChecks++; }
		$this->totalChecks++;

		$checks['post_max_size'] = array(
			'status'=>$this->testPHPIntValue('post_max_size', '16M'),
			'value_minimum' => '16M',
			'found' => ini_get('post_max_size'),
			'message' => 'Your PHP configuration has a too low value for “upload_max_filesize”'
		);
		if ($checks['post_max_size']['status']) { $this->successChecks++; }
		$this->totalChecks++;

		$checks['upload_max_filesize'] = array(
			'status'=>$this->testPHPIntValue('upload_max_filesize', '16M'),
			'value_minimum' => '16M',
			'found' => ini_get('upload_max_filesize'),
			'message' => 'Your PHP configuration has a too low value for “upload_max_filesize”'
		);
		if ($checks['upload_max_filesize']['status']) { $this->successChecks++; }
		$this->totalChecks++;

		$checks['project_folder_writable'] = array(
			'status'=>$this->folderWritable(RENZO_ROOT),
			'folder' => RENZO_ROOT,
			'mod' => fileperms(RENZO_ROOT),
			'message' => 'Project folder is not writable by PHP, you must change its permissions.'
		);
		if ($checks['project_folder_writable']['status']) { $this->successChecks++; }
		$this->totalChecks++;

		$checks['files_folder_writable'] = array(
			'status'=>$this->folderWritable(RENZO_ROOT.'/files'),
			'folder' => RENZO_ROOT.'/files',
			'mod' => fileperms(RENZO_ROOT.'/files'),
			'message' => 'Storage folder is not writable by PHP, you must change its permissions.'
		);
		if ($checks['files_folder_writable']['status']) { $this->successChecks++; }
		$this->totalChecks++;

		return $checks;
	}

	protected function testPHPIntValue($name, $expected) {

		$intValue = (int)(str_replace(array('s','K','M','G'), array('','','',''), ini_get($name)));
		if ($intValue < $expected) {
			return false;
		}
		return true;
	}
	protected function methodExists($name)
	{
		return (function_exists($name) == true) ? (true) : (false);
	}
	protected function folderWritable($filename)
	{
		return is_writable($filename) == true ? true : false;
	}
	protected function testExtension($name)
	{
		return extension_loaded($name);
	}
	protected function testPHPVersion($version)
	{
		return !version_compare(phpversion(), $version, '<');
	}
}