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

use Symfony\Component\HttpFoundation\Session\Session;
use Guzzle\Http\Client;


class SlideShareClient
{
    const RESOURCE_OWNER = 'SlideShare';
    const BASE_URL   = 'https://www.slideshare.net/api/2/';

    protected $container;

    protected $client;

    protected $apiKey;
    
    protected $apiSecret;

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

    public function connect($appKey, $appSecret, $username, $password){
        $this->username = $username;        
        $this->password = $password;
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        try {
            $this->client = new Client(self::BASE_URL);
            return $this;
        }
        catch (ClientErrorResponseException $e) {
            $req = $e->getRequest();
            $resp =$e->getResponse();
            print_r($resp);
        }
        catch (ServerErrorResponseException $e) {

            $req = $e->getRequest();
            $resp =$e->getResponse();
            print_r($resp);
        }
        catch (BadResponseException $e) {
            $req = $e->getRequest();
            $resp =$e->getResponse();
            print_r($resp);
        }
        catch(Exception $e){
            print_r($e->getMessage());
        }
    }

    
    public function getSlideshowByUrl($url)
    {
        $request = $this->client->createRequest('GET', 'https://www.slideshare.net/api/2/get_slideshow');
        $query = $request->getQuery();
        $ts = time();
        $query->set('api_key', $this->appKey);
        $query->set('ts',  $ts);
        $query->set('hash', sha1($this->appSecret.$ts));
        $query->set('username', $this->username);
        $query->set('password', $this->password);
        $query->set('slideshow_url', $url);
        return $request->send()->xml();
    }

    public function getSlideshowById($id)
    {
        $request = $this->client->createRequest('GET', 'https://www.slideshare.net/api/2/get_slideshow');
        $query = $request->getQuery();
        $ts = time();
        $query->set('api_key', $this->appKey);
        $query->set('ts',  $ts);
        $query->set('hash', sha1($this->appSecret.$ts));
        $query->set('username', $this->username);
        $query->set('password', $this->password);
        $query->set('slideshow_id', $id);
        $query->set('detailed', '1');
        $query->set('exclude_tags', '1');
        return $request->send()->xml();
    }
    
    public function getUserTags()
    {
        $request = $this->client->createRequest('GET', 'https://www.slideshare.net/api/2/get_user_tags');
        $query = $request->getQuery();
        $ts = time();
        $query->set('api_key', $this->appKey);
        $query->set('ts', $ts);
        $query->set('hash', sha1($this->appSecret.$ts));
        $query->set('username', $this->username);
        $query->set('password', $this->password);
        return $request->send()->xml();
    }    
    
    public function getUserSlideshows($for = null)
    {
        $request = $this->client->createRequest('GET', 'https://www.slideshare.net/api/2/get_slideshows_by_user');
        $query = $request->getQuery();
        $ts = time();
        if (is_null($for)) { 
            $for = $this->username; 
        }
        $query->set('api_key', $this->appKey);
        $query->set('ts', $ts);
        $query->set('hash', sha1($this->appSecret.$ts));
        $query->set('username_for', $for);
        $query->set('detailed', '1');
        $query->set('username', $this->username);
        $query->set('password', $this->password);
        return $request->send()->xml();
    }

    public function allowEmbedsUserSlideshow($id)
    {
        $request = $this->client->createRequest('GET', 'https://www.slideshare.net/api/2/edit_slideshow');
        $query = $request->getQuery();
        $ts = time();
        $query->set('api_key', $this->appKey);
        $query->set('ts', $ts);
        $query->set('hash', sha1($this->appSecret.$ts));
        $query->set('username_for', $this->username);
        $query->set('slideshow_id', $id);
        $query->set('make_slideshow_private', 'Y');
        $query->set('allow_embeds', 'Y');
        $query->set('username', $this->username);
        $query->set('password', $this->password);
        return $request->send()->xml();
    }

    public function publishUserSlideshow($id)
    {
        $request = $this->client->createRequest('GET', 'https://www.slideshare.net/api/2/edit_slideshow');
        $query = $request->getQuery();
        $ts = time();
        $query->set('api_key', $this->appKey);
        $query->set('ts', $ts);
        $query->set('hash', sha1($this->appSecret.$ts));
        $query->set('username_for', $this->username);
        $query->set('slideshow_id', $id);
        $query->set('make_slideshow_private', 'N');
        $query->set('username', $this->username);
        $query->set('password', $this->password);
        return $request->send()->xml();
    }        
    
}