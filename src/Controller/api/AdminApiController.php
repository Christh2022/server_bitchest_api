<?php

namespace App\Controller\api;

use App\Entity\Admin;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;


class AdminApiController extends AbstractController
{


    #[Route(path: '/api/admin/signup', name: 'admin_signup', methods: ['POST'])]
    public function AdminSignUp(
        ValidatorInterface $validator,
        SerializerInterface $serializer,
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->getUser()) {
            return new JsonResponse(
                $serializer->serialize(["message" => "l'utilisateur doit se déconnecter avant de se connecter "], 'json'),
                Response::HTTP_UNAUTHORIZED,
                [],
                true
            );
        }

        $newUser = $serializer->deserialize($request->getContent(), User::class, 'json');
        $errors = $validator->validate($newUser);
        if (count($errors) > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        $now = new DateTimeImmutable();
        $newUser->setCreatedAt($now);
        $newUser->setUpdatedAt($now);
        $newUser->setRoles(['ROLE_ADMIN', "ROLE_USER"]);
        $newUser->setStatus(true);
        $newUser->setOneUsePassword('not needed');
        
        $getPassword = $newUser->getPassword();
        $newUser->setPassword(
            $userPasswordHasher->hashPassword($newUser, $getPassword)
        );


        $newAdminData = json_decode($request->getContent(), true);

        if (!isset($newAdminData['first_name'], $newAdminData['email'])) {
            return new JsonResponse(
                $serializer->serialize(["message" => "les champs email et firstname sont obligatoire "], 'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        $newAdmin = new Admin();
        $newAdmin->setEmail($newAdminData['email']);
        $newAdmin->setFirstName($newAdminData['first_name']);

        $errors = $validator->validate($newAdmin);
        if (count($errors) > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        $entityManager->persist($newAdmin);
        $entityManager->persist($newUser);
        $entityManager->flush();

        return new JsonResponse(
            $serializer->serialize(["message" => "l'administrateur a été créé avec succès"], 'json'),
            Response::HTTP_CREATED,
            [],
            true
        );
    }
}

