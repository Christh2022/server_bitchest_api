<?php

namespace App\Controller\api;

use App\Entity\Clients;
use App\Entity\CryptoCurrencies;
use App\Entity\Wallets;
use App\Entity\User;
use App\Repository\WalletsRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WalletController extends AbstractController
{
    #[Route('/api/wallet/add-crypto', name: 'api_wallet_add_crypto', methods: ['POST'])]
    public function AddCrypto(
        SerializerInterface $serializer,
        Request $request,
        WalletsRepository $walletsRepo,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): Response {
        // Check if the user is authenticated
        if (!$this->getUser()) {
            return new JsonResponse(
                $serializer->serialize(["message" => "You are not logged in"], 'json'),
                Response::HTTP_UNAUTHORIZED,
                [],
                true
            );
        }

        // Deserialize the request content into Wallets entity
        $wallet = $serializer->deserialize($request->getContent(), Wallets::class, 'json');
        $data = json_decode($request->getContent(), true);
        $cryptoRepo = $entityManager->getRepository(CryptoCurrencies::class);

        // Get the authenticated user
        $user = $this->getUser();
        if ($user->getRoles()[0] == "ROLE_ADMIN") {
            return new JsonResponse(
                ["message" => "You're not allowed to get a wallet administrator"],
                Response::HTTP_UNAUTHORIZED,
                [],
                false
            );
        }

        $clientId = $user->getClientId()->getId();
        $clientData = $entityManager->getRepository(Clients::class)->find($clientId);
        $wallet->setClient($clientData);

        // Validate the entity
        $errors = $validator->validate($wallet);
        // If there are validation errors, return a response with the errors
        if (count($errors) > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        if (!empty($data['cryptoCurrencies'])) {
            $cryptoId = $data['cryptoCurrencies'];
            $cryptoCurrency = $cryptoRepo->find($cryptoId);
            if ($cryptoCurrency) {
                $crypto = $cryptoRepo->find($cryptoId);
                if($crypto->getWalletsCrypto()){
                    if($crypto->getWalletsCrypto()->getClient()->getId() == $clientId){
                        return new JsonResponse(
                            ["message" => "crypto has been found in your wallet"]
                        );
                    }
                }
                $crypto->setWalletsCrypto($wallet);
                // dd($crypto);
                
            } else {
                return new JsonResponse(['error' => 'Invalid cryptocurrency ID'], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // If validation passed, persist the entity
        $entityManager->persist($wallet);
        $entityManager->flush();

        // Return a success response
        return new JsonResponse(
            $serializer->serialize(["message" => "Crypto added successfully"], 'json'),
            Response::HTTP_CREATED,
            [],
            true
        );
    }
}
