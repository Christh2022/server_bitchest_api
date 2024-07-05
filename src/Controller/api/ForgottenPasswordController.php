<?php

namespace App\Controller\api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ForgottenPasswordController extends AbstractController
{
    #[Route('/forgotten/password', name: 'app_forgotten_password', methods: ['POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $newPassword = $data['password'];
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $entityManager->flush();

        return new JsonResponse(
            ['message' => 'Password reset successful'],
            Response::HTTP_OK,
            ['Content-Type' => 'application/json'],
            false
        );
    }
}
