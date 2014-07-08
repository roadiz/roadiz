<?php 
namespace RZ\Renzo\Core\Viewers;

use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Kernel;

class DocumentViewer 
{
	private $document;

	/**
	 * @return RZ\Renzo\Core\Entities\Document
	 */
	public function getDocument() {
	    return $this->document;
	}

	function __construct( Document $document )
	{
		$this->document = $document;
	}

	/**
	 * Generate a resampled document Url
	 * 
	 * - width
	 * - height 
	 * - crop ({w}x{h}, for example : 100x200)
	 * - grayscale / greyscale (boolean)
	 * - quality (1-100)
	 * - background (hexadecimal color without #)
	 * - progressive (boolean)
	 * 
	 * @param  array $args
	 * @return string Url
	 */
	public function getDocumentUrlByArray( $args = null )
	{
		if ($args === null) {
			return Kernel::getInstance()->getRequest()->getBaseUrl().'/files/'.$this->getDocument()->getRelativeUrl();
		}
		else {

			$slirArgs = array();

			if (!empty($args['width'])) {
				$slirArgs['w'] = 'w'.(int)$args['width'];
			}
			if (!empty($args['height'])) {
				$slirArgs['h'] = 'h'.(int)$args['height'];
			}
			if (!empty($args['crop'])) {
				$slirArgs['c'] = 'c'.strip_tags($args['crop']);
			}
			if ((!empty($args['grayscale']) && $args['grayscale'] == true) ||
				(!empty($args['greyscale']) && $args['greyscale'] == true)) {
				$slirArgs['g'] = 'g1';
			}
			if (!empty($args['quality'])) {
				$slirArgs['q'] = 'q'.(int)$args['quality'];
			}
			if (!empty($args['background'])) {
				$slirArgs['b'] = 'b'.strip_tags($args['background']);
			}
			if (!empty($args['progressive']) && $args['progressive'] == true) {
				$slirArgs['p'] = 'p1';
			}

			return Kernel::getInstance()->getUrlGenerator()->generate('SLIRProcess', array(
			    'queryString' => implode('-', $slirArgs),
			    'filename' => $this->getDocument()->getRelativeUrl()
			));
		}
	}
}