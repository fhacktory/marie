<?php

namespace Duchesse\Chaton\Marie\ThePirateBay;
use Duchesse\Chaton\Marie\Util;

class Scraper
{
    const BASE_URL     = 'https://thepiratebay.org';
    const SEARCH_QUERY = '/search/{query}/0/{order}/{cat}';

    const CAT_VIDEO = 200;
    const ORDER_SEEDERS_DESC = 7;

    const XPATH_TORRENT_LINK = "id('searchResult')/tr[1]/td[2]/div/a";
    const XPATH_MAGNET       = "id('details')/div[4]/a[1]";
    const XPATH_IMDB         = "id('details')/dl[1]/dd[4]/a";

    /**
     * @param string $query search string.
     * @param int $cat CAT_*
     * @param int $order ORDER_*
     * @return Torrent[]
     */
    public static function search($query, $cat, $order)
    {
        $url = self::getFirstResultUrl(urlencode($query), $cat, $order);
        $torrentXml = $xml = self::xmlFromUrl($url);

        $magnet = self::getTorrentMagnet($xml);
        $imdb = self::getTorrentImdb($xml);

        return [new Torrent(compact('url', 'magnet', 'imdb'))];
    }

    /**
     * @param SimpleXmlElement
     * @return string IMDB ID.
     */
    protected static function getTorrentImdb($xml)
    {
        $imdb = $xml->xpath(self::XPATH_IMDB);
        if(count($imdb) !== 1)
            throw new \RuntimeException("No imdb for torrent at `$url`.");
        sscanf((string) $imdb[0]['href'], "http://www.imdb.com/title/%s", $url);
        return trim($url, '/');
    }

    /**
     * @param SimpleXmlElement
     * @return string
     */
    protected static function getTorrentMagnet($xml)
    {
        $magnet = $xml->xpath(self::XPATH_MAGNET);
        if(count($magnet) !== 1)
            throw new \RuntimeException("No magnet for torrent at `$url`.");
        return (string) $magnet[0]['href'];
    }

    /**
     * @see search() for params.
     * @return string
     */
    protected static function getFirstResultUrl($query, $cat, $order)
    {
        $url = Util::strTpl(
            self::BASE_URL . self::SEARCH_QUERY,
            compact('query', 'cat', 'order')
        );
        $xml = self::xmlFromUrl($url);
        $links = $xml->xpath(self::XPATH_TORRENT_LINK);
        if(count($links) !== 1)
            throw new \RuntimeException("No result for query `$query`.");

        return self::BASE_URL . ((string) $links[0]['href']);
    }

    /**
     * @param string $url
     * @return SimpleXmlElement
     */
    protected static function xmlFromUrl($url)
    {
        // TODO: dirty cache, make it less dirty.
        $cache = '/tmp/marie.html.' . md5($url);

        if(file_exists($cache)) {
            $raw = file_get_contents($cache);
        } else {
            $raw = file_get_contents($url);
            file_put_contents($cache, $raw);
        }

        $doc = new \DOMDocument();
        @$doc->loadHtml($raw);
        return simplexml_import_dom($doc);
    }
}
