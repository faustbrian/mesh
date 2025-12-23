<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Discovery;

use Spatie\LaravelData\Data;

/**
 * Per-function extension support configuration.
 *
 * Allows individual functions to declare which protocol extensions they accept,
 * overriding server-wide extension defaults. Use either supported (allowlist)
 * or excluded (blocklist), but not both. Enables fine-grained control over
 * extension availability when different functions have varying capabilities.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/system-functions
 * @see https://docs.cline.sh/specs/forrst/system-functions#extension-support
 */
final class FunctionExtensionsData extends Data
{
    /**
     * Create a new function extension configuration.
     *
     * @param null|array<int, string> $supported Allowlist of extension names this function explicitly
     *                                           supports (e.g., ["query", "batch"]). When specified,
     *                                           only these extensions are available for this function
     *                                           regardless of server-wide settings. Use when a function
     *                                           has specific extension requirements or capabilities.
     *                                           Mutually exclusive with excluded field.
     * @param null|array<int, string> $excluded  Blocklist of extension names this function explicitly
     *                                           rejects (e.g., ["query", "cache"]). When specified,
     *                                           these extensions are unavailable for this function even
     *                                           if enabled server-wide. Use when a function cannot support
     *                                           certain extensions due to implementation constraints.
     *                                           Mutually exclusive with supported field.
     */
    public function __construct(
        public readonly ?array $supported = null,
        public readonly ?array $excluded = null,
    ) {}
}
