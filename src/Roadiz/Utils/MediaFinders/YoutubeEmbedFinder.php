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
 * @file YoutubeEmbedFinder.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\MediaFinders;

use RZ\Roadiz\Core\Exceptions\APINeedsAuthentificationException;

/**
 * Youtube tools class.
 *
 * Manage a youtube video feed
 */
class YoutubeEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'youtube';

    /**
     * Tell if embed media exists after its API feed.
     *
     * @return boolean
     */
    public function exists()
    {
        if ($this->getFeed() !== false &&
            isset($this->getFeed()['items'][0])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaTitle()
    {
        if (isset($this->getFeed()['items'][0])) {
            return $this->getFeed()['items'][0]['snippet']['title'];
        }

        return "";
    }
    /**
     * {@inheritdoc}
     */
    public function getMediaDescription()
    {
        if (isset($this->getFeed()['items'][0])) {
            return $this->getFeed()['items'][0]['snippet']['description'];
        }

        return "";
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
        if (isset($this->getFeed()['items'][0])) {
            $thumbnails = $this->getFeed()['items'][0]['snippet']['thumbnails'];

            if (isset($thumbnails['maxres'])) {
                return $thumbnails['maxres']['url'];
            } else {
                return $thumbnails['high']['url'];
            }
        }

        return "";
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFeed($searchTerm, $author, $maxResults = 15)
    {
        if ($this->getKey() != "") {
            $url = "https://www.googleapis.com/youtube/v3/search?q=".$searchTerm."&part=snippet&key=".$this->getKey()."&maxResults=".$maxResults;
            if (!empty($author)) {
                $url .= '&author='.$author;
            }
            return $this->downloadFeedFromAPI($url);
        } else {
            throw new APINeedsAuthentificationException("YoutubeEmbedFinder needs a Google server key, create a “google_server_id” setting.", 1);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaFeed($search = null)
    {
        if ($this->getKey() != "") {
            $url = "https://www.googleapis.com/youtube/v3/videos?id=".$this->embedId."&part=snippet&key=".$this->getKey()."&maxResults=1";
            return $this->downloadFeedFromAPI($url);
        } else {
            throw new APINeedsAuthentificationException("YoutubeEmbedFinder needs a Google server key, create a “google_server_id” setting.", 1);
        }
    }

    /**
     * Get embed media source URL.
     *
     * ### Youtube additional embed parameters
     *
     * * modestbrandin
     * * rel
     * * showinfo
     * * start
     * * enablejsapi
     *
     * @param array $options
     *
     * @return string
     */
    public function getSource(array &$options = [])
    {
        parent::getSource($options);

        $queryString = [
            'rel' => 0,
            'html5' => 1,
            'wmode' => 'transparent',
        ];

        if ($options['autoplay']) {
            $queryString['autoplay'] = (int) $options['autoplay'];
        }
        if ($options['loop']) {
            $queryString['loop'] = (int) $options['loop'];
            $queryString['playlist'] = $this->embedId;
        }
        if (null !== $options['color']) {
            $queryString['color'] = $options['color'];
        }
        if ($options['controls']) {
            $queryString['controls'] = (int) $options['controls'];
        }
        if ($options['fullscreen']) {
            $queryString['fs'] = (int) $options['fullscreen'];
        }

        if ($options['modestbranding']) {
            $queryString['modestbranding'] = (int) $options['modestbranding'];
        }
        if ($options['rel']) {
            $queryString['rel'] = (int) $options['rel'];
        }
        if ($options['showinfo']) {
            $queryString['showinfo'] = (int) $options['showinfo'];
        }
        if ($options['start']) {
            $queryString['start'] = (int) $options['start'];
        }
        if ($options['enablejsapi']) {
            $queryString['enablejsapi'] = (int) $options['enablejsapi'];
        }

        return '//www.youtube.com/embed/'.$this->embedId.'?'.http_build_query($queryString);
    }
}
