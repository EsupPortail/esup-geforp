<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @Route("/api")
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class ShibbolethController extends AbstractController
{
    /**
     * @Route("/shibboleth/auth", name="api.shibboleth.auth")
     */
    public function authAction(Request $request)
    {
        $front_url = $this->container->getParameter('front_url');
        $user = $this->getUser();
        if ($user) {
            // redirect user to login form
            $url = $front_url.'/login?shibboleth=1';
        } else {
            // shibboleth authentification worked
            $token = $this->get('security.context')->getToken();
            $success = '0';
            if ($token->hasAttribute('mail') && $token->getAttribute('mail')) {
                $success = '1';
            }

            // redirect user to registration form
            $url = $front_url.'/register/organization?shibboleth='.$success;
        }

        if ($request->getQueryString()) {
            $url .= '&'.$request->getQueryString();
        }

        return new RedirectResponse($url);
    }

    /**
     * @Route("/shibboleth/token", name="api.shibboleth.token")
     * @Method("POST")
     */
    public function tokenAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Shibboleth session is missing user.');
        }
        $clientId = $request->get('client_id');
        $clientSecret = $request->get('client_secret');
        $generator = $this->get('sygefor_api.oauth.token_generator');

        return $generator->generateTokenResponse($user, $clientId, $clientSecret);
    }

    /**
     * @Route("/shibboleth/attributes", name="api.shibboleth.attributes", defaults={"_format" = "json"})
     * @Rest\View()
     */
    public function attributesAction(Request $request)
    {
        $token = $this->get('security.context')->getToken();
        $attributes = $token->getAttributes();
        $attributes = array_map('utf8_encode', $attributes);
        $attributes['register_data'] = $this->prepareRegisterData($attributes);

        return $attributes;
    }

    /**
     * Prepare register data based on shibboleth attributes.
     */
    protected function prepareRegisterData($attrs)
    {
        $data = array(
            'email' => @$attrs['mail'],
            'lastName' => @$attrs['sn'],
            'firstName' => @$attrs['givenName'],
        );

        // title
        if (!empty($attrs['title']) && in_array($attrs['title'], array('Mme', 'Melle'), true)) {
            $data['title'] = 2;
        } else {
            $data['title'] = 1;
        }

        // phone : mobile first
        $data['phoneNumber'] = isset($attrs['mobile']) ? $attrs['mobile'] : $attrs['telephoneNumber'];

        // postal address
        if (!empty($attrs['postalAddress'])) {
            $address = $this->parseAddress($attrs['postalAddress']);
            if ($address) {
                $data['address'] = $address['address'];
                $data['city'] = $address['city'];
                $data['zip'] = $address['zip'];
            }
        }

        return $data;
    }

    /**
     * @param $address
     *
     * @return array
     */
    private function parseAddress($address)
    {
        if (preg_match('/([^$]*)\$(\w{5})\s(.*)/im', $address, $regs)) {
            return array(
                'address' => $regs[1],
                'zip' => $regs[2],
                'city' => $regs[3],
            );
        }

        return false;
    }
}
