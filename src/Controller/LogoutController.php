<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

class LogoutController extends AbstractFOSRestController
{
    /**
     * @Route("/api/logout", name="logout")
     * @return \FOS\RestBundle\View\View
     */
    public function logout()
    {
        $response = new Response();
        $response->headers->clearCookie('BEARER');
        $response->send();
        return $this->view(['message' => 'Logged out!'], Response::HTTP_OK);
    }
}
