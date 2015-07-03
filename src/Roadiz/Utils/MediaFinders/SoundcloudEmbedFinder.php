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
use RZ\Roadiz\Core\Bags\SettingsBag;

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
    public function getSource($args = [])
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
