<?php

namespace App\Services\Notion;

readonly class NotionSyncResult
{
    /**
     * @param  array<int, string>  $skippedBlockTypes
     */
    public function __construct(
        public string $markdown,
        public array $skippedBlockTypes,
    ) {}
}
