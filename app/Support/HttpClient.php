<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\AppException;

class HttpClient
{
    private int $timeout;
    private array $headers = [];

    public function __construct(int $timeout = 30, array $headers = [])
    {
        $this->timeout = $timeout;
        $this->headers = $headers;
    }

    public function post(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $url, $data, $headers);
    }

    public function get(string $url, array $queryParams = [], array $headers = []): array
    {
        if (!empty($queryParams)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($queryParams);
        }
        return $this->request('GET', $url, [], $headers);
    }

    private function request(string $method, string $url, array $data = [], array $headers = []): array
    {
        $ch = curl_init();

        $allHeaders = array_merge($this->headers, $headers);
        $headerList = [];
        foreach ($allHeaders as $k => $v) {
            $headerList[] = is_numeric($k) ? $v : "{$k}: {$v}";
        }

        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headerList,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'SMM-Panel-Engine/1.0',
        ];

        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            // Check if JSON or form-urlencoded
            $isJson = false;
            foreach ($headerList as $h) {
                if (stripos($h, 'application/json') !== false) {
                    $isJson = true;
                    break;
                }
            }
            $opts[CURLOPT_POSTFIELDS] = $isJson ? json_encode($data) : http_build_query($data);
        }

        curl_setopt_array($ch, $opts);

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0) {
            Logger::error("cURL error connecting to [{$url}]: {$error}", ['errno' => $errno], 'provider');
            throw new AppException("Provider connection failed: {$error}");
        }

        $decoded = json_decode((string)$responseBody, true);

        return [
            'status_code' => $httpCode,
            'body' => $responseBody,
            'data' => is_array($decoded) ? $decoded : null,
        ];
    }
}
