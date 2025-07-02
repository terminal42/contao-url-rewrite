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
        if (!isset($data['id'], $data['type'], $data['requestPath'], $data['responseCode'])) {
            return null;
        }

        $config = new RewriteConfig((string) $data['id'], $data['requestPath'], (int) $data['responseCode']);

        // Hosts
        if (isset($data['requestHosts'])) {
            $config->setRequestHosts(StringUtil::deserialize($data['requestHosts'], true));
        }

        // Response URI
        if (isset($data['responseUri'])) {
            $config->setResponseUri($data['responseUri']);
        }

        // Keep query parameters
        if (isset($data['keepQueryParams'])) {
            $config->setKeepQueryParams(true);
        }

        switch ($data['type']) {
            // Basic type
            case 'basic':
                if (isset($data['requestRequirements'])) {
                    $requirements = [];

                    foreach (StringUtil::deserialize($data['requestRequirements'], true) as $requirement) {
                        if ('' !== $requirement['key'] && '' !== $requirement['value']) {
                            $requirements[$requirement['key']] = $requirement['value'];
                        }
                    }

                    $config->setRequestRequirements($requirements);
                }
                break;
            // Expert type
            case 'expert':
                if (isset($data['requestCondition'])) {
                    $config->setRequestCondition($data['requestCondition']);
                }
                break;
            // Unsupported type
            default:
                throw new \RuntimeException(\sprintf('Unsupported database record config type: %s', $data['type']));
        }

        return $config;
    }
}
