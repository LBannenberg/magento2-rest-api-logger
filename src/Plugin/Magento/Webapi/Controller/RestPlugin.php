<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Plugin\Magento\Webapi\Controller;

use Corrivate\RestApiLogger\Filter\MainFilter;
use Corrivate\RestApiLogger\Formatter\BodyFormatter;
use Corrivate\RestApiLogger\Formatter\HeadersFormatter;
use Corrivate\RestApiLogger\Logger\Logger;
use Corrivate\RestApiLogger\Model\Config;
use Laminas\Http\Header\HeaderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Webapi\Controller\Rest;

class RestPlugin
{
    private bool $isAuthRequest = false;
    private string $title;
    private Config $config;
    private BodyFormatter $bodyFormatter;
    private HeadersFormatter $headersFormatter;
    private Logger $logger;
    private MainFilter $filterProcessor;


    public function __construct(
        Logger           $logger,
        Config           $config,
        BodyFormatter    $bodyFormatter,
        HeadersFormatter $headersFormatter,
        MainFilter       $filterProcessor
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->bodyFormatter = $bodyFormatter;
        $this->headersFormatter = $headersFormatter;
        $this->filterProcessor = $filterProcessor;
    }

    /**
     * @param Rest $subject
     * @param RequestInterface $request
     * @return RequestInterface[]
     */
    public function beforeDispatch(Rest $subject, RequestInterface $request): array
    {
        try {
            if (!$this->config->enabled()) {
                return [$request];
            }

            $this->isAuthRequest = $this->isAuthorizationRequest($request->getPathInfo());

            // We need to capture title before deciding if we want to log the request,
            // in case the filters permit logging a response but not the request.
            $this->title = implode(' ', [
                $request->getClientIp(),
                strtoupper($request->getMethod()),
                $request->getRequestUri(),
                $request->getHeader('User-Agent') ?? ''
            ]);

            [$shouldLogRequest, $shouldCensorRequestBody, $tags] = $this->filterProcessor->processRequest($request);

            if (!$shouldLogRequest) {
                return [$request];
            }



            if ($this->isAuthRequest) {
                $content = "Request body is not logged for authorization requests.";
            } elseif ($shouldCensorRequestBody) {
                $content = "(redacted by filter)";
            } else {
                $accept = $request->getHeader('Accept');
                $accept = strpos($accept, '*/*') !== false
                    ? 'json'
                    : $accept;
                $content = $this->bodyFormatter->format((string)$request->getContent(), $accept);
            }

            $payload = ['BODY' => $content];

            if ($this->config->includeHeaders()) {
                $payload['HEADERS'] = $this->headersFormatter->format($request->getHeaders());
            }

            if ($tags) {
                $payload['TAGS'] = $tags;
            }

            $this->logger->debug('Request: ' . $this->title, $payload);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
        }

        return [$request];
    }


    public function afterSendResponse(Response $response): void
    {
        try {
            if (!$this->config->enabled()) {
                return;
            }


            [$shouldLogRequest, $shouldCensorResponseBody, $tags] = $this->filterProcessor->processResponse($response);
            if (!$shouldLogRequest) {
                return;
            }

            if ($this->isAuthRequest) {
                $content = "Response body is not logged for authorization requests.";
            } elseif ($shouldCensorResponseBody) {
                $content = '(redacted by filter)';
            } else {
                $contentType = $response->getHeader('Content-Type');
                $contentType = $contentType instanceof HeaderInterface ? $contentType->toString() : 'other';
                $content = $this->bodyFormatter->format((string)$response->getBody(), $contentType);
            }

            $payload = [
                'STATUS' => $response->getReasonPhrase(),
                'CODE' => (string)$response->getStatusCode(),
                'BODY' => $content
            ];

            if ($tags) {
                $payload['TAGS'] = $tags;
            }

            if ($this->config->includeHeaders()) {
                $payload['HEADERS'] = $this->headersFormatter->format($response->getHeaders());
            }

            $this->logger->debug('Response: ' . $this->title, $payload);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
        }
    }


    private function isAuthorizationRequest(string $path): bool
    {
        return preg_match('/integration\/(admin|customer)\/token/', $path) !== 0;
    }
}
