<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2021, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle;

class RewriteConfig implements RewriteConfigInterface
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $requestPath = '';

    /**
     * @var array
     */
    private $requestHosts = [];

    /**
     * @var array
     */
    private $requestRequirements = [];

    /**
     * @var string|null
     */
    private $requestCondition;

    /**
     * @var int
     */
    private $responseCode = 301;

    /**
     * @var string|null
     */
    private $responseUri;

    /**
     * RewriteConfig constructor.
     */
    public function __construct(string $identifier, string $requestPath, int $responseCode = 301)
    {
        $this->identifier = $identifier;
        $this->setRequestPath($requestPath);
        $this->setResponseCode($responseCode);
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getRequestPath(): string
    {
        return $this->requestPath;
    }

    public function setRequestPath(string $requestPath): void
    {
        $this->requestPath = $requestPath;
    }

    public function getRequestHosts(): array
    {
        return $this->requestHosts;
    }

    public function setRequestHosts(array $requestHosts): void
    {
        $this->requestHosts = array_values(array_unique(array_filter($requestHosts)));
    }

    public function getRequestRequirements(): array
    {
        return $this->requestRequirements;
    }

    public function setRequestRequirements(array $requestRequirements): void
    {
        $this->requestRequirements = $requestRequirements;
    }

    public function getRequestCondition(): ?string
    {
        return $this->requestCondition;
    }

    /**
     * @param string|null $requestCondition
     */
    public function setRequestCondition(string $requestCondition): void
    {
        $this->requestCondition = $requestCondition;
    }

    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setResponseCode(int $responseCode): void
    {
        if (!\in_array($responseCode, self::VALID_RESPONSE_CODES, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid response code: %s', $responseCode));
        }

        $this->responseCode = $responseCode;
    }

    public function getResponseUri(): ?string
    {
        return $this->responseUri;
    }

    /**
     * @param string|null $responseUri
     */
    public function setResponseUri(string $responseUri): void
    {
        $this->responseUri = $responseUri;
    }
}
