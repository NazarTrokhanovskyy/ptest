<?php

declare(strict_types=1);

namespace App;

use App\Enums\EuropeanCountries;
use App\Services\BinInfo;
use App\Services\ExchangeRatesApi;
use Monolog\Logger;
use SplFileObject;

final class Application
{
    const CURRENCY_EUR = 'EUR';

    const RATE_EUROPEAN = 0.01;

    const RATE_NON_EUROPEAN = 0.02;

    protected Logger $logger;

    protected ExchangeRatesApi $exchangeRatesApi;
    private BinInfo $binInfo;

    public function __construct(Logger $logger, ExchangeRatesApi $exchangeRatesApi, BinInfo $binInfo)
    {
        $this->logger = $logger;
        $this->exchangeRatesApi = $exchangeRatesApi;
        $this->binInfo = $binInfo;
    }

    public function process(string $inputFile): void
    {
        if (!$this->isFileAvailable($inputFile)) {
            printf("File %s doesn't exist or can`t be accessed. \n", $inputFile);
            return;
        }

        $output = '';

        $file = new SplFileObject($inputFile);
        while (!$file->eof()) {
            $line = $file->fgets();

            $record = json_decode($line, true);

            $bin = (int)($record['bin'] ?? 0);
            $amount = (float)($record['amount'] ?? 0);
            $currency = (string)($record['currency'] ?? '');

            $binInfo = $bin ? $this->binInfo->getBinInfo($bin) : [];

            //  Get currency rate
            $rate = $this->exchangeRatesApi->getCurrencyRate($currency);

            //  Notice: if the rate list is unavailable, then commissions will be the same as the amount (based on the code from the test task)
            $fixedAmount = ($currency !== self::CURRENCY_EUR && $rate <> 0) ? $amount / $rate : $amount;

            $countryCode = strtoupper($binInfo['country']['alpha2'] ?? '');
            $rate = in_array($countryCode, EuropeanCountries::cases())
                ? self::RATE_EUROPEAN
                : self::RATE_NON_EUROPEAN;

            $fixedAmount = number_format($fixedAmount * $rate, 2);

            $output .= $fixedAmount . "\n";
        }

        print $output;
    }

    public function isFileAvailable(string $inputFile): bool
    {
        return file_exists($inputFile);
    }
}
