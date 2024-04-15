<?php

namespace Corrivate\RestApiLogger\Filter;

use Corrivate\RestApiLogger\Model\Config\Filter;
use Psr\Log\LoggerInterface;

class EndpointFilter
{
    private LoggerInterface $logger;
    private string $requestMethod = '';

    /** @var string[] */
    private array $requestPathParts = [];
    private string $matchedEndpoint = '';


    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    public function matchRequestToFilter(string $requestedMethodAndPath, Filter $filter): bool
    {
        if ($this->matchedEndpoint && $filter->value != $this->matchedEndpoint) {
            return false; // We already matched the request to a different endpoint
        }

        // Is this the first time unpacking the requested endpoint?
        if (!$this->requestMethod || !$this->requestPathParts) {
            $this->unpackRequestedEndpoint($requestedMethodAndPath); // Will fill those properties after running once.
        }

        [$configuredMethod, $configuredPathParts] = $this->unpackConfiguredEndpoint($filter->value);
        $countConfiguredPathParts = count($configuredPathParts);

        if (strtolower($this->requestMethod) != strtolower($configuredMethod)) {
            return false; // It's not the same endpoint if the methods are different
        }

        if (count($this->requestPathParts) != $countConfiguredPathParts) {
            return false; // It can't be the same endpoint if they don't have the same amount of parts
        }

        // Compare the path parts to see if each of them matches,
        // or if the requested part is a value for a variable from the config
        $match = true;
        for ($i = 0; $i < $countConfiguredPathParts; $i++) {
            if (!$this->compareParts($configuredPathParts[$i], $this->requestPathParts[$i])) {
                $match = false;
                break;
            }
        }

        if ($match) {
            $this->matchedEndpoint = $filter->value;
        }

        if ($filter->condition == '=') {
            return $match;
        }
        if ($filter->condition == '!=') {
            return !$match;
        }

        $this->logger->warning("Unsupported REST API logger filter configuration", ['filter' => $filter]);
        return false;
    }


    private function unpackRequestedEndpoint(string $methodAndEndpoint): void
    {
        $requestElements = explode(' ', $methodAndEndpoint);
        $this->requestMethod = $requestElements[0];
        $requestedPath = explode('/V1/', $requestElements[1])[1];
        if (strpos($requestedPath, '?') !== false) {
            $requestedPath = explode('?', $requestedPath)[0];
        }
        $this->requestPathParts = explode('/', $requestedPath);
    }


    private function compareParts(string $configPart, string $requestPart): bool
    {
        if (strpos($configPart, ':') !== 0) { // Configured part is not a variable
            return $configPart == $requestPart;
        }

        // IDs need to be numeric
        // This check is necessary because there are some URLs that could be mapped
        // either to an endpoint with an ID variable, or to a fixed part. For example:
        // - /cmsPage/search?searchCriteria=
        // - /cmsPage/:pageId
        if (substr(strtolower($configPart), -2) ===  'id') {
            return is_numeric($requestPart);
        }

        // Not an ID, so any string ought to match
        return true;
    }


    /** @return array{string, string[]} */
    private function unpackConfiguredEndpoint(string $methodAndPath): array
    {
        $configuredElements = explode(' ', $methodAndPath);
        $configuredMethod = $configuredElements[0];
        $configuredPathParts = explode('/', str_replace('V1/', '', $configuredElements[1]));
        return [$configuredMethod, $configuredPathParts];
    }
}
