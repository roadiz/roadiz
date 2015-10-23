<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file SoundcloudEmbedFinder.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\MediaFinders;

use RZ\Roadiz\Core\Exceptions\APINeedsAuthentificationException;

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
        if ($this->getKey() != "") {
            $url = "http://api.soundcloud.com/tracks/".
                    $this->embedId.
                    ".json?client_id=".
                    $this->getKey();

            return $this->downloadFeedFromAPI($url);
        } else {
            throw new APINeedsAuthentificationException("Soundcloud need a clientId to perform API calls, create a “soundcloud_client_id” setting.", 1);
        }
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
    public function getSource(&$args = [])
    {
        $queryString = [
            'url' => 'https://api.soundcloud.com/tracks/'.$this->embedId,
        ];

        if (isset($args['hide_related'])) {
            $queryString['hide_related'] = (int) $args['hide_related'];
        }
        if (isset($args['show_comments'])) {
            $queryString['show_comments'] = (int) $args['show_comments'];
        }
        if (isset($args['show_user'])) {
            $queryString['show_user'] = (int) $args['show_user'];
        }
        if (isset($args['show_reposts'])) {
            $queryString['show_reposts'] = (int) $args['show_reposts'];
        }
        if (isset($args['autoplay'])) {
            $queryString['auto_play'] = (int) $args['autoplay'];
        }
        if (isset($args['auto_play'])) {
            $queryString['auto_play'] = (int) $args['autoplay'];
        }
        if (isset($args['visual'])) {
            $queryString['visual'] = (int) $args['visual'];
        }
        if (isset($args['controls'])) {
            $queryString['controls'] = (int) $args['controls'];
        }


        return '//w.soundcloud.com/player/?' . http_build_query($queryString);
    }
}
