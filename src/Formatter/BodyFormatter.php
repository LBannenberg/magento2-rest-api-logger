<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Formatter;

class BodyFormatter
{
    /**
     * @param string|null $content
     * @param string $contentType
     * @return array<mixed>|string
     */
    public function format(?string $content, string $contentType)
    {
        if (!is_string($content) || !$content) {
            return '';
        }

        if (strpos($contentType, 'xml') !== false) {
            $content = $this->formatXml($content);
        }

        if (strpos($contentType, 'json') !== false) {
            $content = $this->formatJson($content);
        }

        return $content ?: '';
    }


    /**
     * @param array<mixed> $array
     * @return int
     */
    private function getArrayDepth(array $array): int
    {
        $maxDepth = 0;
        foreach ($array as $value) {
            $depth = is_array($value)
                ? 1 + $this->getArrayDepth($value)
                : 0;
            $maxDepth = max($maxDepth, $depth);
        }
        return $maxDepth;
    }

    /**
     * @param string $content
     * @return false|mixed|string
     */
    private function formatJson(string $content)
    {
        if (!$content = json_decode($content, true)) {
            return '';
        }

        if (!is_array($content)) {
            return '';
        }

        if ($this->getArrayDepth($content) > 6) {
            $content = json_encode($content); // Monolog will not print overly deep arrays
        }

        return $content;
    }

    private function formatXml(string $content): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = explode("\n", $content);
        $content = array_map(fn($line) => trim($line), $content);
        return implode('', $content);
    }
}
