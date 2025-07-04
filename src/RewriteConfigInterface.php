<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle;

/**
 * @internal this is an internal interface, you are not supposed do add your own configurations at the moment
 */
interface RewriteConfigInterface
{
    public const VALID_RESPONSE_CODES = [301, 302, 303, 307, 410];

    public function getIdentifier(): string;

    public function setIdentifier(string $identifier): void;

    public function getRequestPath(): string;

    public function getRequestHosts(): array;

    public function getRequestRequirements(): array;

    public function getRequestCondition(): string|null;

    public function getResponseCode(): int;

    /**
     * Returns a key value array where the key is the expression language condition, and the
     * value is the response URI in the same format as getResponseUri().
     *
     * @return array<string, string>
     */
    public function getConditionalResponseUris(): array;

    public function getResponseUri(): string|null;

    public function keepQueryParams(): bool;
}
