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
 * @file VimeoEmbedFinder.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\MediaFinders;

/**
 * Vimeo tools class.
 *
 * Manage a Vimeo video feed
 */
class VimeoEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'vimeo';

    /**
     * Create a new Vimeo video handler with its embed id.
     *
     * @param string $embedId Vimeo video identifier
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
        return $this->getFeed()[0]['title'];
    }
    /**
     * {@inheritdoc}
     */
    public function getMediaDescription()
    {
        return $this->getFeed()[0]['description'];
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
        return $this->getFeed()[0]['thumbnail_large'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFeed($searchTerm, $author, $maxResults = 15)
    {
        $url = "http://gdata.youtube.com/feeds/api/videos/?q=" . $searchTerm . "&v=2&alt=json&max-results=" . $maxResults;
        if (!empty($author)) {
            $url .= '&author=' . $author;
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
        $url = "http://vimeo.com/api/v2/video/" . $this->embedId . ".json";

        return $this->downloadFeedFromAPI($url);
    }

    /**
     * Get embed media source URL.
     *
     * ### Vimeo embed parameters
     *
     * * displayTitle
     * * byline
     * * portrait
     * * autoplay
     * * color
     * * loop
     * * controls
     * * api
     *
     * @param array $args
     *
     * @return string
     */
    public function getSource(&$args = [])
    {

        $queryString = [
            'api' => 1,
        ];

        if (isset($args['displayTitle'])) {
            $queryString['title'] = (int) $args['displayTitle'];
        }
        if (isset($args['byline'])) {
            $queryString['byline'] = (int) $args['byline'];
        }
        if (isset($args['color'])) {
            $queryString['color'] = $args['color'];
        }
        if (isset($args['portrait'])) {
            $queryString['portrait'] = (int) $args['portrait'];
        }
        if (isset($args['id'])) {
            $queryString['player_id'] = $args['id'];
        }
        if (isset($args['loop'])) {
            $queryString['loop'] = (int) $args['loop'];
        }
        if (isset($args['autoplay'])) {
            $queryString['autoplay'] = (int) $args['autoplay'];
        }
        if (isset($args['fullscreen'])) {
            $queryString['fullscreen'] = (int) $args['fullscreen'];
        }
        if (isset($args['api'])) {
            $queryString['api'] = (int) $args['api'];
        }
        if (isset($args['controls'])) {
            $queryString['controls'] = (int) $args['controls'];
        }
        if (isset($args['background'])) {
            $queryString['background'] = (int) $args['background'];
        }

        return 'https://player.vimeo.com/video/'.$this->embedId.'?'.http_build_query($queryString);
    }
}
