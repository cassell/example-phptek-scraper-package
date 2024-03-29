<?php

namespace SecretCorporation\Phptek;
use \Goutte\Client;
use \Symfony\Component\DomCrawler\Crawler;
use \SecretCorporation\ExampleCache\Cache;

/**
 * Class SpeakerScraper
 * @package SecretCorporation\Phptek
 */
class SpeakerScraper
{
    const SPEAKERS_URL = 'http://tek.phparch.com/speakers/';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param Client $client
     */
    public function __construct(Client $client,Cache $cache)
    {
        $this->setClient($client);
        $this->setCache($cache);
    }

    /**
     * @return array
     */
    public function getSpeakers()
    {
        if ($this->getCache()->exists()) {
            return $this->getCache()->fetch();
        }
        else {
            $speakers = $this->scrapeSpeakers();
            $this->getCache()->save($speakers);
            return $speakers;
        }

    }

    /**
     * @return array
     */
    private function scrapeSpeakers()
    {
        return $this->getClient()->request('GET', self::SPEAKERS_URL)->filter('#speakerlist > div')->each(function (Crawler $node) {

            $speaker = [];
            $speaker["name"] = trim($node->filter('div.headshot > img')->attr("alt"));
            $speaker["gravatar"] = trim($node->filter('div.headshot > img')->attr("src"));
            $speaker["company"] = trim($node->filter('div.info > h4')->text());
            try {
                $speaker["twitter"]  = trim($node->filter('div.info > h3 > a')->text());
            } catch (\Exception $e) {
                // might fail
                $speaker["twitter"]  = "";
            }

            $speaker["talks"] = $node->filter('div.info > dl')->first()->siblings()->filter('dl')->each(function (Crawler $talkNode) {

                $talk = [];
                $talk['type'] = trim($talkNode->filter('dt > div')->eq(0)->text());
                $talk['level'] = trim($talkNode->filter('dt > div')->eq(1)->text());
                $talk['title'] = trim($talkNode->filter('dd > h5')->text());
                $texts = [];
                foreach ($talkNode->filter('dd')->getNode(0)->childNodes as $child) {
                    if ($child instanceof \DOMText) {
                        $texts[] = trim($child->textContent);
                    }
                }
                $talk['room'] = trim($texts[2]);
                $talk['when'] = trim($texts[3]);

                return $talk;

            });

            return $speaker;

        });

    }

    /**
     * @return mixed
     */
    private function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    private function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param Cache $cache
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

}
