<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Plugin\Magento\Webapi\Controller;

use Corrivate\RestApiLogger\Filter\MainFilter;
use Corrivate\RestApiLogger\Filter\ServiceFilter;
use Corrivate\RestApiLogger\Formatter\BodyFormatter;
use Corrivate\RestApiLogger\Formatter\HeadersFormatter;
use Corrivate\RestApiLogger\Logger\Logger;
use Corrivate\RestApiLogger\Model\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Webapi\Controller\Rest;

class RestPlugin
{
    private bool $isAuthRequest = false;
    private bool $serviceAllowed = true;
    private string $title;
    private Config $config;
    private BodyFormatter $bodyFormatter;
    private HeadersFormatter $headersFormatter;
    private Logger $logger;
    private MainFilter $filterProcessor;
    private ServiceFilter $serviceMatcher;


    public function __construct(
        Logger           $logger,
        Config           $config,
        BodyFormatter    $bodyFormatter,
        HeadersFormatter $headersFormatter,
        MainFilter       $filterProcessor,
        ServiceFilter    $serviceMatcher
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->bodyFormatter = $bodyFormatter;
        $this->headersFormatter = $headersFormatter;
        $this->filterProcessor = $filterProcessor;
        $this->serviceMatcher = $serviceMatcher;
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

            // Must match at least one included service, if included services configured
            // Must not match excluded services
            $this->serviceAllowed = $this->serviceMatcher->matchIncludedServices($request)
                && !$this->serviceMatcher->matchExcludedServices($request);

            if (!$this->serviceAllowed) {
                return [$request];
            }

            [$shouldLogRequest, $shouldCensorRequestBody, $tags] = $this->filterProcessor->processRequest($request);

            if (!$shouldLogRequest) {
                return [$request];
            }

            $this->title = implode(' ', [
                $request->getClientIp(),
                strtoupper($request->getMethod()),
                $request->getRequestUri(),
                $request->getHeader('User-Agent') ?? ''
            ]);

            if ($this->isAuthRequest) {
                $content = "Request body is not logged for authorization requests.";
            } elseif ($shouldCensorRequestBody) {
                $content = "(redacted by filter)";
            } else {
                $content = $this->bodyFormatter->format((string)$request->getContent());
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

            if (!$this->serviceAllowed) {
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
                $content = $this->bodyFormatter->format((string)$response->getBody());
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
