<?php

namespace Expose\Client\Logger;

use Expose\Client\Logger\Concerns\PluginAware;
use Expose\Client\Logger\Plugins\PluginData;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Psr7\Message;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laminas\Http\Header\GenericHeader;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Namshi\Cuzzle\Formatter\CurlFormatter;
use Riverline\MultiPartParser\StreamedPart;

use function GuzzleHttp\Psr7\parse_request;

class LoggedRequest implements \JsonSerializable
{
    use PluginAware;

    /** @var string */
    protected $rawRequest;

    /** @var Request */
    protected $parsedRequest;

    /** @var LoggedResponse */
    protected $response;

    /** @var string */
    protected $id;

    /** @var Carbon */
    protected $startTime;

    /** @var Carbon */
    protected $stopTime;

    /** @var string */
    protected $subdomain;

    protected ?PluginData $pluginData = null;

    public function __construct(string $rawRequest, Request $parsedRequest)
    {
        $this->startTime = now();
        $this->rawRequest = $rawRequest;
        $this->parsedRequest = $parsedRequest;
        $this->id = $this->getRequestId();

        $this->pluginData = $this->loadPluginData();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $data = [
            'id' => $this->id,
            'performed_at' => $this->startTime->toDateTimeString(),
            'duration' => $this->getDuration(),
            'subdomain' => $this->detectSubdomain(),
            'request' => [
                'raw' => $this->isBinary($this->rawRequest) ? 'BINARY' : $this->rawRequest,
                'method' => $this->parsedRequest->getMethod(),
                'uri' => $this->parsedRequest->getUriString(),
                'headers' => $this->parsedRequest->getHeaders()->toArray(),
                'body' => $this->isBinary($this->rawRequest) ? 'BINARY' : $this->parsedRequest->getContent(),
                'query' => $this->parsedRequest->getQuery()->toArray(),
                'post' => $this->getPostData(),
                'curl' => $this->getRequestAsCurl(),
                'plugin' => $this->pluginData ? $this->pluginData->toArray() : null
            ],
        ];

        if ($this->response) {
            $data['response'] = $this->response->toArray();
        }

        return $data;
    }

    protected function isBinary(string $string): bool
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $string) > 0;
    }

    public function getRequest()
    {
        return $this->parsedRequest;
    }

    public function setResponse(string $rawResponse, Response $response)
    {
        $this->response = new LoggedResponse($rawResponse, $response, $this->getRequest());

        if (is_null($this->stopTime)) {
            $this->stopTime = now();
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function getRequestData(): ?string
    {
        return $this->rawRequest;
    }

    public function getResponse(): ?LoggedResponse
    {
        return $this->response;
    }

    public function getPostData()
    {
        $postData = [];

        $contentType = Arr::get($this->parsedRequest->getHeaders()->toArray(), 'Content-Type');
        if ($contentType && Str::contains($contentType, ";")) {
            $contentType = explode(';', $contentType, 2);
            if (is_array($contentType) && count($contentType) > 1) {
                $contentType = $contentType[0];
            }
        }

        switch ($contentType) {
            case 'application/x-www-form-urlencoded':
                parse_str($this->parsedRequest->getContent(), $postData);
                $postData = collect($postData)->map(function ($value, $key) {
                    return [
                        'name' => $key,
                        'value' => $value,
                    ];
                })->toArray();
                break;
            case 'application/json':
                $postData = collect(json_decode($this->parsedRequest->getContent(), true))->map(function ($value, $key) {
                    return [
                        'name' => $key,
                        'value' => $value,
                    ];
                })->values()->toArray();

                break;
            default:
                $stream = fopen('php://temp', 'rw');
                fwrite($stream, $this->rawRequest);
                rewind($stream);

                try {
                    $document = new StreamedPart($stream);
                    if ($document->isMultiPart()) {
                        $postData = collect($document->getParts())->map(function (StreamedPart $part) {
                            return [
                                'name' => $part->getName(),
                                'value' => $part->isFile() ? null : $part->getBody(),
                                'is_file' => $part->isFile(),
                                'filename' => $part->isFile() ? $part->getFileName() : null,
                                'mime_type' => $part->isFile() ? $part->getMimeType() : null,
                            ];
                        })->toArray();
                    }
                } catch (\Exception $e) {
                    //
                }
                break;
        }

        return $postData;
    }

    public function detectSubdomain()
    {
        return collect($this->parsedRequest->getHeaders()->toArray())
            ->mapWithKeys(function ($value, $key) {
                return [strtolower($key) => $value];
            })->get('x-original-host');
    }

    protected function getRequestId()
    {
        return collect($this->parsedRequest->getHeaders()->toArray())
            ->mapWithKeys(function ($value, $key) {
                return [strtolower($key) => $value];
            })->get('x-expose-request-id', (string) Str::uuid());
    }

    public function getStartTime()
    {
        return $this->startTime;
    }

    public function getDuration()
    {
        return $this->startTime->diffInMilliseconds($this->stopTime, false);
    }

    protected function getRequestAsCurl(): string
    {
        $maxRequestLength = 256000;

        if (strlen($this->rawRequest) > $maxRequestLength) {
            return '';
        }

        try {
            return (new CurlFormatter())->format(parse_request($this->rawRequest));
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function getUrl()
    {
        $request = Message::parseRequest($this->rawRequest);
        dd($request->getUri()->withFragment(''));
    }

    public function refreshId()
    {
        $requestId = (string) Str::uuid();

        $this->getRequest()->getHeaders()->removeHeader(
            $this->getRequest()->getHeader('x-expose-request-id')
        );

        $this->getRequest()->getHeaders()->addHeader(new GenericHeader('x-expose-request-id', $requestId));

        $this->id = $requestId;
    }

    public function getCliLabel(): string {
        return $this->pluginData ? $this->pluginData->getCliLabel() : '';
    }
}
