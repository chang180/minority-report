<?php

namespace App\Grounding\DTO;

readonly class GroundingSource
{
    public function __construct(
        public string $title,
        public string $url,
        public string $snippet,
    ) {}

    /**
     * @return array{title: string, url: string, snippet: string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'snippet' => $this->snippet,
        ];
    }
}
