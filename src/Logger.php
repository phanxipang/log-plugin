<?php

declare(strict_types=1);

namespace Fansipan\Log;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class Logger
{
    public const DEFAULT_LOG_LEVELS = [
        LogLevel::INFO => [200, 399],
        LogLevel::ERROR => [400, 499],
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MessageFormatter
     */
    private $formatter;

    /**
     * @var array<string, int|array{0: int, 1: int}>
     */
    private $logLevels = [];

    /**
     * @var array<int, string>
     */
    private $cache = [];

    /**
     * @param  array<string, int|array{0: int, 1: int}> $logLevels
     */
    public function __construct(
        LoggerInterface $logger,
        MessageFormatter $formatter,
        array $logLevels = self::DEFAULT_LOG_LEVELS
    ) {
        $this->logger = $logger;
        $this->formatter = $formatter;
        $this->logLevels = $logLevels;
    }

    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        $start = hrtime(true) / 1E6;

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $this->logger->error($this->formatter->format($request, null, $e));
            throw $e;
        }

        $milliseconds = (int) \round(hrtime(true) / 1E6 - $start);
        $code = $response->getStatusCode();

        if (isset($this->cache[$code])) {
            $level = $this->cache[$code];
        } else {
            $level = $this->cache[$code] = $this->logLevel($code);
        }

        $this->logger->log($level, $this->formatter->format($request, $response), compact('milliseconds'));

        return $response;
    }

    private function logLevel(int $code): string
    {
        foreach ($this->logLevels as $level => $value) {
            if (\is_integer($value) && $value === $code) {
                return $level;
            }

            if (! \is_array($value)) {
                continue;
            }

            if (\count($value) === 2 && \in_array($code, \range($value[0], $value[1]), true)) {
                return $level;
            }
        }

        return LogLevel::INFO;
    }
}
