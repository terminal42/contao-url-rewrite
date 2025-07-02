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

    public function getResponseUri(): string|null;

    public function keepQueryParams(): bool;
}
