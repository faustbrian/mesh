<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Data;

use Cline\Forrst\Exceptions\AbstractRequestException;
use Override;

use function array_any;
use function array_map;
use function count;
use function is_array;
use function is_string;
use function sprintf;

use InvalidArgumentException;

/**
 * Forrst protocol compliant response object.
 *
 * Encapsulates all components of a Forrst protocol response including protocol
 * version, request identifier, result data or errors, extensions, and metadata.
 * Responses must contain either a result (success) or errors (failure), never both.
 * The id field echoes the request identifier to enable request-response correlation.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://docs.cline.sh/forrst/protocol
 * @see https://docs.cline.sh/forrst/document-structure
 */
final class ResponseData extends AbstractData
{
    /**
     * Maximum allowed size for errors array to prevent memory exhaustion.
     */
    private const MAX_ERRORS_COUNT = 100;

    /**
     * Maximum allowed size for extensions array to prevent memory exhaustion.
     */
    private const MAX_EXTENSIONS_COUNT = 50;

    /**
     * Maximum allowed depth for nested meta data to prevent stack overflow.
     */
    private const MAX_META_DEPTH = 10;

    /**
     * Create a new response data instance.
     *
     * @param ProtocolData              $protocol   Forrst protocol identifier object containing protocol name
     *                                              and version. Must match or be compatible with the request
     *                                              protocol version.
     * @param string                    $id         Request identifier echoed from the original request. Must
     *                                              exactly match the request id to enable proper correlation
     *                                              between requests and responses in async scenarios.
     * @param mixed                     $result     The successful result data, typically a resource object,
     *                                              collection, or scalar value. Set to null when errors occur.
     *                                              Must not be present when errors array is populated.
     * @param null|array<ErrorData>     $errors     Array of error objects when request processing fails. Set to
     *                                              null on successful execution. Must contain at least one error
     *                                              when present. Cannot coexist with a non-null result.
     * @param null|array<ExtensionData> $extensions Optional array of extension response data containing results
     *                                              from extension processing (cache status, async job ids, etc.).
     * @param null|array<string, mixed> $meta       Optional metadata about the response such as execution timing,
     *                                              debug information, API version, or other non-standard data
     *                                              that doesn't fit in the standard response structure.
     */
    public function __construct(
        public readonly ProtocolData $protocol,
        public readonly string $id,
        public readonly mixed $result = null,
        public readonly ?array $errors = null,
        public readonly ?array $extensions = null,
        public readonly ?array $meta = null,
    ) {
        // Validate ID is not empty
        if ($id === '') {
            throw new InvalidArgumentException('Response ID cannot be empty');
        }

        // Validate that result and errors are mutually exclusive
        if ($result !== null && $errors !== null && $errors !== []) {
            throw new InvalidArgumentException('Response cannot have both result and errors');
        }

        // Validate errors array size and content
        if ($errors !== null) {
            if (count($errors) > self::MAX_ERRORS_COUNT) {
                throw new InvalidArgumentException(
                    sprintf('Errors array cannot exceed %d items', self::MAX_ERRORS_COUNT)
                );
            }

            if ($errors !== [] && count($errors) === 0) {
                throw new InvalidArgumentException('Errors array cannot be empty when set');
            }

            foreach ($errors as $error) {
                if (!$error instanceof ErrorData) {
                    throw new InvalidArgumentException('All errors must be instances of ErrorData');
                }
            }
        }

        // Validate extensions array size and content
        if ($extensions !== null) {
            if (count($extensions) > self::MAX_EXTENSIONS_COUNT) {
                throw new InvalidArgumentException(
                    sprintf('Extensions array cannot exceed %d items', self::MAX_EXTENSIONS_COUNT)
                );
            }

            foreach ($extensions as $extension) {
                if (!$extension instanceof ExtensionData) {
                    throw new InvalidArgumentException('All extensions must be instances of ExtensionData');
                }
            }
        }

        // Validate meta depth
        if ($meta !== null) {
            $this->validateArrayDepth($meta, self::MAX_META_DEPTH, 'meta');
        }
    }

    /**
     * Create a response from an array.
     *
     * Hydrates a response object from an associative array, typically from JSON-decoded
     * response data. Constructs nested protocol, error, and extension data objects.
     *
     * @param array<string, mixed> ...$payloads Response data arrays (only first element used)
     *
     * @return static Response instance with hydrated nested objects
     */
    public static function from(mixed ...$payloads): static
    {
        $rawData = $payloads[0] ?? [];

        /** @var array<string, mixed> $data */
        $data = $rawData;

        // Handle protocol transformation
        $protocolData = $data['protocol'] ?? [];

        /** @var array{name?: string, version?: string} $protocolArray */
        $protocolArray = is_array($protocolData) ? $protocolData : [];

        $protocol = ProtocolData::from($protocolArray);

        // Handle errors array transformation
        $errors = null;

        if (isset($data['errors']) && is_array($data['errors'])) {
            /** @var array<ErrorData> $errors */
            $errors = array_map(
                function (mixed $errorData): ErrorData {
                    /** @var array<string, mixed> $errorArray */
                    $errorArray = is_array($errorData) ? $errorData : [];

                    return ErrorData::from($errorArray);
                },
                $data['errors'],
            );
        }

        // Handle extensions array transformation
        $extensions = null;

        if (isset($data['extensions']) && is_array($data['extensions'])) {
            /** @var array<ExtensionData> $extensions */
            $extensions = array_map(
                function (mixed $extensionData): ExtensionData {
                    /** @var array<string, mixed> $extensionArray */
                    $extensionArray = is_array($extensionData) ? $extensionData : [];

                    return ExtensionData::from($extensionArray);
                },
                $data['extensions'],
            );
        }

        $id = $data['id'] ?? '';
        $rawMeta = $data['meta'] ?? null;

        /** @var null|array<string, mixed> $meta */
        $meta = is_array($rawMeta) ? $rawMeta : null;

        return new self(
            protocol: $protocol,
            id: is_string($id) ? $id : '',
            result: $data['result'] ?? null,
            errors: $errors,
            extensions: $extensions,
            meta: $meta,
        );
    }

    /**
     * Create a success response.
     *
     * Factory method for creating a successful response with result data.
     * Sets errors to null and uses standard Forrst protocol identifier.
     *
     * @param mixed                     $result     The result data (resource, collection, or scalar)
     * @param string                    $id         The request identifier echoed from the request
     * @param null|array<ExtensionData> $extensions Optional extension response data
     * @param null|array<string, mixed> $meta       Optional metadata
     *
     * @return self Success response with result populated and errors null
     */
    public static function success(
        mixed $result,
        string $id,
        ?array $extensions = null,
        ?array $meta = null,
    ): self {
        return new self(
            protocol: ProtocolData::forrst(),
            id: $id,
            result: $result,
            errors: null,
            extensions: $extensions,
            meta: $meta,
        );
    }

    /**
     * Create an error response from a request exception.
     *
     * Converts a request exception to a properly formatted error response.
     * The exception's error data is extracted and placed in the errors array.
     *
     * @param AbstractRequestException  $exception  The exception containing error details to convert
     * @param string                    $id         The request identifier echoed from the request
     * @param null|array<ExtensionData> $extensions Optional extension response data
     * @param null|array<string, mixed> $meta       Optional metadata
     *
     * @return self Error response with exception converted to ErrorData
     */
    public static function fromException(
        AbstractRequestException $exception,
        string $id,
        ?array $extensions = null,
        ?array $meta = null,
    ): self {
        return new self(
            protocol: ProtocolData::forrst(),
            id: $id,
            result: null,
            errors: [$exception->toError()],
            extensions: $extensions,
            meta: $meta,
        );
    }

    /**
     * Create an error response with a single error.
     *
     * Factory method for creating an error response with one error object.
     * The error is wrapped in an array per Forrst protocol specification.
     *
     * @param ErrorData                 $error      The error data object to include in response
     * @param string                    $id         The request identifier echoed from the request
     * @param null|array<ExtensionData> $extensions Optional extension response data
     * @param null|array<string, mixed> $meta       Optional metadata
     *
     * @return self Error response with single error in errors array
     */
    public static function error(
        ErrorData $error,
        string $id,
        ?array $extensions = null,
        ?array $meta = null,
    ): self {
        return new self(
            protocol: ProtocolData::forrst(),
            id: $id,
            result: null,
            errors: [$error],
            extensions: $extensions,
            meta: $meta,
        );
    }

    /**
     * Create an error response with multiple errors.
     *
     * Factory method for creating an error response with multiple error objects.
     * Useful when validation produces multiple errors or when aggregating failures.
     *
     * @param array<ErrorData>          $errors     Array of error data objects (must not be empty)
     * @param string                    $id         The request identifier echoed from the request
     * @param null|array<ExtensionData> $extensions Optional extension response data
     * @param null|array<string, mixed> $meta       Optional metadata
     *
     * @return self Error response with multiple errors in errors array
     */
    public static function errors(
        array $errors,
        string $id,
        ?array $extensions = null,
        ?array $meta = null,
    ): self {
        return new self(
            protocol: ProtocolData::forrst(),
            id: $id,
            result: null,
            errors: $errors,
            extensions: $extensions,
            meta: $meta,
        );
    }

    /**
     * Determine if the request was successful.
     *
     * A response is successful when it contains no errors. Note that a null
     * result with no errors is still considered successful.
     *
     * @return bool True if no errors occurred, false otherwise
     */
    public function isSuccessful(): bool
    {
        return !$this->isFailed();
    }

    /**
     * Determine if the response indicates an error occurred.
     *
     * A response has failed when it contains one or more error objects
     * in the errors array.
     *
     * @return bool True if the response contains error information, false otherwise
     */
    public function isFailed(): bool
    {
        return $this->errors !== null && $this->errors !== [];
    }

    /**
     * Determine if the response indicates a client error occurred.
     *
     * Client errors typically have 4xx status codes and indicate problems with
     * the request such as invalid arguments, missing required fields, or authentication failures.
     *
     * @return bool True if any error is a client-side error, false otherwise
     */
    public function isClientError(): bool
    {
        if ($this->errors === null) {
            return false;
        }

        return array_any($this->errors, fn ($error) => $error->isClient());
    }

    /**
     * Determine if the response indicates a server error occurred.
     *
     * Server errors typically have 5xx status codes and indicate problems with
     * the server such as unhandled exceptions, database failures, or service unavailability.
     *
     * @return bool True if any error is a server-side error, false otherwise
     */
    public function isServerError(): bool
    {
        if ($this->errors === null) {
            return false;
        }

        return array_any($this->errors, fn ($error) => $error->isServer());
    }

    /**
     * Get the first error if present.
     *
     * Convenience method for accessing the first error when you only need
     * to display a single error message to the user.
     *
     * @return null|ErrorData The first error or null if no errors exist
     */
    public function getFirstError(): ?ErrorData
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Get an extension by URN.
     *
     * Searches the extensions array for an extension matching the specified URN.
     * Extensions use URN format like "urn:forrst:ext:async" for identification.
     *
     * @param string $urn The extension URN to search for (e.g., "urn:forrst:ext:cache")
     *
     * @return null|ExtensionData The extension object or null if not found
     */
    public function getExtension(string $urn): ?ExtensionData
    {
        if ($this->extensions === null) {
            return null;
        }

        foreach ($this->extensions as $extension) {
            if ($extension->urn === $urn) {
                return $extension;
            }
        }

        return null;
    }

    /**
     * Check if a specific extension is present in the response.
     *
     * Convenience method that checks if an extension with the given URN
     * exists in the extensions array.
     *
     * @param string $urn The extension URN to check (e.g., "urn:forrst:ext:batch")
     *
     * @return bool True if the extension is present, false otherwise
     */
    public function hasExtension(string $urn): bool
    {
        return $this->getExtension($urn) instanceof ExtensionData;
    }

    /**
     * Convert the response data to an array representation.
     *
     * Per Forrst protocol specification:
     * - Errors always use the `errors` field (plural array)
     * - The `errors` array MUST contain at least one error
     *
     * @return array<string, mixed> The Forrst protocol compliant response array
     */
    #[Override()]
    public function toArray(): array
    {
        $response = [
            'protocol' => $this->protocol->toArray(),
            'id' => $this->id,
        ];

        if ($this->errors !== null && $this->errors !== []) {
            $response['result'] = null;
            $response['errors'] = array_map(
                fn (ErrorData $err): array => $err->toArray(),
                $this->errors,
            );
        } else {
            $response['result'] = $this->result;
        }

        if ($this->extensions !== null) {
            $response['extensions'] = array_map(
                fn (ExtensionData $ext): array => $ext->toResponseArray(),
                $this->extensions,
            );
        }

        if ($this->meta !== null) {
            $response['meta'] = $this->meta;
        }

        return $response;
    }
}
