<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file YoutubeFinder.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Utils;

/**
 * Youtube tools class.
 *
 * Manage a youtube video feed
 */
class YoutubeEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'youtube';

    /**
     * Create a new Youtube video handler with its embed id.
     *
     * @param string $embedId Youtube video identifier
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
        return $this->getFeed()['entry']['title']['$t'];
    }
    /**
     * {@inheritdoc}
     */
    public function getMediaDescription()
    {
        return $this->getFeed()['entry']['media$group']['media$description']['$t'];
    }
    /**
     * {@inheritdoc}
     */
    public function getThumbnailURL()
    {
        return $this->getFeed()['entry']['media$group']['media$thumbnail'][2]['url'];
    }


    /**
     * {@inheritdoc}
     */
    public function getSearchFeed( $searchTerm, $author, $maxResults=15 )
    {
        $url = "http://gdata.youtube.com/feeds/api/videos/?q=".$searchTerm."&v=2&alt=json&max-results=".$maxResults;
        if (!empty($author)) {
            $url .= '&author='.$author;
        }

        return $this->downloadFeedFromAPI($url);
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaFeed($search = null)
    {
        // http://gdata.youtube.com/feeds/api/videos/<Code de la vidéo>?v=2&alt=json ---> JSON
        //
        $url = "http://gdata.youtube.com/feeds/api/videos/".$this->embedId."?v=2&alt=json";

        return $this->downloadFeedFromAPI($url);
    }

    /**
     * {@inheritdoc}
     */
    public function getSource($args = array())
    {
        return '//www.youtube.com/embed/'.$this->embedId.'?rel=0&html5=1&wmode=transparent';
    }
}
