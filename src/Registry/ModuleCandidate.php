<?php

declare(strict_types=1);

namespace Supe\Core\Registry;

/**
 * Simple value object representing a registered module candidate.
 *
 * @internal
 */
final class ModuleCandidate {

    public function __construct(
        /** @var class-string */
        public readonly string $class,
        public readonly string $id,
        public readonly string $version,
    ) {}
}
