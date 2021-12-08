<?php

declare(strict_types=1);

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2021, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle;

interface RewriteConfigInterface
{
    public const VALID_RESPONSE_CODES = [301, 302, 303, 307, 410];

    public function getIdentifier(): string;

    public function setIdentifier(string $identifier): void;

    public function getRequestPath(): string;

    public function getRequestHosts(): array;

    public function getRequestRequirements(): array;

    public function getRequestCondition(): ?string;

    public function getResponseCode(): int;

    public function getResponseUri(): ?string;
}
