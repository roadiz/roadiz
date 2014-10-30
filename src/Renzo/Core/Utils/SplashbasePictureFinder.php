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
use RZ\Renzo\Core\Entities\Document;

use GuzzleHttp\Exception\ClientErrorResponseException;
use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use Pimple\Container;

/**
 * Util to grab a facebook profile picture from userAlias.
 */
class SplashbasePictureFinder extends AbstractEmbedFinder
{
    private $client;
    protected static $platform = 'splashbase';

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getRandom()
    {
        try {
            $response = $this->client->get('http://www.splashbase.co/api/v1/images/random');
            $this->feed = $response->json();

            if (false !== strpos($this->feed['url'], '.jpg')) {

                $this->embedId = $this->feed['id'];

                return $this->feed;
            } else {
                $this->feed = false;
                return false;
            }

        } catch (ClientErrorResponseException $e) {
            $this->feed = false;
            return false;
        }
    }


    /**
     * {@inheritdoc}
     */
    public function getSource($args = array())
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getMediaFeed($search = null)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFeed($searchTerm, $author, $maxResults = 15)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getMediaTitle()
    {
        return "";
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaDescription()
    {
        return "";
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaCopyright()
    {
        return $this->feed['copyright'].' — '.$this->feed['site'];
    }

    /**
     * {@inheritdoc}
     */
    public function getThumbnailURL()
    {
        if (null === $this->feed) {
            $this->getRandom();

            if (false === $this->feed) {
                return false;
            }
        }

        return $this->feed['url'];
    }
}
