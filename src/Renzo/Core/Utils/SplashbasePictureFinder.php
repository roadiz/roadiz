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

    }

    /**
     * {@inheritdoc}
     */
    public function getMediaDescription()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getThumbnailURL()
    {
        $this->getRandom();

        if (false !== $this->feed) {
            return $this->feed['url'];
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createDocumentFromFeed(Container $container)
    {
        $url = $this->downloadThumbnail();

        if (false !== $url &&
            false !== $this->feed) {

            $existingDocument = $container['em']->getRepository('RZ\Renzo\Core\Entities\Document')
                                                ->findOneBy(array('filename'=>$url));
            if (null !== $existingDocument) {
                throw new EntityAlreadyExistsException('embed.document.already_exists');
            }

            $document = new Document();

            if (false !== $url) {

                /*
                 * Move file from documents file root to its folder.
                 */
                $document->setFilename($url);
                $document->setMimeType('image/jpeg');
                $document->setCopyright($this->feed['copyright'].' — '.$this->feed['site']);

                if (!file_exists(Document::getFilesFolder().'/'.$document->getFolder())) {
                    mkdir(Document::getFilesFolder().'/'.$document->getFolder());
                }
                rename(Document::getFilesFolder().'/'.$url, $document->getAbsolutePath());
            }

            $container['em']->persist($document);
            $container['em']->flush();

            return $document;
        } else {
            throw new \Exception('no.random.document.found');
        }
    }
}
