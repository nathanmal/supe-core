<?php

declare(strict_types=1);

namespace Supe\Core\Support;

/**
 * SafeHtml
 *
 * Marks a string of markup as already escaped, so AdminUi helpers that
 * escape plain strings (e.g. table cells) output it as-is instead of
 * escaping it a second time. Only AdminUi's own builders (badge(), code())
 * and code that has escaped its own output should ever construct one.
 */
final class SafeHtml {

    public function __construct( public readonly string $html ) {}

    public function __toString(): string {
        return $this->html;
    }
}
