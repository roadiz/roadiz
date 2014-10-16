<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file SplashbasePictureFinder.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientErrorResponseException;

/**
 * Util to grab a facebook profile picture from userAlias.
 */
class SplashbasePictureFinder
{
    private $client;

    public function __construct(){
        $this->client = new Client();
    }

    public function getRandom()
    {
        try {
            $this->response = $this->client->get('http://www.splashbase.co/api/v1/images/random');
            $json = $this->response->json();

            if (false !== strpos($json['url'], '.jpg')) {
                return $json;
            } else {
                return false;
            }

        } catch (ClientErrorResponseException $e) {
            return false;
        }
    }

    public function getSource($sourceId)
    {
        try {
            $this->response = $this->client->get('http://www.splashbase.co/api/v1/sources/'.(int) $sourceId);

            return $this->response->json();
        } catch (ClientErrorResponseException $e) {
            return false;
        }
    }
}
