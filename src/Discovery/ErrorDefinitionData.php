<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Discovery;

use BackedEnum;
use Spatie\LaravelData\Data;

/**
 * Error definition for function error documentation.
 *
 * Describes a specific error condition that a function may return. Used in
 * discovery documents to document expected error responses, enabling clients
 * to implement proper error handling and display meaningful error messages.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/errors
 * @see https://docs.cline.sh/specs/forrst/discovery#error-definition-object
 */
final class ErrorDefinitionData extends Data
{
    /**
     * Machine-readable error code identifier.
     */
    public readonly string $code;

    /**
     * Create a new error definition.
     *
     * @param BackedEnum|string         $code        Machine-readable error code identifier following SCREAMING_SNAKE_CASE
     *                                               convention (e.g., ErrorCode::InvalidArgument, "RESOURCE_NOT_FOUND"). Used by
     *                                               clients to programmatically identify and handle specific error conditions
     *                                               without parsing human-readable messages.
     * @param string                    $message     Human-readable error message template describing the error condition.
     *                                               May include variable placeholders that are populated with context-specific
     *                                               values when the error occurs. Displayed to end users in error dialogs
     *                                               and logging output.
     * @param null|string               $description Optional detailed explanation of when this error occurs, what
     *                                               causes it, and how to resolve it. Provides additional context
     *                                               beyond the brief message for documentation and troubleshooting.
     * @param null|array<string, mixed> $details     JSON Schema definition for the error's details field.
     *                                               Specifies the structure and validation rules for
     *                                               additional error metadata, enabling type-safe error
     *                                               handling and validation in client implementations.
     */
    public function __construct(
        BackedEnum|string $code,
        public readonly string $message,
        public readonly ?string $description = null,
        public readonly ?array $details = null,
    ) {
        $this->code = match (true) {
            $code instanceof BackedEnum => (string) $code->value,
            default => $this->validateCode($code),
        };
    }

    /**
     * Validate error code follows SCREAMING_SNAKE_CASE convention.
     *
     * @throws \InvalidArgumentException
     */
    private function validateCode(string $code): string
    {
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $code)) {
            throw new \InvalidArgumentException(
                "Error code must follow SCREAMING_SNAKE_CASE convention. Got: '{$code}'"
            );
        }

        return $code;
    }
}
