<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Data\Configuration;

use Cline\Forrst\Contracts\FunctionInterface;
use Cline\Forrst\Data\AbstractData;

/**
 * Configuration data for a single Forrst server instance.
 *
 * Represents the configuration for one Forrst server endpoint including its
 * routing information, version, middleware stack, and available functions.
 * Multiple server instances can be configured to provide different API
 * versions or isolated function sets.
 *
 * Each server acts as an independent RPC endpoint with its own route,
 * version, and function registry. This allows applications to run multiple
 * versions side-by-side or separate public and admin APIs.
 *
 * @see https://docs.cline.sh/forrst/
 */
final class ServerData extends AbstractData
{
    /**
     * Create a new server configuration instance.
     *
     * @param string                                           $name       Unique identifier for this server instance, used
     *                                                                     to distinguish between multiple servers in the
     *                                                                     configuration (e.g., 'v1', 'admin', 'public').
     * @param string                                           $path       File system path or namespace where this server's
     *                                                                     function classes are located. Used for automatic
     *                                                                     function discovery during server initialization.
     * @param string                                           $route      HTTP route path where this server accepts requests.
     *                                                                     Should include leading slash and may include route
     *                                                                     parameters (e.g., '/api/v1/forrst', '/forrst/{version}').
     * @param string                                           $version    API version string for this server instance. Used
     *                                                                     for version tracking and documentation generation.
     *                                                                     Should follow semantic versioning (e.g., '1.0.0').
     * @param array<int, string>                               $middleware Ordered array of middleware class names or aliases
     *                                                                     that apply to all requests to this server. Executed
     *                                                                     in array order before function dispatching occurs.
     * @param null|array<int, class-string<FunctionInterface>> $functions  Optional array of function class names to register for
     *                                                                     this server. If null, all functions in the configured
     *                                                                     path will be auto-discovered and registered.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly string $route,
        public readonly string $version,
        public readonly array $middleware,
        public readonly ?array $functions,
    ) {}
}
