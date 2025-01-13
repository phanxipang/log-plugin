<?php

declare(strict_types=1);

namespace Fansipan\Log;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class HttpMessageFormatter implements MessageFormatter
{
    public const CLF = '{hostname} {req.header_User-Agent} - [{date_common_log}] "{method} {target} HTTP/{version}" {code} {res.header_Content-Length}';
    public const DEBUG = ">>>>>>>>\n{request}\n<<<<<<<<\n{response}\n--------\n{error}";
    public const DEBUG_JSON = ">>>>>>>>\n{request:json}\n<<<<<<<<\n{response:json}\n--------\n{error}";
    public const SHORT = '[{ts}] "{method} {target} HTTP/{version}" {code}';

    /**
     * @var string
     */
    protected $template;

    /**
     * @var string
     */
    protected $binaryDetectionRegex;

    public function __construct(
        ?string $template = null,
        string $binaryDetectionRegex = '/([\x00-\x09\x0C\x0E-\x1F\x7F])/'
    ) {
        $this->template = $template ?? self::DEBUG;
        $this->binaryDetectionRegex = $binaryDetectionRegex;
    }

    public function format(RequestInterface $request, ?ResponseInterface $response = null, ?\Throwable $error = null): string
    {
        $cache = [];

        return \preg_replace_callback(
            '/{\s*([A-Za-z_\-\:\.0-9]+)\s*}/',
            function (array $matches) use ($request, $response, $error, &$cache) {
                if (isset($cache[$matches[1]])) {
                    return $cache[$matches[1]];
                }

                $result = '';
                switch ($matches[1]) {
                    case 'request:json':
                        $result = \json_encode([
                            'method' => \trim($request->getMethod()),
                            'uri' => (string) $request->getUri(),
                            'path' => $request->getRequestTarget(),
                            'version' => $request->getProtocolVersion(),
                            'headers' => $request->getHeaders(),
                            'body' => $this->body($request->getBody()),
                        ]);
                        break;
                    case 'response:json':
                        $result = $response ?
                            \json_encode([
                                'version' => $response->getProtocolVersion(),
                                'status' => \sprintf('%d %s', $response->getStatusCode(), $response->getReasonPhrase()),
                                'headers' => $response->getHeaders(),
                                'body' => $this->body($response->getBody()),
                            ])
                            : 'NULL';
                        break;
                    case 'request':
                        $result = \trim($request->getMethod()
                                .' '.$request->getRequestTarget())
                            .' HTTP/'.$request->getProtocolVersion()."\r\n"
                            .$this->headers($request)."\r\n\r\n"
                            .$this->body($request->getBody());
                        break;
                    case 'response':
                        $result = $response ?
                            \sprintf(
                                'HTTP/%s %d %s',
                                $response->getProtocolVersion(),
                                $response->getStatusCode(),
                                $response->getReasonPhrase()
                            )."\r\n".$this->headers($response)."\r\n\r\n"
                            .$this->body($response->getBody())
                            : 'NULL';
                        break;
                    case 'req.headers':
                        $result = \trim($request->getMethod()
                                .' '.$request->getRequestTarget())
                            .' HTTP/'.$request->getProtocolVersion()."\r\n"
                            .$this->headers($request);
                        break;
                    case 'res.headers':
                        $result = $response ?
                            \sprintf(
                                'HTTP/%s %d %s',
                                $response->getProtocolVersion(),
                                $response->getStatusCode(),
                                $response->getReasonPhrase()
                            )."\r\n".$this->headers($response)
                            : 'NULL';
                        break;
                    case 'req.body':
                        $result = $this->body($request->getBody());
                        break;
                    case 'res.body':
                        if (! $response instanceof ResponseInterface) {
                            $result = 'NULL';
                            break;
                        }

                        $result = $this->body($response->getBody());
                        break;
                    case 'ts':
                    case 'date_iso_8601':
                        $result = \gmdate('c');
                        break;
                    case 'date_common_log':
                        $result = \date('d/M/Y:H:i:s O');
                        break;
                    case 'method':
                        $result = $request->getMethod();
                        break;
                    case 'version':
                        $result = $request->getProtocolVersion();
                        break;
                    case 'uri':
                    case 'url':
                        $result = (string) $request->getUri();
                        break;
                    case 'target':
                        $result = $request->getRequestTarget();
                        break;
                    case 'req.version':
                        $result = $request->getProtocolVersion();
                        break;
                    case 'res.version':
                        $result = $response
                            ? $response->getProtocolVersion()
                            : 'NULL';
                        break;
                    case 'host':
                        $result = $request->getHeaderLine('Host');
                        break;
                    case 'hostname':
                        $result = \gethostname();
                        break;
                    case 'code':
                        $result = $response ? $response->getStatusCode() : 'NULL';
                        break;
                    case 'phrase':
                        $result = $response ? $response->getReasonPhrase() : 'NULL';
                        break;
                    case 'error':
                        $result = $error ? $error->getMessage() : 'NULL';
                        break;
                    default:
                        // handle prefixed dynamic headers
                        if (\strpos($matches[1], 'req.header_') === 0) {
                            $result = $request->getHeaderLine(\substr($matches[1], 11));
                        } elseif (\strpos($matches[1], 'res.header_') === 0) {
                            $result = $response
                                ? $response->getHeaderLine(\substr($matches[1], 11))
                                : 'NULL';
                        }
                }

                $cache[$matches[1]] = $result;

                return $result;
            },
            $this->template
        ) ?? '';
    }

    protected function headers(MessageInterface $message): string
    {
        $result = '';
        foreach ($message->getHeaders() as $name => $values) {
            $result .= $name.': '.\implode(', ', $values)."\r\n";
        }

        return \trim($result);
    }

    protected function body(StreamInterface $stream): string
    {
        if (! $stream->isSeekable()) {
            return '[RESPONSE_NOT_LOGGEABLE]';
        }

        $data = (string) $stream;
        $stream->rewind();

        if (\preg_match($this->binaryDetectionRegex, $data)) {
            return '[BINARY_STREAM_OMITTED]';
        }

        return $data;
    }
}
