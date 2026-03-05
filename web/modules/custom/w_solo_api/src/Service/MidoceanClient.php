<?php

namespace Drupal\w_solo_api\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class MidoceanClient {

  public function __construct(
    protected ClientInterface $httpClient,
    protected LoggerInterface $logger,
  ) {}

  public function getProducts(string $language = 'en'): array {
    try {
      $response = $this->httpClient->request('GET', 'https://api.midocean.com/gateway/products/2.0', [
        'query' => ['language' => $language],
        'headers' => [
          // Use the header name expected by the API:
          'x-Gateway-APIKey' => $this->getApiKey(),
          'Accept' => 'application/json',
        ],
        'timeout' => 50,
      ]);

      $body = (string) $response->getBody();
      $data = json_decode($body, TRUE);

      // json_decode() returns null on invalid JSON.
      if (!is_array($data)) {
        $this->logger->error('Midocean API returned invalid JSON. Body: @body', [
          '@body' => mb_substr($body, 0, 2000),
        ]);
        return [];
      }

      return $data;
    }
    catch (RequestException $e) {
      $status = $e->getResponse()?->getStatusCode();
      $error_body = $e->getResponse() ? (string) $e->getResponse()->getBody() : '';

      $this->logger->error('Midocean API request failed (@status): @message. Body: @body', [
        '@status' => $status ?? 'n/a',
        '@message' => $e->getMessage(),
        '@body' => mb_substr($error_body, 0, 2000),
      ]);

      return [];
    }
    catch (\Throwable $e) {
      $this->logger->error('Midocean API unexpected error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  public function getPrintData(string $language = 'en'): array {
    try {
      $response = $this->httpClient->request('GET', 'https://api.midocean.com/gateway/printdata/1.0', [
        'query' => ['language' => $language],
        'headers' => [
          // Use the header name expected by the API:
          'x-Gateway-APIKey' => $this->getApiKey(),
          'Accept' => 'application/json',
        ],
        'timeout' => 50,
      ]);

      $body = (string) $response->getBody();
      $data = json_decode($body, TRUE);

      // json_decode() returns null on invalid JSON.
      if (!is_array($data)) {
        $this->logger->error('Midocean API returned invalid JSON. Body: @body', [
          '@body' => mb_substr($body, 0, 2000),
        ]);
        return [];
      }

      return $data;
    }
    catch (RequestException $e) {
      $status = $e->getResponse()?->getStatusCode();
      $error_body = $e->getResponse() ? (string) $e->getResponse()->getBody() : '';

      $this->logger->error('Midocean API request failed (@status): @message. Body: @body', [
        '@status' => $status ?? 'n/a',
        '@message' => $e->getMessage(),
        '@body' => mb_substr($error_body, 0, 2000),
      ]);

      return [];
    }
    catch (\Throwable $e) {
      $this->logger->error('Midocean API unexpected error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  private function getApiKey(): string {
    // Prefer settings.php:
    // $settings['midocean_api_key'] = '...';
    $key = \Drupal::service('settings')->get('midocean_api_key');


    return '41e1592a-7bef-407e-ab83-e45d4279ad2d';
    // Avoid passing null/empty keys to Guzzle.
    return is_string($key) ? $key : '';
  }

}
