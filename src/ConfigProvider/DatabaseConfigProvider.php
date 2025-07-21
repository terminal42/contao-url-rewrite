<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\ConfigProvider;

use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Terminal42\UrlRewriteBundle\Exception\TemporarilyUnavailableConfigProviderException;
use Terminal42\UrlRewriteBundle\RewriteConfig;
use Terminal42\UrlRewriteBundle\RewriteConfigInterface;

class DatabaseConfigProvider implements ConfigProviderInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function find(string $id): RewriteConfigInterface|null
    {
        try {
            $data = $this->connection->fetchAssociative('SELECT * FROM tl_url_rewrite WHERE id=? AND inactive=?', [$id, 0]);
        } catch (\PDOException|ConnectionException|TableNotFoundException|InvalidFieldNameException $e) {
            throw new TemporarilyUnavailableConfigProviderException($e->getMessage(), $e->getCode(), $e);
        }

        if (false === $data) {
            return null;
        }

        return $this->createConfig($data);
    }

    public function findAll(): array
    {
        try {
            $records = $this->connection->fetchAllAssociative('SELECT * FROM tl_url_rewrite WHERE inactive=? ORDER BY priority DESC', [0]);
        } catch (\PDOException|ConnectionException|TableNotFoundException|InvalidFieldNameException) {
            return [];
        }

        if (0 === \count($records)) {
            return [];
        }

        $configs = [];

        foreach ($records as $record) {
            if (null !== ($config = $this->createConfig($record))) {
                $configs[] = $config;
            }
        }

        return $configs;
    }

    /**
     * Create the config.
     */
    private function createConfig(array $data): RewriteConfig|null
    {
        if (!isset($data['id'], $data['requestPath'], $data['responseCode'])) {
            return null;
        }

        $config = new RewriteConfig((string) $data['id'], $data['requestPath'], (int) $data['responseCode']);

        // Hosts
        if (isset($data['requestHosts'])) {
            $config->setRequestHosts(StringUtil::deserialize($data['requestHosts'], true));
        }

        // Conditional response URIs
        $config->setConditionalResponseUris(self::parseKeyValueWizardValue($data['conditionalResponseUri'] ?? null));

        // Response URI
        if (isset($data['responseUri'])) {
            $config->setResponseUri($data['responseUri']);
        }

        // Keep query parameters
        if ($data['keepQueryParams'] ?? false) {
            $config->setKeepQueryParams(true);
        }

        // Request requirements
        $config->setRequestRequirements(self::parseKeyValueWizardValue($data['requestRequirements'] ?? null));

        // Request condition
        if (isset($data['requestCondition'])) {
            $config->setRequestCondition($data['requestCondition']);
        }

        return $config;
    }

    /**
     * @return array<string, string>
     */
    private static function parseKeyValueWizardValue(string|null $value): array
    {
        $parsed = [];

        foreach (StringUtil::deserialize($value, true) as $row) {
            if ('' !== $row['key'] && '' !== $row['value']) {
                $parsed[$row['key']] = $row['value'];
            }
        }

        return $parsed;
    }
}
