<?php

namespace QuickWP\Http;

/**
 * Minimal REST client abstraction around cURL.
 * Consolidates all HTTP communication for WordPress REST API.
 */
class RestClient
{
    private int $timeout = 30;
    private int $connectTimeout = 10;

    /**
     * Set request timeout.
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Set connection timeout.
     */
    public function setConnectTimeout(int $seconds): self
    {
        $this->connectTimeout = $seconds;
        return $this;
    }

    /**
     * Build Basic Auth header value.
     */
    private function buildAuthHeader(string $username, string $appPassword): string
    {
        return 'Basic ' . base64_encode($username . ':' . $appPassword);
    }

    /**
     * Normalize cURL response into a standard array.
     */
    private function buildResponse($ch, string $responseBody, array $headers = []): array
    {
        $curlErrNo = curl_errno($ch);
        $curlErr = $curlErrNo ? curl_error($ch) : null;
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $info = curl_getinfo($ch);

        curl_close($ch);

        return [
            'ok' => !$curlErrNo && $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'curl_error' => $curlErr,
            'raw_body' => $responseBody,
            'info' => $info,
            'json' => $responseBody ? json_decode($responseBody, true) : null,
            'headers' => $headers,
        ];
    }

    /**
     * Parse response headers into associative array.
     */
    private function parseHeaders(string $headerString): array
    {
        $headers = [];
        foreach (explode("\r\n", $headerString) as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }
        return $headers;
    }

    /**
     * Perform a GET request.
     */
    public function get(string $url, string $username, string $appPassword, bool $verifySsl = true): array
    {
        $ch = curl_init($url);

        $responseHeaders = '';
        
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->buildAuthHeader($username, $appPassword),
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
            CURLOPT_HEADERFUNCTION => function($ch, $header) use (&$responseHeaders) {
                $responseHeaders .= $header;
                return strlen($header);
            },
        ]);

        $responseBody = curl_exec($ch);
        $headers = $this->parseHeaders($responseHeaders);

        return $this->buildResponse($ch, (string) $responseBody, $headers);
    }

    /**
     * Perform a POST request with JSON payload.
     */
    public function postJson(string $url, array $payload, string $username, string $appPassword, bool $verifySsl = true): array
    {
        $ch = curl_init($url);

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Authorization: ' . $this->buildAuthHeader($username, $appPassword),
            ],
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
        ]);

        $responseBody = curl_exec($ch);

        return $this->buildResponse($ch, (string) $responseBody);
    }

    /**
     * Perform a PUT/PATCH request with JSON payload (used for updates).
     */
    public function putJson(string $url, array $payload, string $username, string $appPassword, bool $verifySsl = true, string $method = 'POST'): array
    {
        $ch = curl_init($url);

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Authorization: ' . $this->buildAuthHeader($username, $appPassword),
            ],
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
        ]);

        $responseBody = curl_exec($ch);

        return $this->buildResponse($ch, (string) $responseBody);
    }

    /**
     * Perform an OPTIONS request to get API schema.
     */
    public function options(string $url, string $username, string $appPassword, bool $verifySsl = true): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'OPTIONS',
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->buildAuthHeader($username, $appPassword),
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
        ]);

        $responseBody = curl_exec($ch);

        return $this->buildResponse($ch, (string) $responseBody);
    }

    /**
     * Perform a DELETE request.
     */
    public function delete(string $url, string $username, string $appPassword, bool $verifySsl = true, bool $force = false): array
    {
        $deleteUrl = $force ? $url . (strpos($url, '?') === false ? '?' : '&') . 'force=true' : $url;

        $ch = curl_init($deleteUrl);

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->buildAuthHeader($username, $appPassword),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
        ]);

        $responseBody = curl_exec($ch);

        return $this->buildResponse($ch, (string) $responseBody);
    }

    /**
     * Upload a file (multipart/form-data).
     */
    public function uploadFile(
        string $url,
        array $fileInfo,
        array $additionalFields,
        string $username,
        string $appPassword,
        bool $verifySsl = true
    ): array {
        if (empty($fileInfo['tmp_name']) || !is_uploaded_file($fileInfo['tmp_name'])) {
            return [
                'ok' => false,
                'http_code' => 0,
                'curl_error' => 'No valid file uploaded.',
                'raw_body' => null,
                'info' => null,
                'json' => null,
            ];
        }

        $ch = curl_init($url);

        $filename = $fileInfo['name'] ?? 'upload';
        $mime = $fileInfo['type'] ?? 'application/octet-stream';

        $curlFile = new \CURLFile($fileInfo['tmp_name'], $mime, $filename);

        $postFields = array_merge($additionalFields, [
            'file' => $curlFile,
        ]);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->buildAuthHeader($username, $appPassword),
                'Content-Disposition: attachment; filename="' . $filename . '"',
            ],
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
        ]);

        $responseBody = curl_exec($ch);

        return $this->buildResponse($ch, (string) $responseBody);
    }
}
