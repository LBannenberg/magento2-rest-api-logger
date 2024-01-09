<?php
declare(strict_types=1);

namespace Corrivate\RestApiLogger\Plugin\Magento\Webapi\Controller;

use Corrivate\RestApiLogger\Helpers\BodyFormatter;
use Corrivate\RestApiLogger\Helpers\Config;
use Corrivate\RestApiLogger\Helpers\Aspects;
use Corrivate\RestApiLogger\Helpers\FilterProcessor;
use Corrivate\RestApiLogger\Helpers\HeadersFormatter;
use Corrivate\RestApiLogger\Helpers\Policy;
use Corrivate\RestApiLogger\Logger\Logger;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Webapi\Controller\Rest;

class RestPlugin
{
    private bool $isAuthRequest = false;
    private Policy $policy;
    private Config $config;
    private BodyFormatter $bodyFormatter;
    private HeadersFormatter $headersFormatter;
    private Logger $logger;
    private FilterProcessor $filterProcessor;
    private Aspects $aspects;


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
        $this->policy = new Policy();
        $this->aspects = new Aspects();
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

            $this->aspects->method = strtoupper($request->getMethod());

            if (!in_array($this->aspects->method, $this->config->logRequestMethods())) {
                return [$request];
            }

            $this->aspects->user_agent = $request->getHeader('User-Agent') ?? '';
            $this->aspects->ip = $request->getClientIp();
            $this->aspects->route = $request->getRequestUri();
            $this->aspects->request_body = (string)$request->getContent();

            $this->policy = $this->filterProcessor->process($this->aspects, $this->policy, false);
            $this->logger->info('request policy', [$this->policy]);

            if ($this->policy->preventLogRequest()) {
                return [$request]; // blocked by policy
            }

            if (!in_array($this->aspects->method, $this->config->logResponseMethodBody())) {
                $this->logger->debug('Request: ' . $this->aspects->title());
                return [$request];
            }

            $content = $this->bodyFormatter->format($this->aspects->request_body);
            if ($this->isAuthRequest) {
                $content = "Request body is not logged for authorization requests.";
            }

            if($this->policy->censorRequest) {
                $content = "(redacted by filter)";
            }

            $payload = ['BODY' => $content];

            // Prepare header logs, if needed
            if ($this->config->includeHeaders()) {
                $payload['HEADERS'] = $this->headersFormatter->format($request->getHeaders());
            }

            $this->logger->debug('Request: ' . $this->aspects->title(), $payload);

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

            if (!in_array($this->aspects->method, $this->config->logResponseMethods())) {
                return;
            }

            $this->aspects->status_code = (string)$response->getStatusCode();
            $this->aspects->response_body = (string)$response->getBody();
            $this->policy = $this->filterProcessor->process($this->aspects, $this->policy, true);
            $this->logger->info('response policy', [$this->policy]);

            $payload = [
                'STATUS' => $response->getReasonPhrase(),
                'CODE' => $this->aspects->status_code,
            ];

            if ($this->policy->preventLogResponse()) {
                return;
            }

            if (!in_array($this->aspects->method, $this->config->logResponseMethodBody())) {
                $this->logger->debug('Response: ' . $this->aspects->title(), $payload);
                return;
            }

            $content = $this->bodyFormatter->format($this->aspects->response_body);

            if ($this->isAuthRequest) {
                $content = "Response body is not logged for authorization requests.";
            }

            if($this->policy->censorResponse) {
                $content = '(redacted by filter)';
            }

            $payload['BODY'] = $content;

            // Prepare header logs
            if ($this->config->includeHeaders()) {
                $payload['HEADERS'] = $this->headersFormatter->format($response->getHeaders());
            }

            $this->logger->debug('Response: ' . $this->aspects->title(), $payload);

        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
        }
    }


    private function isAuthorizationRequest(string $path): bool
    {
        return preg_match('/integration\/(admin|customer)\/token/', $path) !== 0;
    }


}
