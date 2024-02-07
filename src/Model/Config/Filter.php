<?php

namespace Corrivate\RestApiLogger\Model\Config;

class Filter
{
    public string $aspect;
    public string $condition;
    public string $value;
    public string $consequence;
    /**
     * @var string[]
     */
    public array $tags;

    /**
     * @param string[] $tags
     */
    public function __construct(
        string $aspect,
        string $condition,
        string $value,
        string $consequence,
        array $tags = []
    ) {

        $this->aspect = $aspect;
        $this->condition = $condition;
        $this->value = $value;
        $this->consequence = $consequence;
        $this->tags = $tags;
    }
}
