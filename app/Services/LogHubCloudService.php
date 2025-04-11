<?php
namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Session;

class LogHubCloudService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(['base_uri' => 'https://api.loghub.cloud', 'verify' => false]);
    }

    public function logger($content)
    {
        $response = $this->client->post('/log', [
            'headers' => [
                'Authorization' => 'Bearer c1c24925bf3872176cbb95a6b63b6c249ee9292c318a49848d2c68f5e1258541',
                'Content-Type' => 'application/json',
                'User-Agent' => 'cURL-Client/1.0.0'
            ],
            'json' => $content,
        ]);

        $data = json_decode($response->getBody(), true);

        if (isset($data['status'])) {
            if ($data['status'] == 'ok')
                return true;
        }
        return false;
    }

    public function me()
    {
        $response = $this->client->get('/api/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('token')
            ]
        ]);

        return json_decode($response->getBody(), true);
    }
}
