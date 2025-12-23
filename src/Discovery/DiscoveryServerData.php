<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Discovery;

use InvalidArgumentException;
use Spatie\LaravelData\Data;

/**
 * Server endpoint information for discovery documents.
 *
 * Defines a single server endpoint where the Forrst service can be accessed.
 * Supports URL templating with variable substitution for dynamic endpoint
 * configuration across multiple environments or regions.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/system-functions
 * @see https://docs.cline.sh/specs/forrst/discovery#server-object
 */
final class DiscoveryServerData extends Data
{
    /**
     * Create a new server endpoint definition.
     *
     * @param string                                          $name        Human-readable identifier for this server endpoint (e.g., "Production",
     *                                                                     "Staging", "US East"). Used in client tooling to display available
     *                                                                     server options and help users select the appropriate endpoint.
     * @param string                                          $url         Server URL supporting RFC 6570 URI template syntax for variable
     *                                                                     substitution (e.g., "https://{environment}.api.example.com/v1").
     *                                                                     Variables enclosed in braces are replaced with values from the
     *                                                                     variables array, enabling dynamic endpoint construction.
     * @param null|string                                     $summary     Brief one-line description of this server's purpose. Displayed
     *                                                                     in compact views and navigation lists where a full description
     *                                                                     would be too verbose.
     * @param null|string                                     $description Detailed human-readable explanation of this server's purpose,
     *                                                                     characteristics, or usage constraints. Supports Markdown. Provides
     *                                                                     context about when to use this endpoint versus alternatives.
     * @param null|array<string, ServerVariableData>          $variables   URL template variable definitions keyed
     *                                                                     by variable name. Each variable defines
     *                                                                     allowed values, default value, and
     *                                                                     description for template substitution
     *                                                                     in the URL field.
     * @param null|array<int, ServerExtensionDeclarationData> $extensions  Extensions supported by this server endpoint.
     *                                                                     Each declaration specifies the extension URN
     *                                                                     and version. Allows clients to discover which
     *                                                                     optional protocol features are available.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $url,
        public readonly ?string $summary = null,
        public readonly ?string $description = null,
        public readonly ?array $variables = null,
        public readonly ?array $extensions = null,
    ) {
        $this->validateUrlTemplate($url);
    }

    /**
     * Validate RFC 6570 URI template syntax.
     *
     * @throws InvalidArgumentException
     */
    private function validateUrlTemplate(string $url): void
    {
        // Check for basic template syntax errors
        if (substr_count($url, '{') !== substr_count($url, '}')) {
            throw new InvalidArgumentException(
                "Invalid URI template: Mismatched braces in URL '{$url}'"
            );
        }

        // Extract and validate variable names
        preg_match_all('/\{([^}]+)\}/', $url, $matches);
        foreach ($matches[1] as $varName) {
            // RFC 6570: variable names must be [A-Za-z0-9_]+ (no hyphens, dots, etc.)
            if (!preg_match('/^[A-Za-z0-9_]+$/', $varName)) {
                throw new InvalidArgumentException(
                    "Invalid variable name '{$varName}' in URI template. "
                    .'Variable names must contain only letters, numbers, and underscores.'
                );
            }
        }

        // Validate URL structure (basic sanity check)
        $testUrl = preg_replace('/\{[^}]+\}/', 'test', $url);
        if (!filter_var($testUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(
                "Invalid URL structure: '{$url}' is not a valid URL template"
            );
        }
    }
}
