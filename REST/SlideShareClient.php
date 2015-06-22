<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
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
        
        return $this->connect($application->getKey(), $application->getSecret(), $location->getIdentifier(), $location->getPassword());
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

    
/*
                $oauthApp = $this->get('campaignchain.security.authentication.client.oauth.application');
                $application = $oauthApp->getApplication(self::RESOURCE_OWNER);
                $ts = time();
                $client = new \Guzzle\Http\Client;
                $request = $client->createRequest('GET', 'https://www.slideshare.net/api/2/get_user_tags');
                $query = $request->getQuery();
                $query->set('api_key', $application->getKey());
                $query->set('ts',  $ts);
                $query->set('hash', sha1($application->getSecret().$ts));
                $query->set('username', $locationUsername);
                $query->set('password', $locationPassword);
                $response = $request->send();
                
                $xml = $response->xml();
                if (isset($xml->Message) && strtolower($xml->Message) == 'failed user authentication') {
                  throw new \Exception('The credentials provided are invalid');
                }
*/                
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
    
}