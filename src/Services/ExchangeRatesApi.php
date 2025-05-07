<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Monolog\Logger;

final class ExchangeRatesApi
{
    protected string $exchangeRatesUrl;
    protected string $exchangeRatesKey;
    private array $rates = [];

    protected Logger $logger;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->exchangeRatesUrl = $_ENV['EXCHANGE_RATES_URL'] ?? 'http://api.exchangeratesapi.io';

        if(is_null($_ENV['EXCHANGE_RATES_KEY'])) {
            throw new Exception('Exchange rates API key is not set');
        }
        $this->exchangeRatesKey = $_ENV['EXCHANGE_RATES_KEY'];

        $this->getRates();
    }

    public function getRates(): array
    {
        $url = sprintf('%s/v1/latest?access_key=%s', $this->exchangeRatesUrl, $this->exchangeRatesKey);
        try {
            if ($response = @file_get_contents($url)) {
                $data = json_decode($response, true);
                $this->rates = $data['rates'] ?? [];
            }
        } catch (Exception $exception) {
            $this->logger->debug('No results for : ', [$url]);
            $this->logger->error('Error : ', [$exception]);
        }

        return $this->rates;
    }

    public function setRates(array $rates): void
    {
        $this->rates = $rates;
    }

    public function getCurrencyRate(string $currency): float
    {
        return !empty($this->rates[$currency]) ? (float) $this->rates[$currency] : 0;
    }
}
