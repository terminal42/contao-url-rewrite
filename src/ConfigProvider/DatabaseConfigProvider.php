<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2019, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

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
    /**
     * @var Connection
     */
    private $connection;

    /**
     * DatabaseConfigProvider constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $id): ?RewriteConfigInterface
    {
        try {
            $data = $this->connection->fetchAssoc('SELECT * FROM tl_url_rewrite WHERE id=? AND inactive=?', [$id, 0]);
        } catch (\PDOException | ConnectionException | TableNotFoundException | InvalidFieldNameException $e) {
            throw new TemporarilyUnavailableConfigProviderException($e->getMessage(), $e->getCode(), $e);
        }

        if (false === $data) {
            return null;
        }

        return $this->createConfig($data);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        try {
            $records = $this->connection->fetchAll('SELECT * FROM tl_url_rewrite WHERE inactive=? ORDER BY priority DESC', [0]);
        } catch (\PDOException | ConnectionException | TableNotFoundException | InvalidFieldNameException $e) {
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
     *
     * @param array $data
     *
     * @return RewriteConfig|null
     */
    private function createConfig(array $data): ?RewriteConfig
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
                throw new \RuntimeException(sprintf('Unsupported database record config type: %s', $data['type']));
        }

        return $config;
    }
}
