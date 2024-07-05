<?php

namespace App\Controller\api;

use App\Entity\Clients;
use App\Entity\Transactions;
use App\Entity\Wallets;
use App\Repository\CryptoCurrenciesRepository;
use App\Repository\WalletsRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TransactionsController extends AbstractController
{
    #[Route('/api/buycrypto', name: 'app_transactions_buy', methods: ['POST'])]
    public function Buy(
        SerializerInterface $serializer,
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        CryptoCurrenciesRepository $cryptoCurrenciesRepository
    ): JsonResponse {
        // Désérialiser le contenu de la requête
        $content = $request->getContent();
        $data = json_decode($content, true);
        $buy = new Transactions();

        // Définir les propriétés de la transaction
        $buy->setTransactionType('BUY');
        $buy->setTransactionDate(new DateTime());

        // Obtenir l'ID du client et ses données de portefeuille
        $clientId = $data['id'];
        $clientData = $entityManager->getRepository(Wallets::class)->findAll(['client' => $clientId]);
        $cryptoFound = false;

        foreach ($clientData as $wallet) {
            $crypto = $cryptoCurrenciesRepository->findOneBy(['WalletsCrypto' => $wallet->getId()]);
            if ($crypto && $crypto->getName() == $data['name']) {
                $cryptoFound = true;
                $buy->setWalletId($wallet);

                // Mise à jour du solde du client
                $updateBalance = $entityManager->getRepository(Clients::class)->find($clientId);
                if ((float)$updateBalance->getBalance() - (float)$data['price'] >= 0) {
                    $updateBalance->setBalance((float)$updateBalance->getBalance() - (float)$data['price']);
                    $wallet->setAveragePurchasePrice((float)$wallet->getAveragePurchasePrice() + (float)$data['price']);
                    $wallet->setQuantity((float)$wallet->getQuantity() + (float)$data['quantity']);

                    // Définir la quantité et le prix dans l'entité Transactions
                    $buy->setQuantity((float)$data['quantity']);
                    $buy->setPrice((float)$data['price']);
                } else {
                    return new JsonResponse(
                        ["message" => "insufficient funds"],
                        Response::HTTP_BAD_REQUEST,
                        [],
                        false
                    );
                }
            }
        }

        if (!$cryptoFound) {
            return new JsonResponse(
                ['message' => 'no wallet found'],
                Response::HTTP_BAD_REQUEST,
                [],
                false
            );
        }

        // Validation des données de la transaction
        $error = $validator->validate($buy);
        if (count($error) > 0) {
            return new JsonResponse(
                $serializer->serialize($error, 'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        // Enregistrer la transaction dans la base de données
        $entityManager->persist($buy);
        $entityManager->flush();

        // Retourner une réponse de succès
        return new JsonResponse(
            $serializer->serialize($buy, 'json'),
            Response::HTTP_CREATED,
            [],
            true
        );
    }


    
    #[Route('/api/sellcrypto', name: 'app_transactions_sell', methods: ['POST'])]
    public function SELL(
        SerializerInterface $serializer,
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifier si l'utilisateur est connecté
        if (empty($this->getUser())) {
            return new JsonResponse(
                ['message' => 'please login'],
                Response::HTTP_UNAUTHORIZED,
                ['accept' => 'application/json']
            );
        }

        // Désérialiser le contenu de la requête
        $sell = $serializer->deserialize($request->getContent(), Transactions::class, 'json');

        // Définir les propriétés de la transaction
        $sell->setTransactionType('SELL');
        $sell->setTransactionDate(new DateTime());

        // Obtenir l'ID du client et ses données de portefeuille
        $clientId = $this->getUser()->getClientId()->getId();
        $clientData = $entityManager->getRepository(Wallets::class)->findOneBy(['client' => $clientId]);
        $sell->setWalletId($clientData);
        $wallet = $sell->getWalletId();
        
        if ($wallet == null) {
            // Handle the case where there's no wallet associated with this transaction
            // Perhaps log an error or return an appropriate response.
            return new JsonResponse(
                ['message' => 'no wallet found'],
                Response::HTTP_BAD_REQUEST,
                [],
                false // Permettre au sérialiseur de gérer le format JSON
            );
        }
        
        
        
        //update the client balance
        $updateBalance = $entityManager->getRepository(Clients::class)->find($clientId);
        if ($sell->getQuantity() - $clientData->getQuantity() <= 0) {
            $clientData->setQuantity(abs($clientData->getQuantity() - $sell->getQuantity()));
            $clientData->setAveragePurchasePrice(abs($clientData->getAveragePurchasePrice() - $sell->getPrice()));
            $updateBalance->setBalance($updateBalance->getBalance() + $sell->getPrice());
            
        } else {
            return new JsonResponse(
                ["message" => "you don't have enough crypto to sell"],
                Response::HTTP_BAD_REQUEST,
                [],
                false
            );
        }
        // Valider les données de la transaction
        $error = $validator->validate($sell);
        if (count($error) > 0) {
            return new JsonResponse(
                $serializer->serialize($error, 'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true // Permettre au sérialiseur de gérer le format JSON
            );
        }

        

        $entityManager->persist($sell);
        $entityManager->flush();
        

        // Retourner une réponse de succès
        return new JsonResponse(
            ['message' => 'payment successful'],
            Response::HTTP_CREATED,
            [],
            false // Ceci doit être true car nous utilisons JsonResponse sans pré-sérialisation
        );
    }
}
