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
 * Request-response pair demonstrating a complete function invocation.
 *
 * Links argument examples to expected results, providing end-to-end
 * documentation of how a function behaves. Used in discovery documents
 * to show realistic usage scenarios with complete input/output pairs.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/discovery#componentsexamplepairings
 */
final class ExamplePairingData extends Data
{
    /**
     * Create a new example pairing.
     *
     * @param string                           $name        Unique identifier for this pairing within the examples collection
     *                                                      (e.g., "GetSingleEvent", "ListPublishedEvents"). Used for referencing
     *                                                      the pairing from function definitions using $ref notation.
     * @param array<int, array<string, mixed>> $params      Array of parameter examples demonstrating the function call.
     *                                                      Each element contains 'name' (parameter name) and 'value'
     *                                                      (example value). Should cover all required parameters and
     *                                                      relevant optional parameters for this scenario.
     * @param null|string                      $summary     Brief one-line description of what this example demonstrates
     *                                                      (e.g., "Retrieve all published events"). Displayed as the
     *                                                      example title in documentation and selection lists.
     * @param null|string                      $description Detailed explanation of the example scenario, including any
     *                                                      preconditions, important notes about the input values, and
     *                                                      context about the expected behavior. Supports Markdown.
     * @param null|array<string, mixed>        $result      Expected response for this example invocation. Contains 'name'
     *                                                      (result identifier) and 'value' (example response data). May be
     *                                                      omitted for notification-style functions that don't return data.
     */
    public function __construct(
        public readonly string $name,
        public readonly array $params,
        public readonly ?string $summary = null,
        public readonly ?string $description = null,
        public readonly ?array $result = null,
    ) {}
}
