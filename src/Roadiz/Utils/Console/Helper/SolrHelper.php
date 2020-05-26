<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use Solarium\Client;
use Symfony\Component\Console\Helper\Helper;

/**
 * SolrHelper.
 */
class SolrHelper extends Helper
{
    private $solr;

    /**
     * @param \Solarium\Client|null $solr
     */
    public function __construct(Client $solr = null)
    {
        $this->solr = $solr;
    }

    /**
     * @return \Solarium\Client
     */
    public function getSolr()
    {
        return $this->solr;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'solr';
    }

    /**
     * @return boolean
     */
    public function ready()
    {
        if (null !== $this->solr) {
            // create a ping query
            $ping = $this->solr->createPing();
            // execute the ping query
            try {
                $this->solr->ping($ping);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }
}
