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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientErrorResponseException;

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
            $this->response = $client->get('http://graph.facebook.com/'.$this->facebookUserAlias.'/picture?redirect=false&width=200&height=200');
            $json = $this->response->json();

            return $json['data']['url'];
        } catch (ClientErrorResponseException $e) {
            return false;
        }
    }
}
