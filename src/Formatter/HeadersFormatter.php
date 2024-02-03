<?php

namespace Corrivate\RestApiLogger\Helpers;

class HeadersFormatter
{
    /**
     * @param mixed $headers
     * @return string[]
     */
    public function format($headers): array
    {
        $headerLogData = [];
        foreach ($headers->toArray() as $key => $value) {
            $headerLogData[$key] = $key == 'Authorization'
                ? $this->hashAuthorizationHeader((string)$value)
                : (string)$value;
        }
        return $headerLogData;
    }


    private function hashAuthorizationHeader(string $value): string
    {
        preg_match('/^(?<type>\S+)\s(?<data>\S+)/', $value, $matches);
        if (count($matches) !== 5) {
            return 'SHA256:' . hash('sha256', $value);
        }
        return $matches['type'] . ' SHA256:' . hash('sha256', $matches['data']);
    }
}
