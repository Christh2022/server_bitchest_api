<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfTokenController extends AbstractController
{
    private $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * @Route("/api/csrf-token", name="csrf_token", methods={"GET"})
     */
    public function getCsrfToken(): JsonResponse
    {
        $token = $this->csrfTokenManager->getToken('authenticate')->getValue();
        return $this->json(['csrfToken' => $token]);
    }
}
