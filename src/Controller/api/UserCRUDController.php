<?php

namespace App\Controller\api;

use App\Entity\User;
use App\Entity\Wallets;
use App\Repository\AdminRepository;
use App\Repository\ClientsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Id;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/user')]
class UserCRUDController extends AbstractController
{
    //list of all users
    #[Route(path: '/list', name: 'user_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        UserRepository $userRepository,
        SerializerInterface $serializer
    ): Response {

        $userList = $userRepository->findAll();

        // Convert each user to array and exclude the password field
        $userArray = [];
        foreach ($userList as $user) {
            if($user->getRoles()["0"] === "ROLE_USER"){
                $data = $serializer->serialize($user, "json");
                $newUser = json_decode($data, true);
                unset(
                    $newUser['password'], $newUser['admin_id'], 
                    $newUser['client_id']['client_id'], 
                    $newUser['client_id']['wallets']['crypto_id'],
                );
                $userArray[] = $newUser;
            }
        }

        return new JsonResponse(
            $userArray,
            Response::HTTP_ACCEPTED,
            [],
            false
        );
    }

    //Edit user information
    #[Route('/{id}/edit', name: 'user_edit', methods: ['POST'])]
    public function edit(
        $id,
        Request $request,
        UserRepository $userRepository,
        AdminRepository $adminRepository,
        ClientsRepository $clientsRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        LoggerInterface $logger // Inject the logger
    ): Response {

        $logger->info("Received request to edit user with ID: " . $id);

        $content = $request->getContent();
        $data = json_decode($content, true);

        $logger->info("Request data: " . json_encode($data));

        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse(
                ["message" => "User not found"],
                Response::HTTP_NOT_FOUND,
                [],
                false
            );
        }

        // Update user fields
        if (!empty($data['first_name'])) {
            $user->setFirstName($data['first_name']);
        }
        if (!empty($data['last_name'])) {
            $user->setLastName($data['last_name']);
        }
        if (!empty($data['address'])) {
            $user->setAddress($data['address']);
        }
        if (!empty($data['password'])) {
            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $data['password'])
            );
        }

        // Check if there is nothing to update
        if (empty($data['first_name']) && empty($data['last_name']) && empty($data['address']) && empty($data['password'])) {
            return new JsonResponse(
                ["message" => "Nothing to update"],
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        // Update roles
        $roles = $user->getRoles();
        if (in_array("ROLE_ADMIN", $roles)) {
            $admin = $adminRepository->findOneBy(["email" => $user->getEmail()]);
            if ($admin) {
                if (!empty($data['first_name'])) {
                    $admin->setFirstName($data['first_name']);
                }
            } else {
                $logger->error("Admin not found for user ID: " . $id);
            }
        } else {
            $clientId = $user->getClientId();
            if (is_int($clientId)) {
                $client = $clientsRepository->find($clientId);
                if ($client) {
                    if (!empty($data['first_name'])) {
                        $client->setFirstName($data['first_name']);
                    }
                    if (!empty($data['last_name'])) {
                        $client->setLastName($data['last_name']);
                    }
                } else {
                    $logger->error("Client not found for client ID: " . $clientId);
                }
            } else {
                $logger->error("Client ID is not an integer for user ID: " . $id);
            }
        }

        $entityManager->flush();

        return new JsonResponse(
            ["message" => "Update successfully"],
            Response::HTTP_OK,
            [],
            false
        );
    }


    //Read user information
    #[Route('/{id}/show', name: 'user_show', methods: ['GET'])]
    public function show(
        User $user,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
    ): Response {
        // if (empty($this->getUser())) {
        //     return new JsonResponse(
        //         $serializer->serialize(["message" => "you're not connected"], 'json'),
        //         Response::HTTP_UNAUTHORIZED,
        //         [],
        //         true
        //     );
        // }
        $data = $serializer->serialize($user, "json");
        $userArray = json_decode($data, true);
        // Remove the password field
        unset($userArray['password']);
        unset($userArray['admin_id']);
        unset($userArray['client_id']['client_id']);
        unset($userArray['client_id']['firstname']);
        unset($userArray['client_id']['lastname']);
        unset($userArray['client_id']['email']);
        unset($userArray['client_id']['wallets']);
        $clientId = $userArray['id'];
        $clientData = $entityManager->getRepository(Wallets::class)->findAll(['client' => $clientId]);
        $crypto = [];
        foreach($clientData as $wallet){
            $transactionList = [];
            foreach ($wallet->getTransactions() as $transaction){
                $transaction->getTransactionType();
                $transaction->getPrice();
                $transaction->getTransactionDate();
                $transactionList[]= [
                    'transaction_type' => $transaction->getTransactionType(),
                    'quantity' => $transaction->getQuantity(),
                    'price' => $transaction->getPrice(),
                    'transaction_date' => $transaction->getTransactionDate()->format('Y-m-d H:i:s')
                ];
            }

            $cryptoList = [];
            foreach($wallet->getCrypto() as $value){
                $cryptoList[] = [
                    'id'=>$value->getId(),
                    'name'=>$value->getName(),
                    'symbol'=>$value->getSymbol(),
                ];
            }

            if(!empty($clientData)){
                $crypto[] = [
                    'id' => $wallet->getId(),
                    'crypto_info' => $cryptoList,
                    'quantity' => $wallet->getQuantity(),
                    'average_price' => $wallet->getAveragePurchasePrice(),
                    'transaction_history' => $transactionList
                ];
            }
        };

        $userArray['wallets'] =  $crypto;
        // dd($userArray);


        return new JsonResponse(
            $userArray,
            Response::HTTP_ACCEPTED,
            [],
            false
        );
    }

    //Delete user
    #[Route('/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function delete(User $user, EntityManagerInterface $entityManager): Response
    {
        // if(empty($this->getUser())){
        //     return new JsonResponse(
        //         ["message"=>"you're not connected, please login first"],
        //         Response::HTTP_UNAUTHORIZED,
        //         [],
        //         false
        //     );
        // }

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(
            ["message" =>"user deleted"],
            Response::HTTP_OK,
            [],
            false
        );
    }


    
}
