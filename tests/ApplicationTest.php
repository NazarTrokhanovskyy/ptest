<?php

declare(strict_types=1);

namespace App;

use App\Services\BinInfo;
use App\Services\ExchangeRatesApi;
use Dotenv\Dotenv;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use SplFileObject;

final class ApplicationTest extends TestCase
{
    protected string $inputData;

    protected array $inputArr;

    public function setUp(): void
    {
        parent::setUp();

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../', '.env.testing');
        $dotenv->load();

        $this->inputData = '{"bin":"45717360","amount":"100.00","currency":"EUR"}' . "\n"
            . '{"bin":"516793","amount":"50.00","currency":"USD"}' . "\n"
            . '{"bin":"45417360","amount":"10000.00","currency":"JPY"}' . "\n"
            . '{"bin":"41417360","amount":"130.00","currency":"USD"}' . "\n"
            . '{"bin":"4745030","amount":"2000.00","currency":"GBP"}';
    }

    public function testIsFileAvailableFalse(): void
    {
        $logger = $this->createMock(Logger::class);

        $application = new Application($logger, new ExchangeRatesApi(), new BinInfo($logger));

        $fileLocation = './test.txt';

        $this->assertFalse($application->isFileAvailable($fileLocation));
    }

    public function testIsFileAvailableTrue(): void
    {
        $logger = $this->createMock(Logger::class);

        $application = new Application($logger, new ExchangeRatesApi(), new BinInfo($logger));

        $fileLocation = __DIR__ . '/test.txt';
        fopen($fileLocation, 'w+');

        $this->assertTrue($application->isFileAvailable($fileLocation));

        unlink($fileLocation);
    }

    public function testProcessFileUnavailable()
    {
        $logger = $this->createMock(Logger::class);

        $application = new Application($logger, new ExchangeRatesApi(), new BinInfo($logger));

        $fileLocation = './test.txt';

        $application->process($fileLocation);
        $this->expectOutputString("File ./test.txt doesn't exist or can`t be accessed. \n");
    }

    public function testProcessFileWithDataEmptyRates(): void
    {
        $logger = $this->createMock(Logger::class);

        $application = new Application($logger, new ExchangeRatesApi(), new BinInfo($logger));

        $fileLocation = __DIR__ . '/input.txt';
        $file = new SplFileObject($fileLocation, 'w+');

        $file->fwrite($this->inputData);

        $application->process($fileLocation);

        $expectedMessage = "1.00\n0.50\n200.00\n2.60\n40.00\n0.00\n";
        $this->expectOutputString($expectedMessage);

        unlink($fileLocation);
    }

    public function testProcessFileWithDataMockedRates(): void
    {
        $logger = $this->createMock(Logger::class);

        $exchangeRatesApi = new ExchangeRatesApi();
        $exchangeRatesApi->setRates(
            [
            'EUR' => 1,
            'USD' => 1.136293,
            'JPY' => 128.203376,
            'GBP' => 0.851543
            ]
        );

        $application = new Application($logger, $exchangeRatesApi, new BinInfo($logger));

        $fileLocation = __DIR__ . '/input.txt';
        $file = new SplFileObject($fileLocation, 'w+');

        $file->fwrite($this->inputData);

        $application->process($fileLocation);

        $expectedMessage = "1.00\n0.44\n1.56\n2.29\n46.97\n0.00\n";
        $this->expectOutputString($expectedMessage);

        unlink($fileLocation);
    }
}
