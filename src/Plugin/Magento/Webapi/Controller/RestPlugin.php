<?php
declare(strict_types=1);

namespace Corrivate\RestApiLogger\Plugin\Magento\Webapi\Controller;

use Corrivate\RestApiLogger\Helpers\BodyFormatter;
use Corrivate\RestApiLogger\Helpers\Config;
use Corrivate\RestApiLogger\Helpers\Aspects;
use Corrivate\RestApiLogger\Helpers\FilterProcessor;
use Corrivate\RestApiLogger\Helpers\HeadersFormatter;
use Corrivate\RestApiLogger\Logger\Logger;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Webapi\Controller\Rest;

class RestPlugin
{
    private bool $isAuthRequest = false;
    private string $title;
    private string $method;
    private Config $config;
    private BodyFormatter $bodyFormatter;
    private HeadersFormatter $headersFormatter;
    private Logger $logger;
    private FilterProcessor $filterProcessor;


    public function __construct(
        Logger           $logger,
        Config           $config,
        BodyFormatter    $bodyFormatter,
        HeadersFormatter $headersFormatter,
        FilterProcessor  $filterProcessor
    )
    {
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

            $this->method = strtoupper($request->getMethod());

            if (!in_array($this->method, $this->config->logRequestMethods())) {
                return [$request];
            }

            $userAgent = $request->getHeader('User-Agent') ?? '';
            $ipAddress = $request->getClientIp();
            $route = $request->getRequestUri();

            $this->title = implode(' ', [$ipAddress, $userAgent, $this->method, $route]);

            $requestBody = (string)$request->getContent();

            $policy = $this->filterProcessor->processRequest($request);
            $this->logger->info('request policy', [$policy]);

            if ($policy->preventLogRequest()) {
                return [$request]; // blocked by policy
            }

            if (!in_array($this->method, $this->config->logResponseMethodBody())) {
                $this->logger->debug('Request: ' . $this->title);
                return [$request];
            }

            $content = $this->bodyFormatter->format($requestBody);
            if ($this->isAuthRequest) {
                $content = "Request body is not logged for authorization requests.";
            }

            if($policy->censorRequest) {
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

            if (!in_array($this->method, $this->config->logResponseMethods())) {
                return;
            }

            $statusCode = (string)$response->getStatusCode();
            $responseBody = (string)$response->getBody();
            $policy = $this->filterProcessor->processResponse($response);
            $this->logger->info('response policy', [$policy]);

            $payload = [
                'STATUS' => $response->getReasonPhrase(),
                'CODE' => $statusCode,
            ];

            if ($policy->preventLogResponse()) {
                return;
            }

            if (!in_array($this->method, $this->config->logResponseMethodBody())) {
                $this->logger->debug('Response: ' . $this->title, $payload);
                return;
            }

            $content = $this->bodyFormatter->format($responseBody);

            if ($this->isAuthRequest) {
                $content = "Response body is not logged for authorization requests.";
            }

            if($policy->censorResponse) {
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
