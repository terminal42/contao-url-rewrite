<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle;

interface RewriteConfigInterface
{
    const VALID_RESPONSE_CODES = [301, 302, 303, 307, 410];

    /**
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * @return string
     */
    public function getRequestPath(): string;

    /**
     * @return array
     */
    public function getRequestHosts(): array;

    /**
     * @return array
     */
    public function getRequestRequirements(): array;

    /**
     * @return null|string
     */
    public function getRequestCondition(): ?string;

    /**
     * @return int
     */
    public function getResponseCode(): int;

    /**
     * @return null|string
     */
    public function getResponseUri(): ?string;
}
