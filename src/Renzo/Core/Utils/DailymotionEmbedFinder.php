<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file DailymotionFinder.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Utils;

/**
 * Dailymotion tools class.
 *
 * Manage a youtube video feed
 */
class DailymotionEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'dailymotion';

    /**
     * Create a new Dailymotion video handler with its embed id.
     *
     * @param string $embedId Dailymotion video identifier
     */
    public function __construct($embedId)
    {
        $this->embedId = $embedId;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaTitle()
    {
        return $this->getFeed()['title'];
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
    public function getThumbnailURL()
    {
        return $this->getFeed()['thumbnail_url'];
    }


    /**
     * {@inheritdoc}
     */
    public function getSearchFeed($searchTerm, $author, $maxResults = 15)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaFeed($search = null)
    {
        // http://gdata.youtube.com/feeds/api/videos/<Code de la vidéo>?v=2&alt=json ---> JSON
        //
        $url = "http://www.dailymotion.com/services/oembed?format=json&url=".
                "http://www.dailymotion.com/video/".
                $this->embedId;

        return $this->downloadFeedFromAPI($url);
    }

    /**
     * {@inheritdoc}
     */
    public function getSource($args = array())
    {
        $uri = '//www.dailymotion.com/embed/video/'.$this->embedId;

        return $uri;
    }
}
