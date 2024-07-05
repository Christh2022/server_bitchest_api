<?php

namespace App\Controller\api;

use App\Entity\Admin;
use App\Entity\Clients;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SignupController extends AbstractController
{
    #[Route('/api/signup', name: 'api_register', methods: ['POST'])]
    public function apiSignUp(
        ValidatorInterface $validator,
        SerializerInterface $serializer,
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->getUser()) {
            return new JsonResponse(
                $serializer->serialize(
                    ['message' => 'L\'utilisateur doit se déconnecter avant d\'accéder à la page de connexion.'],
                    'json'
                ),
                Response::HTTP_UNAUTHORIZED,
                [],
                true
            );
        }


        $newUser = $serializer->deserialize($request->getContent(), User::class, 'json');
        $admin = $entityManager->getRepository(Admin::class)->find(8);

        $getPassword = $this->generatePassword(10);

        $now = new DateTimeImmutable();
        $newUser->setCreatedAt($now);
        $newUser->setUpdatedAt($now);
        $newUser->setAdminId($admin);
        $newUser->setRoles(['ROLE_USER']);
        $newUser->setOneUsePassword($getPassword);
        $newUser->setStatus(false);

        $errors = $validator->validate($newUser);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        $newUser->setPassword(
            $userPasswordHasher->hashPassword($newUser, $getPassword)
        );

        $newClientData = json_decode($request->getContent(), true);
        if (!isset($newClientData['email'], $newClientData['last_name'], $newClientData['first_name'])) {
            return new JsonResponse(
                $serializer->serialize(['message' => 'Veuillez remplir tous les champs.'], 'json'),
                Response::HTTP_BAD_REQUEST,
                ['accept' => 'application/json'],
                true
            );
        }

        $newClient = new Clients();
        $newClient->setLastname($newClientData['last_name']);
        $newClient->setFirstname($newClientData['first_name']);
        $newClient->setEmail($newClientData['email']);
        $newClient->setBalance(0.00);

        $newUser->setClientId($newClient);

        $errors = $validator->validate($newClient);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        $entityManager->persist($newClient);
        $entityManager->persist($newUser);
        $entityManager->flush();

        return new JsonResponse(
            $serializer->serialize(['message' => 'Vous recevrez un mail après traitement de votre demande.'], 'json'),
            Response::HTTP_OK,
            ['accept' => 'application/json'],
            true
        );
    }


    function GeneratePassword($length = 12, $includeUpperCase = true, $includeNumbers = true, $includeSymbols = true)
    {
        $lowerCaseChars = 'abcdefghijklmnopqrstuvwxyz';
        $upperCaseChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numberChars = '0123456789';
        $symbolChars = '!@#$%^&*()-_=+{}[]|:;<>,.?';

        $allChars = $lowerCaseChars;
        if ($includeUpperCase) {
            $allChars .= $upperCaseChars;
        }
        if ($includeNumbers) {
            $allChars .= $numberChars;
        }
        if ($includeSymbols) {
            $allChars .= $symbolChars;
        }

        $password = '';
        $maxIndex = strlen($allChars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $allChars[random_int(0, $maxIndex)];
        }

        return $password;
    }
}
