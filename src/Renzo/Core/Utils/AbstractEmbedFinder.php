<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file AbstractEmbedFinder.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Utils;

use RZ\Renzo\Core\Entities\Document;
use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use Pimple\Container;

/**
 * abstract class to handle external media via their Json API.
 */
abstract class AbstractEmbedFinder
{
    protected $feed = null;
    protected $embedId;

    protected static $platform = 'abstract';

    /**
     * Tell if embed media exists after its API feed.
     *
     * @return boolean
     */
    public function exists()
    {
        if ($this->getFeed() !== false) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Crawl and parse an API json feed for current embedID.
     *
     * @return array | false
     */
    public function getFeed()
    {
        if (null === $this->feed) {
            $this->feed = $this->getVideoFeed();
            if (false !== $this->feed) {
                $this->feed = json_decode($this->feed, true);
            }
        }
        return $this->feed;
    }

    /**
     * Get embed media source URL.
     *
     * @param array $args
     *
     * @return string
     */
    public abstract function getSource($args = array());

    /**
     * Crawl an embed API to get a Json feed.
     *
     * @param string | null $search
     *
     * @return string
     */
    public abstract function getVideoFeed($search = null);

    /**
     * Crawl an embed API to get a Json feed against a search query.
     *
     * @param string  $searchTerm
     * @param string  $author
     * @param integer $maxResults
     *
     * @return string
     */
    public abstract function getSearchFeed($searchTerm, $author, $maxResults=15);

    /**
     * Compose an HTML iframe for viewing embed media.
     *
     * @param  array | null $args
     *
     * @return string
     */
    public function getIFrame(&$args=null)
    {
        $attributes = array();

        $attributes['src'] = $this->getSource($this->embedId);

        if (isset($args['width'])) {
            $attributes['width'] = $args['width'];

            /*
             * Default height is defined to 16:10
             */
            if (!isset($args['height'])) {
                $attributes['height'] = (int)(($args['width']*10)/16);
            }
        }
        if (isset($args['height'])) {
            $attributes['height'] = $args['height'];
        }
        if (isset($args['title'])) {
            $attributes['title'] = $args['title'];
        }
        if (isset($args['id'])) {
            $attributes['id'] = $args['id'];
        }

        if (isset($args['autoplay']) && $args['autoplay'] == true) {
            $attributes['src'] .= '&autoplay=1';
        }
        if (isset($args['controls']) && $args['controls'] == false) {
            $attributes['src'] .= '&controls=0';
        }

        $attributes['frameborder'] = "0";
        $attributes['webkitAllowFullScreen'] = "1";
        $attributes['mozallowfullscreen'] = "1";
        $attributes['allowFullScreen'] = "1";

        /* <iframe id="video_<?php echo $i ?>" frameborder="0" allowfullscreen="1" title="YouTube video player" width="586" height="360"
        // src="http://www.youtube.com/embed/<?php echo $production->embed ?>"></iframe>*/

        $htmlTag = '<iframe';
        foreach ($attributes as $key => $value) {
            if ($value == '') {
                $htmlTag .= ' '.$key;
            }
            else {
                $htmlTag .= ' '.$key.'="'.addslashes($value).'"';
            }
        }
        $htmlTag .= ' ></iframe>';

        return $htmlTag;
    }
    /**
     * Create a Document from an embed media
     *
     * @param Pimple\Container $container description
     *
     * @return Document
     */
    public function createDocumentFromFeed(Container $container)
    {
        $url = $this->downloadThumbnail();

        if (false !== $url) {
            $existingDocument = $container['em']->getRepository('RZ\Renzo\Core\Entities\Document')
                                                ->findOneBy(array('filename'=>$url));
        } else {
            $existingDocument = $container['em']->getRepository('RZ\Renzo\Core\Entities\Document')
                                                ->findOneBy(array('embedId'=>$this->embedId));
        }

        if (null !== $existingDocument) {
            throw new EntityAlreadyExistsException('embed.document.already_exists');
        }

        $document = new Document();
        $document->setName($this->getMediaTitle());
        $document->setDescription($this->getMediaDescription());

        $document->setEmbedId($this->embedId);
        $document->setEmbedPlatform(static::$platform);

        if (false !== $url) {
            /*
             * Move file from documents file root to its folder.
             */
            $document->setFilename($url);
            mkdir(Document::getFilesFolder().'/'.$document->getFolder());
            rename(Document::getFilesFolder().'/'.$url, $document->getAbsolutePath());
        }

        $container['em']->persist($document);
        $container['em']->flush();

        return $document;
    }

    /**
     * Get media title from feed.
     *
     * @return string
     */
    public abstract function getMediaTitle();

    /**
     * Get media description from feed.
     *
     * @return string
     */
    public abstract function getMediaDescription();

    /**
     * Get media thumbnail external URL from its feed.
     *
     * @return string
     */
    public abstract function getThumbnailURL();

    /**
     * Send a CURL request and get its string output.
     *
     * @param string $url
     *
     * @return string|false
     */
    public function downloadFeedFromAPI($url)
    {
        $data = '';
        /* --------------------
         * Get files from github
         * -------------------- */
        if (!function_exists('curl_init')) {
            return false;
        }

        // initialisation de la session
        $ch = curl_init();


        /* Check if cURL is available */
        if ($ch !== FALSE)
        {
            // configuration des options
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
            //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.116 Safari/537.36 FirePHP/4Chrome");
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

            // exécution de la session
            $data = curl_exec($ch);

            if ($data !== null && $data != '') {

                // fermeture des ressources
                curl_close($ch);

                return $data;
            } else {
                // fermeture des ressources
                curl_close($ch);

                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Download a picture from the embed media platform
     * to get a thumbnail.
     *
     * @return string|false File URL in document files folder.
     */
    public function downloadThumbnail()
    {
        if ($this->getThumbnailURL() != '') {

            $pathinfo = basename($this->getThumbnailURL());
            $thumbnailName = $this->embedId.'_'.$pathinfo;

            // initialisation de la session
            $ch = curl_init();

            /* Check if cURL is available */
            if ($ch !== FALSE) {

                $fh = fopen(Document::getFilesFolder().'/'.$thumbnailName, 'w');

                if($fh !== FALSE)
                {
                    // configuration des options
                    curl_setopt($ch, CURLOPT_URL, $this->getThumbnailURL());
                    curl_setopt($ch, CURLOPT_FILE, $fh);
                    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
                    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.116 Safari/537.36 FirePHP/4Chrome");
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

                    // exécution de la session
                    if(curl_exec($ch) === true) {

                        // fermeture des ressources
                        curl_close($ch);

                        if (file_exists(Document::getFilesFolder().'/'.$thumbnailName) &&
                            filesize(Document::getFilesFolder().'/'.$thumbnailName) > 0) {

                            return $thumbnailName;
                        } else {
                            return false;
                        }
                    }
                    else {
                        // fermeture des ressources
                        curl_close($ch);
                        return false;
                    }
                }
                else {
                    return false;
                }
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }
}
