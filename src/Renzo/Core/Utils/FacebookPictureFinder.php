<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file FacebookPictureFinder.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Utils;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
/**
 * Util to grab a facebook profile picture from userAlias.
 */
class FacebookPictureFinder
{
    protected $facebookUserAlias;
    protected $response;

    /**
     * @param string $facebookUserAlias
     */
    public function __construct($facebookUserAlias)
    {
        $this->facebookUserAlias = $facebookUserAlias;
    }

    /**
     * @return string Facebook profile image URL or FALSE
     */
    public function getPictureUrl()
    {
        try {
            $client = new Client();
            $req = $client->get('http://graph.facebook.com/'.$this->facebookUserAlias.'/picture?redirect=false&width=200&height=200');
            $this->response = $req->send();
            $json = json_decode($this->response->getBody(), true);

            return $json['data']['url'];
        } catch (ClientErrorResponseException $e) {
            return false;
        }
    }
}
