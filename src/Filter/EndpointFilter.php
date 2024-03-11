<?php

namespace Corrivate\RestApiLogger\Filter;

use Corrivate\RestApiLogger\Model\Config\Filter;
use Psr\Log\LoggerInterface;

class EndpointFilter
{
    private LoggerInterface $logger;

    private string $observedMethod = '';

    /** @var string[] */
    private array $observedServiceParts = [];

    private string $matchedPath = '';

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    public function matchPathToService(string $path, Filter $filter): bool
    {
        if ($this->matchedPath && $filter->value != $this->matchedPath) {
            return false; // We already matched the path to a different endpoint
        }

        // Is this the first time unpacking the observed service?
        if (!$this->observedMethod || !$this->observedServiceParts) {
            $this->observe($path); // Will fill those properties after running once.
        }

        [$configuredMethod, $configuredServiceParts] = $this->unpackConfig($filter->value);
        $countConfiguredServiceParts = count($configuredServiceParts);

        if (strtolower($this->observedMethod) != strtolower($configuredMethod)) {
            return false; // It's not the same endpoint if the methods are different
        }

        if (count($this->observedServiceParts) != $countConfiguredServiceParts) {
            return false; // It can't be the same endpoint if they don't have the same amount of parts
        }

        // Compare the parts to see if they're either the same,
        // or if the observed part is a value for a variable from the config
        $match = true;
        for ($i = 0; $i < $countConfiguredServiceParts; $i++) {
            if (!$this->compareParts($configuredServiceParts[$i], $this->observedServiceParts[$i])) {
                $match = false;
                break;
            }
        }

        if ($match) {
            $this->matchedPath = $filter->value;
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

    private function observe(string $path): void
    {
        $observed = explode(' ', $path);
        $this->observedMethod = $observed[0];
        $observedService = explode('/V1/', $observed[1])[1];
        if (strpos($observedService, '?') !== false) {
            $observedService = explode('?', $observedService)[0];
        }
        $this->observedServiceParts = explode('/', $observedService);
    }

    private function compareParts(string $configPart, string $observedPart): bool
    {
        if (strpos($configPart, ':') !== 0) { // Configured part is not a variable
            return $configPart == $observedPart;
        }

        // IDs need to be numeric
        // This check is necessary because there are some URLs that could be mapped
        // either to an endpoint with an ID variable, or to a fixed part. For example:
        // - /cmsPage/search?searchCriteria=
        // - /cmsPage/:pageId
        if (substr(strtolower($configPart), -2) ===  'id') {
            return is_numeric($observedPart);
        }

        // Not an ID, so any string ought to match
        return true;
    }


    /** @return array{string, string[]} */
    private function unpackConfig(string $value): array
    {
        $configured = explode(' ', $value);
        $configuredMethod = $configured[0];
        $configuredServiceParts = explode('/', str_replace('V1/', '', $configured[1]));
        return [$configuredMethod, $configuredServiceParts];
    }
}
