<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Extensions;

use Cline\Forrst\Data\ExtensionData;
use Cline\Forrst\Data\ResponseData;
use Cline\Forrst\Events\FunctionExecuted;
use Cline\Forrst\Events\RequestValidated;
use Override;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Intl\Timezones;

use function array_filter;
use function array_pop;
use function assert;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_string;
use function mb_strtoupper;

/**
 * Locale extension handler.
 *
 * Enables clients to specify language, region, and formatting preferences for
 * localized responses. Servers use these preferences to localize error messages,
 * format dates and numbers, and select appropriate translations. Implements
 * sophisticated language negotiation with fallback chains per RFC 5646.
 *
 * Request options:
 * - language: Language tag per RFC 5646 (e.g., 'en', 'en-US', 'zh-Hans')
 * - fallback: Array of fallback languages in preference order
 * - timezone: IANA timezone identifier (e.g., 'America/New_York', 'UTC')
 * - currency: ISO 4217 currency code (e.g., 'USD', 'EUR')
 *
 * Response data:
 * - language: Resolved language used in response
 * - fallback_used: Whether a fallback language was selected
 * - timezone: Validated timezone used for formatting
 * - currency: Validated currency used for formatting
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/locale
 */
final class LocaleExtension extends AbstractExtension
{
    /**
     * Default language when none specified or available.
     */
    public const string DEFAULT_LANGUAGE = 'en';

    /**
     * Default timezone when none specified.
     */
    public const string DEFAULT_TIMEZONE = 'UTC';

    /**
     * Resolved locale for the current request.
     *
     * @var array{language: string, fallback_used: bool, timezone: ?string, currency: ?string}
     */
    private array $resolvedLocale = [
        'language' => self::DEFAULT_LANGUAGE,
        'fallback_used' => false,
        'timezone' => null,
        'currency' => null,
    ];

    /**
     * Create a new extension instance.
     *
     * @param array<int, string> $supportedLanguages Language codes the server supports using RFC 5646 tags
     *                                               (e.g., ['en', 'en-US', 'fr', 'es']). The extension
     *                                               will negotiate the best match from this list when
     *                                               resolving client language preferences.
     */
    public function __construct(
        /**
         * Supported languages (configurable by server).
         *
         * @var array<int, string>
         */
        private readonly array $supportedLanguages = [self::DEFAULT_LANGUAGE],
    ) {}

    /**
     * {@inheritDoc}
     */
    #[Override()]
    public function getUrn(): string
    {
        return ExtensionUrn::Locale->value;
    }

    /**
     * {@inheritDoc}
     */
    #[Override()]
    public function isErrorFatal(): bool
    {
        return false; // Locale errors should not fail requests
    }

    /**
     * {@inheritDoc}
     */
    #[Override()]
    public function getSubscribedEvents(): array
    {
        return [
            RequestValidated::class => [
                'priority' => 50,
                'method' => 'onRequestValidated',
            ],
            FunctionExecuted::class => [
                'priority' => 100,
                'method' => 'onFunctionExecuted',
            ],
        ];
    }

    /**
     * Resolve and validate locale options on request validation.
     *
     * Extracts locale preferences from the request extension options and resolves
     * them against the server's supported languages and validation rules. Stores
     * the resolved locale for use during request processing.
     *
     * @param RequestValidated $event Event containing validated request data
     */
    public function onRequestValidated(RequestValidated $event): void
    {
        $extension = $event->request->getExtension(ExtensionUrn::Locale->value);

        if (!$extension instanceof ExtensionData) {
            return;
        }

        $options = $extension->options ?? [];
        $this->resolvedLocale = $this->resolveLocale($options);
    }

    /**
     * Add locale metadata to response after execution.
     *
     * Enriches the response with locale information indicating which language,
     * timezone, and currency were used for formatting. Helps clients understand
     * the context of localized data in the response.
     *
     * @param FunctionExecuted $event Event containing request and response data
     */
    public function onFunctionExecuted(FunctionExecuted $event): void
    {
        $responseData = array_filter([
            'language' => $this->resolvedLocale['language'],
            'fallback_used' => $this->resolvedLocale['fallback_used'],
            'timezone' => $this->resolvedLocale['timezone'],
            'currency' => $this->resolvedLocale['currency'],
        ], fn (bool|string|null $value): bool => $value !== null);

        $extensions = $event->getResponse()->extensions ?? [];
        $extensions[] = ExtensionData::response(ExtensionUrn::Locale->value, $responseData);

        $event->setResponse(
            new ResponseData(
                protocol: $event->getResponse()->protocol,
                id: $event->getResponse()->id,
                result: $event->getResponse()->result,
                errors: $event->getResponse()->errors,
                extensions: $extensions,
                meta: $event->getResponse()->meta,
            ),
        );
    }

    /**
     * Get the resolved language for the current request.
     *
     * Returns the language code that was negotiated for this request based on
     * client preferences and server support. Use this in application code to
     * select translations or format localized content.
     *
     * @return string RFC 5646 language tag (e.g., 'en', 'en-US')
     */
    public function getLanguage(): string
    {
        return $this->resolvedLocale['language'];
    }

    /**
     * Get the resolved timezone for the current request.
     *
     * Returns the validated IANA timezone identifier to use for date/time
     * formatting in this request, or null if no timezone was specified.
     *
     * @return null|string IANA timezone identifier or null
     */
    public function getTimezone(): ?string
    {
        return $this->resolvedLocale['timezone'];
    }

    /**
     * Get the resolved currency for the current request.
     *
     * Returns the validated ISO 4217 currency code to use for monetary
     * formatting in this request, or null if no currency was specified.
     *
     * @return null|string ISO 4217 currency code or null
     */
    public function getCurrency(): ?string
    {
        return $this->resolvedLocale['currency'];
    }

    /**
     * Check if a fallback language was used.
     *
     * Indicates whether the server selected a fallback language because the
     * client's preferred language was not available. Useful for logging or
     * displaying language mismatch warnings.
     *
     * @return bool True if fallback was used, false if exact match
     */
    public function wasFallbackUsed(): bool
    {
        return $this->resolvedLocale['fallback_used'];
    }

    /**
     * Get supported languages.
     *
     * Returns the list of language codes that this server supports. Useful
     * for capability negotiation or displaying available languages to clients.
     *
     * @return array<int, string> Array of RFC 5646 language tags
     */
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    /**
     * Validate a language tag using Symfony Intl.
     *
     * Checks whether the language tag is valid according to RFC 5646 by
     * validating the base language code against Symfony Intl's language database.
     *
     * @param  string $tag RFC 5646 language tag to validate
     * @return bool   True if valid, false otherwise
     */
    public function isValidLanguageTag(string $tag): bool
    {
        // Extract base language code
        $parts = explode('-', $tag);
        $baseLanguage = $parts[0];

        return Languages::exists($baseLanguage);
    }

    /**
     * {@inheritDoc}
     */
    #[Override()]
    public function toCapabilities(): array
    {
        return [
            'urn' => ExtensionUrn::Locale->value,
            'supported_languages' => $this->supportedLanguages,
            'default_language' => self::DEFAULT_LANGUAGE,
        ];
    }

    /**
     * Resolve locale from extension options.
     *
     * Processes client locale preferences and resolves them against server
     * capabilities and validation rules. Performs language negotiation,
     * timezone validation, and currency code normalization.
     *
     * @param  array<string, mixed>                                                               $options Extension options from request
     * @return array{language: string, fallback_used: bool, timezone: ?string, currency: ?string} Resolved locale configuration
     */
    private function resolveLocale(array $options): array
    {
        $requestedLanguage = $options['language'] ?? null;
        assert($requestedLanguage === null || is_string($requestedLanguage));

        /** @var array<int, string> $fallbacks */
        $fallbacks = $options['fallback'] ?? [];

        $timezone = $options['timezone'] ?? null;
        assert($timezone === null || is_string($timezone));

        $currency = $options['currency'] ?? null;
        assert($currency === null || is_string($currency));

        // Resolve language with fallback chain
        [$language, $fallbackUsed] = $this->resolveLanguage($requestedLanguage, $fallbacks);

        // Validate timezone
        $validatedTimezone = $this->validateTimezone($timezone);

        // Validate currency
        $validatedCurrency = $this->validateCurrency($currency);

        return [
            'language' => $language,
            'fallback_used' => $fallbackUsed,
            'timezone' => $validatedTimezone,
            'currency' => $validatedCurrency,
        ];
    }

    /**
     * Resolve language using fallback chain.
     *
     * Implements sophisticated language negotiation using progressive fallback.
     * Attempts exact match first, then progressively shorter language tags,
     * then fallback languages, and finally defaults to English.
     *
     * Resolution order:
     * 1. Exact match (e.g., zh-Hans-CN)
     * 2. Base match (e.g., zh-Hans)
     * 3. Language only (e.g., zh)
     * 4. Fallback languages (same progressive matching)
     * 5. Default (en)
     *
     * @param  null|string               $requested Requested RFC 5646 language tag
     * @param  array<int, string>        $fallbacks Fallback language tags in preference order
     * @return array{0: string, 1: bool} Tuple of [resolved language tag, whether fallback was used]
     */
    private function resolveLanguage(?string $requested, array $fallbacks): array
    {
        if ($requested === null) {
            return [self::DEFAULT_LANGUAGE, true];
        }

        // Try exact match first
        if ($this->isLanguageSupported($requested)) {
            return [$requested, false];
        }

        // Try progressively shorter language tags
        $parts = explode('-', $requested);

        while (count($parts) > 1) {
            array_pop($parts);
            $shorter = implode('-', $parts);

            if ($this->isLanguageSupported($shorter)) {
                return [$shorter, true];
            }
        }

        // Try fallback languages
        foreach ($fallbacks as $fallback) {
            if ($this->isLanguageSupported($fallback)) {
                return [$fallback, true];
            }

            // Also try shorter versions of fallback
            $fallbackParts = explode('-', $fallback);

            while (count($fallbackParts) > 1) {
                array_pop($fallbackParts);
                $shorterFallback = implode('-', $fallbackParts);

                if ($this->isLanguageSupported($shorterFallback)) {
                    return [$shorterFallback, true];
                }
            }
        }

        // Default to English
        return [self::DEFAULT_LANGUAGE, true];
    }

    /**
     * Check if a language is supported.
     *
     * Verifies whether the given language tag is in the server's supported
     * languages list. Also checks base language codes for broader matching
     * (e.g., 'en' matches 'en-US').
     *
     * @param  string $language RFC 5646 language tag to check
     * @return bool   True if supported, false otherwise
     */
    private function isLanguageSupported(string $language): bool
    {
        // Check server's supported languages
        if (in_array($language, $this->supportedLanguages, true)) {
            return true;
        }

        // Check base language (e.g., 'en' for 'en-US')
        $baseLang = explode('-', $language)[0];

        return in_array($baseLang, $this->supportedLanguages, true);
    }

    /**
     * Validate and normalize timezone.
     *
     * Validates the timezone identifier against the IANA timezone database
     * using Symfony Intl. Returns null for invalid timezones to prevent
     * runtime errors during date formatting.
     *
     * @param  null|string $timezone IANA timezone identifier to validate
     * @return null|string Validated timezone or null if invalid
     */
    private function validateTimezone(?string $timezone): ?string
    {
        if ($timezone === null) {
            return null;
        }

        // Use Symfony Intl for timezone validation
        if (Timezones::exists($timezone)) {
            return $timezone;
        }

        return null;
    }

    /**
     * Validate and normalize currency code.
     *
     * Validates and normalizes currency codes to uppercase ISO 4217 format
     * using Symfony Intl's currency database. Returns null for invalid codes
     * to prevent formatting errors.
     *
     * @param  null|string $currency ISO 4217 currency code to validate
     * @return null|string Normalized uppercase currency code or null if invalid
     */
    private function validateCurrency(?string $currency): ?string
    {
        if ($currency === null) {
            return null;
        }

        $normalized = mb_strtoupper($currency);

        // Use Symfony Intl for currency validation
        if (Currencies::exists($normalized)) {
            return $normalized;
        }

        return null;
    }
}
