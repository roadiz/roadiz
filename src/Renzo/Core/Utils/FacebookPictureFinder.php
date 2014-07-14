<?php 

namespace RZ\Renzo\Core\Utils;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
/**
* 
*/
class FacebookPictureFinder
{
	protected $facebookUserAlias;
	protected $response;

	function __construct( $facebookUserAlias )
	{
		$this->facebookUserAlias = $facebookUserAlias;
	}

	public function getPictureUrl()
	{
		try {
			$client = new Client();
			$req = $client->get('http://graph.facebook.com/'.$this->facebookUserAlias.'/picture?redirect=false&width=200&height=200');
			$this->response = $req->send();

			$json = json_decode($this->response->getBody(), true);
			return $json['data']['url'];                 // {"type":"User"...'
		}
		catch (ClientErrorResponseException $e){
			return false;
		}
	}
}