<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file SoundcloudFinder.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Utils;

use RZ\Renzo\Core\Exceptions\APINeedsAuthentificationException;
use RZ\Renzo\Core\Bags\SettingsBag;

/**
 * Soundcloud tools class.
 *
 * Manage a youtube video feed
 */
class SoundcloudEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'soundcloud';

    /**
     * Create a new Soundcloud video handler with its embed id.
     *
     * @param string $embedId Soundcloud video identifier
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
        return $this->getFeed()['description'];
    }
    /**
     * {@inheritdoc}
     */
    public function getMediaCopyright()
    {
        return "";
    }
    /**
     * {@inheritdoc}
     */
    public function getThumbnailURL()
    {
        return $this->getFeed()['artwork_url'];
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
        $clientId = SettingsBag::get('soundcloud_client_id');

        if (false === $clientId) {
            throw new APINeedsAuthentificationException("Soundcloud need a clientId to perform API calls, create a “soundcloud_client_id” setting.", 1);
        }

        $url = "http://api.soundcloud.com/tracks/".
                $this->embedId.
                ".json?client_id=".
                $clientId;

        return $this->downloadFeedFromAPI($url);
    }

    /**
     * {@inheritdoc}
     *
     * ## Available fields
     *
     * * auto_play
     * * hide_related
     * * show_comments
     * * show_user
     * * show_reposts
     * * visual
     */
    public function getSource($args = array())
    {
        $uri = '//w.soundcloud.com/player/?url='.
                'https://api.soundcloud.com/tracks/'.
                $this->embedId;

        if (!empty($args['auto_play'])) {
            $uri .= '&auto_play='.((boolean) $args['auto_play'] ? 'true': 'false');
        } else {
            $uri .= '&auto_play=false';
        }

        if (!empty($args['hide_related'])) {
            $uri .= '&hide_related='.((boolean) $args['hide_related'] ? 'true': 'false');
        } else {
            $uri .= '&hide_related=true';
        }

        if (!empty($args['show_comments'])) {
            $uri .= '&show_comments='.((boolean) $args['show_comments'] ? 'true': 'false');
        } else {
            $uri .= '&show_comments=true';
        }

        if (!empty($args['show_user'])) {
            $uri .= '&show_user='.((boolean) $args['show_user'] ? 'true': 'false');
        } else {
            $uri .= '&show_user=true';
        }

        if (!empty($args['show_reposts'])) {
            $uri .= '&show_reposts='.((boolean) $args['show_reposts'] ? 'true': 'false');
        } else {
            $uri .= '&show_reposts=false';
        }

        if (!empty($args['visual'])) {
            $uri .= '&visual='.((boolean) $args['visual'] ? 'true': 'false');
        } else {
            $uri .= '&visual=true';
        }

        return $uri;
    }
}
