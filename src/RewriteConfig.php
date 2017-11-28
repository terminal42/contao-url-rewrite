<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2017, terminal42 gmbh
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
     *
     * @param string $identifier
     * @param string $requestPath
     * @param int    $responseCode
     */
    public function __construct(string $identifier, string $requestPath, int $responseCode = 301)
    {
        $this->identifier = $identifier;
        $this->setRequestPath($requestPath);
        $this->setResponseCode($responseCode);
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getRequestPath(): string
    {
        return $this->requestPath;
    }

    /**
     * @param string $requestPath
     */
    public function setRequestPath(string $requestPath): void
    {
        $this->requestPath = $requestPath;
    }

    /**
     * @return array
     */
    public function getRequestHosts(): array
    {
        return $this->requestHosts;
    }

    /**
     * @param array $requestHosts
     */
    public function setRequestHosts(array $requestHosts): void
    {
        $this->requestHosts = array_unique(array_filter($requestHosts));
    }

    /**
     * @return array
     */
    public function getRequestRequirements(): array
    {
        return $this->requestRequirements;
    }

    /**
     * @param array $requestRequirements
     */
    public function setRequestRequirements(array $requestRequirements): void
    {
        $this->requestRequirements = $requestRequirements;
    }

    /**
     * @return null|string
     */
    public function getRequestCondition(): ?string
    {
        return $this->requestCondition;
    }

    /**
     * @param null|string $requestCondition
     */
    public function setRequestCondition(string $requestCondition): void
    {
        $this->requestCondition = $requestCondition;
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    /**
     * @param int $responseCode
     *
     * @throws \InvalidArgumentException
     */
    public function setResponseCode(int $responseCode): void
    {
        if (!\in_array($responseCode, self::VALID_RESPONSE_CODES, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid response code: %s', $responseCode));
        }

        $this->responseCode = $responseCode;
    }

    /**
     * @return null|string
     */
    public function getResponseUri(): ?string
    {
        return $this->responseUri;
    }

    /**
     * @param null|string $responseUri
     */
    public function setResponseUri(string $responseUri): void
    {
        $this->responseUri = $responseUri;
    }
}
