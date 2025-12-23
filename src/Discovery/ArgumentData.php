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
 * Function argument definition for API discovery documentation.
 *
 * Describes a single function parameter including its JSON Schema, validation rules,
 * default values, and documentation. Used in discovery documents to communicate
 * function signatures and parameter requirements to API consumers.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://docs.cline.sh/forrst/
 */
final class ArgumentData extends Data
{
    /**
     * Create a new argument definition instance.
     *
     * @param string                 $name        The parameter name as it appears in function calls, typically
     *                                            in camelCase or snake_case. Used to identify the argument in
     *                                            request payloads and must match exactly.
     * @param array<string, mixed>   $schema      JSON Schema definition describing the parameter's type, format,
     *                                            validation constraints, and structure. Supports all JSON Schema
     *                                            Draft 7 features including type definitions, format specifiers,
     *                                            pattern matching, and nested object schemas.
     * @param bool                   $required    Indicates whether this parameter must be provided in function calls.
     *                                            Required parameters generate validation errors when missing. Defaults
     *                                            to false, making parameters optional unless explicitly marked required.
     * @param null|string            $summary     Brief one-line description of the parameter's purpose. Used for quick
     *                                            reference in API documentation and IDE tooltips. Should be concise
     *                                            (typically under 80 characters).
     * @param null|string            $description Detailed explanation of the parameter including usage guidelines,
     *                                            expected formats, validation rules, and examples. Supports markdown
     *                                            formatting for enhanced documentation presentation.
     * @param mixed                  $default     Default value used when the parameter is omitted from function calls.
     *                                            Must match the type specified in the schema. Only applicable when
     *                                            required is false.
     * @param null|DeprecatedData    $deprecated  Deprecation information if this parameter is being phased out. Contains
     *                                            the reason for deprecation and optional sunset date. Presence indicates
     *                                            the parameter should not be used in new code.
     * @param null|array<int, mixed> $examples    Array of example values demonstrating valid parameter usage. Used in
     *                                            documentation and testing. Should cover common use cases and edge cases.
     */
    public function __construct(
        public readonly string $name,
        public readonly array $schema,
        public readonly bool $required = false,
        public readonly ?string $summary = null,
        public readonly ?string $description = null,
        public readonly mixed $default = null,
        public readonly ?DeprecatedData $deprecated = null,
        public readonly ?array $examples = null,
    ) {}
}
