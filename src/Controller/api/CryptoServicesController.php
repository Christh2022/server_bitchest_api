<?php

namespace App\Controller\api;

use App\Entity\CryptoCurrencies;
use App\Entity\CryptoPrices;
use App\Repository\CryptoCurrenciesRepository;
use App\Repository\CryptoPricesRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route("/api")]
class CryptoServicesController extends AbstractController
{
    #[Route('/crypto/services', name: 'app_crypto_services', methods: ['POST'])]
    public function index(
        SerializerInterface $serializer,
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();
        if (empty($user)) {
            return new JsonResponse(
                $serializer->serialize(['message' => 'please login'], 'json'),
                Response::HTTP_UNAUTHORIZED,
                ['accept' => 'application/json'],
                false // Changed to false because we are already serializing the data
            );
        }

        $crypto = $serializer->deserialize($request->getContent(), CryptoCurrencies::class, 'json');
        $error = $validator->validate($crypto);

        $cryptoRepo = $entityManager->getRepository(CryptoCurrencies::class)->findOneBy(["name"=> $crypto->getName()]);
        if($cryptoRepo){
            return new JsonResponse(
                ['message' => 'crypto already exists'], 
                Response::HTTP_BAD_REQUEST,
                ['accept' => 'application/json'],
                false 
            );
        }

        if (count($error) > 0) {
            return new JsonResponse(
                $serializer->serialize($error, "json"),
                Response::HTTP_BAD_REQUEST,
                ['accept' => 'application/json'],
                false // Changed to false because we are already serializing the data
            );
        }

        $now = new DateTime();
        $data = json_decode($request->getContent(), true);
        
        $cryptoPrice = new CryptoPrices();
        $cryptoPrice->setDate($now);
        $cryptoPrice->setPrice($data['price']);
        $crypto->setCryptoPrices($cryptoPrice);



        $entityManager->persist($cryptoPrice);
        $entityManager->persist($crypto);
        $entityManager->flush();

        // Serialize the objects before passing to JsonResponse
        $responseData = $serializer->serialize([$crypto, $cryptoPrice], "json");

        return new JsonResponse(
            $responseData,
            Response::HTTP_OK,
            ['accept' => 'application/json'],
            true 
        );
    }

    // Function to retrieve cryptocurrency data from the CoinMarketCap API
    #[Route('/crypto/list', name: 'list_crypto', methods:['GET'])]
    public function listCrypto(
        CryptoCurrenciesRepository $cryptoCurrenciesRepo,
        SerializerInterface $serializer
    ):Response
    {
        $cryptoLit = $cryptoCurrenciesRepo->findAll();

        $cryptoArray = array();

        foreach ($cryptoLit as $crypto){
            $data = $serializer->serialize($crypto, "json");
            $newCrypto = json_decode($data, true);
            unset($newCrypto['_wallets_crypto'], $newCrypto['wallets'], $newCrypto['crypto_prices']['crypto_id']);
            $cryptoArray[] = $newCrypto;
        }

        return new JsonResponse(
            $cryptoArray,
            Response::HTTP_ACCEPTED,
            [],
            false
        );
    }

    #[Route('/crypto/currentPrice', name: 'crypto_currentPrice', methods: ['POST'])]
    public function currentPriceAction(
        CryptoPricesRepository $cryptoPricesRepo,
        Request $request,
        EntityManagerInterface $entityManager
    ):Response
    {
        $data = json_decode($request->getContent(), true);

        if($data['id'] && ($data['price'])){
            $crypto = $cryptoPricesRepo->find($data['id']);
            $crypto->setPrice($data['price']);
            $crypto->setDate(new DateTime());

            $entityManager->flush();
        }

        return new JsonResponse(
            [],
            Response::HTTP_NO_CONTENT,
            [],
            false
        );
    }
    
}
