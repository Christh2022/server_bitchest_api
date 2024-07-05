<?php 
    namespace App\Controller\api;

use App\Security\UserInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

    class LoginController extends AbstractController{
        #[Route(path: 'api/login', name: 'api_login', methods: ['POST'])]
    
       
        public function login(){
            /** @var User|null  */

            $user = $this->getUser();

            return $this->json([
                'id' => $user->getId(),
            ]);
        }
    }