<?php

declare(strict_types=1);

class TemplateInheritance
{
    private const string WARNING_STARTBLOCK_ENDBLOCK_MISMATCH = "startblock('%s') does not match endblock('%s')";
    private const string WARNING_ORPHAN_ENDBLOCK = "orphan endblock('%s')";
    private const string WARNING_MISSING_ENDBLOCK = "missing endblock() for startblock ('%s')";

    private ?Block $base = null;
    private array $stack;

    private int $level;
    private ?array $hash = null;
    private ?int $end = null;
    private ?string $after = null;

    public function startblock(string $name): void
    {
        $trace = $this->callingTrace();
        $this->init($trace);
        $this->stack[] = $this->newBlock($name, $trace);
    }

    public function endblock(?string $name = null): void
    {
        $trace = $this->callingTrace();
        $this->init($trace);
        if ($this->stack) {
            $block = array_pop($this->stack);
            if ($name && $name != $block->name) {
                $this->warning(sprintf(self::WARNING_STARTBLOCK_ENDBLOCK_MISMATCH, $block->name, $name), $trace);
            }
            $this->insertBlock($block);

            return;
        }
        $this->warning(
            $name ? sprintf(self::WARNING_ORPHAN_ENDBLOCK, $name) : sprintf(self::WARNING_ORPHAN_ENDBLOCK, ''),
            $trace
        );
    }

    public function flushblocks(): void
    {
        if ($this->base !== null) {
            while ($block = array_pop($this->stack)) {
                $this->warning(
                    sprintf(self::WARNING_MISSING_ENDBLOCK, $block->name),
                    $this->callingTrace(),
                    $block->trace
                );
            }
            while (ob_get_level() > $this->level) {
                ob_end_flush(); // will eventually trigger bufferCallback
            }
            $this->base = null;
            $this->stack = [];
        }
    }

    public function blockbase(): void
    {
        $this->init($this->callingTrace());
    }

    private function init(array $trace): void
    {
        if ($this->base && !$this->inBaseOrChild($trace)) {
            $this->flushblocks(); // will set $this->base to null
        }
        if ($this->base === null) {
            $this->base = new Block('base', $trace, 0, null, []); // base block (top-level template
            $this->level = ob_get_level();
            $this->stack = array();
            $this->hash = array();
            $this->end = null;
            $this->after = '';
            ob_start(fn(string $buffer) => $this->bufferCallback($buffer));
        }
    }

    private function newBlock(string $name, array $trace): Block
    {
        while ($block = end($this->stack)) {
            if ($this->isSameFile($block->trace, $trace)) {
                break;
            } else {
                array_pop($this->stack);
                $this->insertBlock($block);
                $this->warning(
                    sprintf(self::WARNING_MISSING_ENDBLOCK, $block->name),
                    $this->callingTrace(),
                    $block->trace
                );
            }
        }
        if ($this->base?->end === null && !$this->inBase($trace)) {
            $this->base->end = ob_get_length();
        }

        return new Block($name, $trace, ob_get_length(), null, []);
    }

    private function insertBlock(Block $block): void
    {
        $block->end = $this->end = ob_get_length();
        $name = $block->name;
        if ($this->stack || $this->inBase($block->trace)) {
            $block_anchor = array(
                'start' => $block->start,
                'end' => $this->end,
                'block' => $block
            );
            if ($this->stack) {
                // nested block
                $this->stack[count($this->stack) - 1]->children[] = &$block_anchor;
            } else {
                // top-level block in base
                $this->base->children[] = &$block_anchor;
            }
            $this->hash[$name] = &$block_anchor; // same reference as children array
        } elseif (isset($this->hash[$name])) {
            if ($this->isSameFile($this->hash[$name]['block']->trace, $block->trace)) {
                $this->warning(
                    "cannot define another block called '$name'",
                    $this->callingTrace(),
                    $block->trace
                );
            } else {
                // top-level block in a child template; override the base's block
                $this->hash[$name]['block'] = $block;
            }
        }
    }

    private function bufferCallback(string $buffer): string
    {
        if ($this->base) {
            while ($block = array_pop($this->stack)) {
                $this->insertBlock($block);
                $this->warning(
                    sprintf(self::WARNING_MISSING_ENDBLOCK, $block->name),
                    $this->callingTrace(),
                    $block->trace
                );
            }
            if ($this->base?->end === null) {
                $this->base->end = strlen($buffer);
                $this->end = null;
                // means there were no blocks other than the base's
            }
            $parts = $this->compile($this->base, $buffer);
            // remove trailing whitespace from end
            $i = count($parts) - 1;
            $parts[$i] = rtrim((string) $parts[$i]);
            // if there are child template blocks, preserve output after last one
            if ($this->end !== null) {
                $parts[] = substr($buffer, $this->end);
            }
            // for error messages
            $parts[] = $this->after;
            return implode('', $parts);
        } else {
            return '';
        }
    }

    private function compile(Block $block, string $buffer): array
    {
        $parts = array();
        $previous = $block->start;
        foreach ($block->children as $child_anchor) {
            $parts[] = substr($buffer, $previous, $child_anchor['start'] - $previous);
            $parts = array_merge(
                $parts,
                $this->compile($child_anchor['block'], $buffer)
            );
            $previous = $child_anchor['end'];
        }
        if ($previous !== $block->end) {
            // could be a big buffer, so only do substr if necessary
            $parts[] = substr($buffer, $previous, $block->end - $previous);
        }
        return $parts;
    }

    private function warning(string $message, array $trace, ?array $warning_trace = null): void
    {
        if (error_reporting() & E_USER_WARNING) {
            if (defined('STDIN')) {
                // from command line
                $format = "\nWarning: %s in %s on line %d\n";
            } else {
                // from browser
                $format = "<br />\n<b>Warning</b>:  %s in <b>%s</b> on line <b>%d</b><br />\n";
            }
            if (!$warning_trace) {
                $warning_trace = $trace;
            }
            $s = sprintf($format, $message, $warning_trace[0]['file'], $warning_trace[0]['line']);
            if (!$this->base || $this->inBase($trace)) {
                echo $s;
            } else {
                $this->after .= $s;
            }
        }
    }

    private function callingTrace(): array
    {
        $trace = debug_backtrace();
        foreach ($trace as $i => $location) {
            if ($location['file'] !== __FILE__) {
                return array_slice($trace, $i);
            }
        }

        return [];
    }

    private function inBase(array $trace): bool
    {
        return $this->isSameFile($trace, $this->base->trace);
    }

    private function inBaseOrChild(array $trace): bool
    {
        $base_trace = $this->base->trace;
        return
            $trace && $base_trace &&
            $this->isSubtrace(array_slice($trace, 1), $base_trace) &&
            $trace[0]['file'] === $base_trace[count($base_trace) - count($trace)]['file'];
    }

    private function isSameFile(array $trace1, array $trace2): bool
    {
        return
            $trace1 && $trace2 &&
            $trace1[0]['file'] === $trace2[0]['file'] &&
            array_slice($trace1, 1) === array_slice($trace2, 1);
    }

    private function isSubtrace(array $trace1, array $trace2): bool
    {
        // is trace1 a sub trace of trace2
        $len1 = count($trace1);
        $len2 = count($trace2);
        if ($len1 > $len2) {
            return false;
        }
        for ($i = 0; $i < $len1; $i++) {
            if ($trace1[$len1 - 1 - $i] !== $trace2[$len2 - 1 - $i]) {
                return false;
            }
        }
        return true;
    }
}
