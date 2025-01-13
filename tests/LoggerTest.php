<?php

declare(strict_types=1);

namespace Fansipan\Log\Tests;

use Fansipan\Log\HttpMessageFormatter;
use Fansipan\Log\Logger;
use Fansipan\Mock\MockClient;
use Fansipan\Mock\MockResponse;
use Fansipan\Request;
use Monolog\Handler\TestHandler;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

final class LoggerTest extends TestCase
{
    /**
     * @dataProvider provideTemplates
     */
    public function test_logger_middleware_success_request(string $template, string $needle): void
    {
        $logger = new Monolog('test', [
            $handler = new TestHandler(),
        ]);

        $messageFormatter = new HttpMessageFormatter($template);

        $connector = (new Connector())->withClient(new MockClient());
        $connector->middleware()->push(new Logger($logger, $messageFormatter));
        $connector->send($this->createTestRequest());

        $this->assertTrue($handler->hasInfoRecords());
        $this->assertTrue($handler->hasInfoThatContains($needle));
    }

    public static function provideTemplates(): iterable
    {
        yield 'clf' => [HttpMessageFormatter::CLF, '"GET / HTTP/1.1" 200'];
        yield 'short' => [HttpMessageFormatter::SHORT, '"GET / HTTP/1.1" 200'];
        yield 'debug' => [HttpMessageFormatter::DEBUG, '>>>>>>>>'];
        yield 'debug_json' => [HttpMessageFormatter::DEBUG_JSON, '<<<<<<<<'];
    }

    public function test_logger_level(): void
    {
        $logger = new Monolog('test', [
            $handler = new TestHandler(),
        ]);

        $messageFormatter = new HttpMessageFormatter(HttpMessageFormatter::SHORT);

        $connector = (new Connector())->withClient(new MockClient([
            MockResponse::create(''),
            MockResponse::create('', 403),
            MockResponse::create('', 500),
        ]));
        $connector->middleware()->push(new Logger($logger, $messageFormatter, [
            LogLevel::INFO => [200, 399],
            LogLevel::WARNING => [400, 499],
            LogLevel::ERROR => [500, 599],
        ]));
        $connector->send($this->createTestRequest());

        $this->assertTrue($handler->hasInfoRecords());
        $this->assertFalse($handler->hasWarningRecords());
        $this->assertFalse($handler->hasErrorRecords());

        $connector->send($this->createTestRequest());

        $this->assertTrue($handler->hasWarningRecords());
        $this->assertFalse($handler->hasErrorRecords());

        $connector->send($this->createTestRequest());

        $this->assertTrue($handler->hasErrorRecords());
    }

    private function createTestRequest(): Request
    {
        return new class () extends Request {
            public function endpoint(): string
            {
                return '/';
            }

            public function method(): string
            {
                return 'GET';
            }
        };
    }
}
