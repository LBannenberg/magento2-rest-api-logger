<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Filter;

use Corrivate\RestApiLogger\Model\Config;
use Magento\Framework\App\RequestInterface;

class ServiceFilter
{
    private Config $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }


    public function matchIncludedServices(RequestInterface $request): bool
    {
        $services = $this->config->getIncludedServices();
        if (empty($services)) {
            return true; // if no services are configured, we always accept
        }
        return $this->matchServicesToRequest($request, $this->config->getIncludedServices());
    }


    public function matchExcludedServices(RequestInterface $request): bool
    {
        return $this->matchServicesToRequest($request, $this->config->getExcludedServices());
    }


    /**
     * @param string[] $services
     */
    private function matchServicesToRequest(RequestInterface $request, array $services): bool
    {
        $requestMethod = $request->getMethod();
        $requestParts = $this->extractRequestParts($request);
        $pathLength = count($requestParts);

        foreach ($services as $service) {
            if (strpos(strtolower($service), strtolower($requestMethod)) === false) {
                continue; // Service doesn't match request method
            }

            $serviceParts = $this->extractServiceParts($service);
            if (count($serviceParts) != $pathLength) {
                continue;
            }
            if ($this->partsMatch($requestParts, $serviceParts, $pathLength)) {
                return true;
            }
        }
        return false;
    }


    /**
     * @return string[]
     */
    private function extractRequestParts(RequestInterface $request): array
    {
        $path = $request->getPathInfo();
        $path = (strpos($path, 'rest') === 0)
            ? substr_replace($path, '', 0, 4)
            : $path;
        $path = trim($path, '/');
        [$store, $target] = explode('/V1/', $path);
        return explode('/', $target);
    }


    /**
     * @return array<string|null>
     */
    private function extractServiceParts(string $service): array
    {
        $service = explode(' ', $service);
        $method = $service[0];
        $path = $service[1];

        $parts = explode('/', str_replace('V1/', '', $path));
        $structure = [];
        foreach ($parts as $part) {
            $structure[] = strpos($part, ':') === 0 // variables start with ":"
                ? null // variable part
                : $part; // static part
        }
        return $structure;
    }


    /**
     * @param string[] $pathParts
     * @param array<string|null> $serviceParts
     */
    private function partsMatch(array $pathParts, array $serviceParts, int $pathLength): bool
    {
        for ($i = 0; $i < $pathLength; $i++) {
            if ($serviceParts[$i] === null) {
                continue; // this part is a variable
            }

            if ($pathParts[$i] != $serviceParts[$i]) {
                return false; // mismatch in this part
            }
        }
        return true; // all parts match
    }
}
