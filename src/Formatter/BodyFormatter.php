<?php

namespace Corrivate\RestApiLogger\Formatter;

class BodyFormatter
{


    /**
     * @param string|null $content
     * @return array<mixed>|string
     */
    public function format(?string $content)
    {
        if(!is_string($content)) {
            return '';
        }

        if(!$content = json_decode($content, true)) {
            return '';
        }

        if(!is_array($content)) {
            return '';
        }

        if ($this->getArrayDepth($content) > 6) {
            $content = json_encode($content); // Monolog will not print overly deep arrays
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
}
