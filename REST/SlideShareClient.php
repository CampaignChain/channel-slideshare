<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Channel\SlideShareBundle\REST;

use CampaignChain\CoreBundle\Exception\ExternalApiException;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Session\Session;

class SlideShareClient
{
    const RESOURCE_OWNER = 'SlideShare';
    const BASE_URL   = 'https://www.slideshare.net/api/2/';

    protected $container;

    /** @var  Client */
    protected $client;

    protected $appKey;
    
    protected $appSecret;

    protected $username;
    
    protected $password;
    
    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function connectByActivity($activity){
        return $this->connectByLocation($activity->getLocation());
    }

    public function logout() {
    
    }
    
    public function connectByLocation($location){  
        $app = $this->container->get('campaignchain.security.authentication.client.oauth.application');
        $application = $app->getApplication(self::RESOURCE_OWNER);       
        $slideshareLocation = $this->container->get('doctrine')->getRepository('CampaignChainLocationSlideShareBundle:SlideShareUser')->findOneByLocation($location);
        return $this->connect($application->getKey(), $application->getSecret(), $slideshareLocation->getIdentifier(), $slideshareLocation->getPassword());
    }

    public function connect($appKey, $appSecret, $username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;

        try {
            $this->client = new Client([
                'base_uri' => self::BASE_URL,
                'headers' => [
                    'Content-Type' => 'text/xml; charset=UTF8',
                ],
            ]);

            return $this;
        } catch (\Exception $e) {
            throw new ExternalApiException($e->getMessage(), $e->getCode());
        }
    }

    private function request($method, $uri, $body = array())
    {
        $authQuery = array();
        $ts = time();
        $authQuery['api_key']   = $this->appKey;
        $authQuery['ts']        = $ts;
        $authQuery['hash']      = sha1($this->appSecret.$ts);
        $authQuery['username']  = trim($this->username);
        $authQuery['password']  = trim($this->password);

        if(isset($body['query'])){
            $body['query'] = array_merge($body['query'], $authQuery);
        }

        try {
            $res = $this->client->request($method, $uri, $body);
            $xml = simplexml_load_string($res->getBody()->getContents());
            if(isset($xml->Message) && $xml->Message == 'Failed User authentication'){
                throw new ExternalApiException($xml->Message);
            }
            return $xml;
        } catch(\Exception $e){
            throw new ExternalApiException($e->getMessage(), $e->getCode());
        }
    }

    public function getSlideshowByUrl($url)
    {
        return $this->request(
            'GET',
            'https://www.slideshare.net/api/2/get_slideshow',
            array('query' => array(
                'slideshow_url' => $url,
            ))
        );
    }

    public function getSlideshowById($id)
    {
        return $this->request(
            'GET',
            'https://www.slideshare.net/api/2/get_slideshow',
            array('query' => array(
                'slideshow_id' => $id,
                'detailed' => '1',
                'exclude_tags' => '1',
            ))
        );
    }
    
    public function getUserTags()
    {
        return $this->request('GET', 'https://www.slideshare.net/api/2/get_user_tags');
    }
    
    public function getUserSlideshows($for = null)
    {
        if (is_null($for)) {
            $for = $this->username;
        }

        return $this->request(
            'GET',
            'https://www.slideshare.net/api/2/get_slideshows_by_user',
            array('query' => array(
                'username_for' => $for,
                'detailed' => '1',
            ))
        );
    }

    public function allowEmbedsUserSlideshow($id)
    {
        return $this->request('GET', 'https://www.slideshare.net/api/2/edit_slideshow',
            array('query' => array(
                'username_for' => $this->username,
                'slideshow_id' => $id,
                'make_slideshow_private' => 'Y',
                'allow_embeds' => 'Y',
            ))
        );
    }

    public function publishUserSlideshow($id)
    {
        return $this->request(
            'GET',
            'https://www.slideshare.net/api/2/edit_slideshow',
            array('query' => array(
                'username_for' => $this->username,
                'slideshow_id' => $id,
                'make_slideshow_private' => 'N',
            ))
        );
    }        
    
}