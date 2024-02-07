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
    private string $method;
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

            // Must match at least one included service, if included services configured
            // Must not match excluded services
            $this->serviceAllowed = $this->serviceMatcher->matchIncludedServices($request)
                && !$this->serviceMatcher->matchExcludedServices($request);

            if (!$this->serviceAllowed) {
                return [$request];
            }


            $this->isAuthRequest = $this->isAuthorizationRequest($request->getPathInfo());

            $this->method = strtoupper($request->getMethod());

            if (!in_array($this->method, $this->config->logRequestMethods())) {
                return [$request];
            }

            $userAgent = $request->getHeader('User-Agent') ?? '';
            $ipAddress = $request->getClientIp();
            $route = $request->getRequestUri();

            $this->title = implode(' ', [$ipAddress, $this->method, $route, $userAgent]);

            $requestBody = (string)$request->getContent();

            [$shouldLogRequest, $shouldCensorRequestBody] = $this->filterProcessor->processRequest($request);

            if (!$shouldLogRequest) {
                return [$request];
            }

            if (!in_array($this->method, $this->config->logResponseMethodBody())) {
                $this->logger->debug('Request: ' . $this->title);
                return [$request];
            }

            $content = $this->bodyFormatter->format($requestBody);
            if ($this->isAuthRequest) {
                $content = "Request body is not logged for authorization requests.";
            }

            if ($shouldCensorRequestBody) {
                $content = "(redacted by filter)";
            }

            $payload = ['BODY' => $content];

            // Prepare header logs, if needed
            if ($this->config->includeHeaders()) {
                $payload['HEADERS'] = $this->headersFormatter->format($request->getHeaders());
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

            if (!in_array($this->method, $this->config->logResponseMethods())) {
                return;
            }

            $statusCode = (string)$response->getStatusCode();
            $responseBody = (string)$response->getBody();
            [$shouldLogRequest, $shouldCensorResponseBody] = $this->filterProcessor->processResponse($response);

            if (!$shouldLogRequest) {
                return;
            }

            $payload = [
                'STATUS' => $response->getReasonPhrase(),
                'CODE' => $statusCode,
            ];

            if (!in_array($this->method, $this->config->logResponseMethodBody())) {
                $this->logger->debug('Response: ' . $this->title, $payload);
                return;
            }

            $content = $this->bodyFormatter->format($responseBody);

            if ($this->isAuthRequest) {
                $content = "Response body is not logged for authorization requests.";
            }

            if ($shouldCensorResponseBody) {
                $content = '(redacted by filter)';
            }

            $payload['BODY'] = $content;

            // Prepare header logs
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
