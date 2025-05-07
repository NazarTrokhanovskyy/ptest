<?php

declare(strict_types=1);

namespace App\Services;

use Monolog\Logger;

final class BinInfo
{
    protected Logger $logger;
    private array $binInfo;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function getBinInfo(int $bin): array
    {
        if(!empty($this->binInfo[$bin])) {
            $binResult = $this->binInfo[$bin];
        } else {
            $binResult = [];
            $lookupBinListUrl = 'https://lookup.binlist.net/' . $bin;
            if ($response = file_get_contents($lookupBinListUrl)) {
                $binResult = json_decode($response, true);
                $this->binInfo[$bin] = $binResult;
                $this->logger->debug('$binResults', [$binResult]);
            } else {
                $this->logger->debug('No results for : ', [$lookupBinListUrl]);
            }
        }

        return $binResult;
    }
}
