<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CryptoPriceService
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'base_uri' => 'https://api.coingecko.com/api/v3/',
            'verify' => __DIR__ . '/../../config/certs/cacert.pem', // Chemin vers le certificat
        ]);
    }

    public function getCurrentPrices(array $cryptos): array
    {
        try {
            $response = $this->httpClient->request('GET', 'simple/price', [
                'query' => [
                    'ids' => implode(',', $cryptos),
                    'vs_currencies' => 'usd',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody()->getContents(), true);
            }
        } catch (RequestException $e) {
            // Gérer les erreurs de requête (par exemple, le service est inaccessible)
            return ['error' => $e->getMessage()];
        }

        return [];
    }
}
