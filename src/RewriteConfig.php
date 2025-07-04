<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle;

class RewriteConfig implements RewriteConfigInterface
{
    private string $requestPath = '';

    private array $requestHosts = [];

    private array $requestRequirements = [];

    private string|null $requestCondition = null;

    private int $responseCode = 301;

    /**
     * @var array<string, string>
     */
    private array $conditionalResponseUris = [];

    private string|null $responseUri = null;

    private bool $keepQueryParams = false;

    public function __construct(
        private string $identifier,
        string $requestPath,
        int $responseCode = 301,
    ) {
        $this->setRequestPath($requestPath);
        $this->setResponseCode($responseCode);
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

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

    public function getRequestCondition(): string|null
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
            throw new \InvalidArgumentException(\sprintf('Invalid response code: %s', $responseCode));
        }

        $this->responseCode = $responseCode;
    }

    public function getResponseUri(): string|null
    {
        return $this->responseUri;
    }

    public function setResponseUri(string|null $responseUri): void
    {
        $this->responseUri = $responseUri;
    }

    public function setKeepQueryParams(bool $keepQueryParams): void
    {
        $this->keepQueryParams = $keepQueryParams;
    }

    public function keepQueryParams(): bool
    {
        return $this->keepQueryParams;
    }

    /**
     * @param array<string, string> $conditionalResponseUris
     */
    public function setConditionalResponseUris(array $conditionalResponseUris): void
    {
        $this->conditionalResponseUris = $conditionalResponseUris;
    }

    public function getConditionalResponseUris(): array
    {
        return $this->conditionalResponseUris;
    }
}
