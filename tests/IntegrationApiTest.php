<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class IntegrationApiTest extends TestCase
{
    protected static int $serverPid;

    public static function setUpBeforeClass(): void
    {
        // Start the built-in PHP server in the background, serving from the public directory on port 8000.
        $output = [];
        exec('php -S localhost:8000 -t public > /dev/null 2>&1 & echo $!', $output);
        self::$serverPid = (int)$output[0];
        // Allow the server some time to start.
        sleep(2);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$serverPid) {
            exec('kill ' . self::$serverPid);
        }
    }

    public function testApiEndpoint(): void
    {
        // Send a request to the API endpoint served by the built-in PHP server.
        $response = @file_get_contents('http://localhost:8000');
        $this->assertNotFalse($response, 'API did not return a response');
        $this->assertGreaterThan(0, strlen($response), 'API response is empty');
    }
}
