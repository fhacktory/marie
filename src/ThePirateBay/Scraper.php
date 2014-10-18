<?php

namespace Duchesse\Chaton\Marie\ThePirateBay;
use Duchesse\Chaton\Marie\Util;

class Scraper
{
    const BASE_URL     = 'https://thepiratebay.org';
    const SEARCH_QUERY = '/search/{query}/0/{order}/{cat}';

    const CAT_VIDEO = 200;
    const ORDER_SEEDERS_DESC = 7;

    const XPATH_TORRENT_LINK  = "id('searchResult')/tr[1]/td[2]/div/a";
    const XPATH_MAGNET        = "id('details')/div[4]/a[1]";
    const XPATH_MAGNET_W_IMG  = "id('details')/div[4]/a[1]";
    const XPATH_MAGNET_WO_IMG = "id('details')/div[3]/a[1]";
    const XPATH_IMDB          = "id('details')/dl[1]/dd[4]/a";
    const XPATH_HASH_W_IMG    = "id('details')/dl[1]";
    const XPATH_HASH_WO_IMG   = "id('details')/dl[2]";

    /**
     * @param string $query search string.
     * @param int $cat CAT_*
     * @param int $order ORDER_*
     * @return Torrent[]
     */
    public static function search($query, $cat, $order)
    {
        try {
            $url = self::getFirstResultUrl(urlencode($query), $cat, $order);
        } catch (\RuntimeException $e) {
            return [];
        }

        $torrentXml = $xml = self::xmlFromUrl($url);

        $magnet = self::getTorrentMagnet($xml);
        $imdb = self::getTorrentImdb($xml);
        $hash = self::getTorrentHash($xml);

        return [new Torrent(compact('url', 'magnet', 'imdb', 'hash'))];
    }

    /**
     * @param SimpleXmlElement
     * @return string IMDB ID.
     */
    protected static function getTorrentImdb($xml)
    {
        $imdb = $xml->xpath(self::XPATH_IMDB);
        if (count($imdb) !== 1)
            throw new \RuntimeException('No imdb for torrent.');
        sscanf((string) $imdb[0]['href'], "http://www.imdb.com/title/%s", $url);
        return trim($url, '/');
    }

    /**
     * @param SimpleXmlElement
     * @return string HASH ID.
     */
    protected static function getTorrentHash($xml)
    {
        $hash = $xml->xpath(self::XPATH_HASH_W_IMG);
        if (empty($hash[0]) || strlen(trim($hash[0])) <= 0)
            $hash = $xml->xpath(self::XPATH_HASH_WO_IMG);

        if (empty($hash[0]) || strlen(trim($hash[0])) <= 0)
            throw new \RuntimeException('No hash for torrent.');

        return strtoupper(trim((string) $hash[0]));
    }

    /**
     * @param SimpleXmlElement
     * @return string
     */
    protected static function getTorrentMagnet($xml)
    {
        $magnet = $xml->xpath(self::XPATH_MAGNET_W_IMG);

        if (empty($magnet[0]['href']) || strpos((string) $magnet[0]['href'], 'magnet:') !== 0)
            $magnet = $xml->xpath(self::XPATH_MAGNET_WO_IMG);

        if (empty($magnet[0]['href']) || strpos((string) $magnet[0]['href'], 'magnet:') !== 0)
            throw new \RuntimeException('No magnet for torrent.');

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
        if (count($links) !== 1)
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

        if (file_exists($cache)) {
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
