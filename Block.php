<?php

declare(strict_types=1);

class Block {
    public function __construct(
        public string $name,
        public array $trace,
        public int $start,
        public ?int $end = null,
        public array $children = [],
    ) {
    }
}
