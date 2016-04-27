<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Channel\SlideShareBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\Location\SlideShareBundle\Entity\SlideShareUser;

class SlideShareController extends Controller
{

    const RESOURCE_OWNER = 'SlideShare';
    const LOCATION_BUNDLE = 'campaignchain/location-slideshare';
    const LOCATION_MODULE = 'campaignchain-slideshare-user';
    private $applicationInfo = array(
        'key_labels' => array('id', 'API Key'),
        'secret_labels' => array('secret', 'API Secret'),
        'config_url' => 'http://www.slideshare.net/developers/applyforapi',
        'parameters' => array(),

    );

    public function createAction()
    {
        $oauthApp = $this->get('campaignchain.security.authentication.client.oauth.application');
        $application = $oauthApp->getApplication(self::RESOURCE_OWNER);
        if(!$application){
            return $oauthApp->newApplicationTpl(self::RESOURCE_OWNER, $this->applicationInfo);
        }
        else {
            return $this->render(
                'CampaignChainChannelSlideShareBundle:Create:index.html.twig',
                array(
                    'page_title' => 'Connect with SlideShare'
                )
            );
        }
    }
    
    public function newAction(Request $request)
    {
        $locationType = $this->get('campaignchain.core.form.type.location');
        $locationType->setBundleName('campaignchain/location-slideshare');
        $locationType->setModuleIdentifier('campaignchain-slideshare');
        $form = $this->createFormBuilder()
            ->add('username', 'text')
            ->add('password', 'repeated', array(
            'required'        => false,
            'type'            => 'password',
            'first_name'      => 'password',
            'second_name'     => 'password_again',
            'invalid_message' => 'The password fields must match.',
            ))            
            ->getForm();
            
        $form->handleRequest($request);
        try {
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->getConnection()->beginTransaction();
                
                $locationUsername = $form->getData()['username'];
                $locationPassword = $form->getData()['password'];
                
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
                */
                
                $oauthApp = $this->get('campaignchain.security.authentication.client.oauth.application');
                $application = $oauthApp->getApplication(self::RESOURCE_OWNER);
                $client = $this->container->get('campaignchain.channel.slideshare.rest.client');
                $connection = $client->connect(
                  $application->getKey(), $application->getSecret(), $locationUsername, $locationPassword
                );       
                $xml = $connection->getUserTags();
                if (isset($xml->Message) && strtolower($xml->Message) == 'failed user authentication') {
                  throw new \Exception('The credentials provided are invalid');
                }
                
                
                $locationURL = 'http://www.slideshare.net/' . $locationUsername;
                $locationService = $this->get('campaignchain.core.location');
                $locationModule = $locationService->getLocationModule('campaignchain/location-slideshare', 'campaignchain-slideshare-user');
                $location = new Location();
                $location->setLocationModule($locationModule);
                $location->setName($locationUsername);
                $location->setUrl($locationURL);
                /*
                 * If user uploaded an image, use that as the Location image,
                 * otherwise, take the SlideShare default profile image.
                 */
                $slideShareUserImage = 'http://cdn.slidesharecdn.com/profile-photo-'.$locationUsername.'-96x96.jpg';
                try {
                    getimagesize($slideShareUserImage);
                } catch (\Exception $e) {
                    $slideShareUserImage = 'http://public.slidesharecdn.com/b/images/user-96x96.png';
                }
                $location->setImage($slideShareUserImage);
                $wizard = $this->get('campaignchain.core.channel.wizard');
                $wizard->setName($location->getName());
                $wizard->addLocation($location->getUrl(), $location);
                $channel = $wizard->persist();
                $wizard->end();
                
                $slideshareUser = new SlideShareUser();
                $slideshareUser->setLocation($channel->getLocations()[0]);
                $slideshareUser->setIdentifier($locationUsername);
                $slideshareUser->setPassword($locationPassword);
                $slideshareUser->setDisplayName($locationUsername);
                $em->persist($slideshareUser);
                $em->flush();
                $em->getConnection()->commit();
                $this->get('session')->getFlashBag()->add(
                    'success',
                    'The Slideshare location <a href="#">'.$locationUsername.'</a> was connected successfully.'
                );            
                return $this->redirect($this->generateUrl(
                    'campaignchain_core_channel'));
            }
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw $e;
        }
        return $this->render(
            'CampaignChainCoreBundle:Base:new.html.twig',
            array(
                'page_title' => 'Connect SlideShare Account',
                'form' => $form->createView(),
            ));
    }
    
}
