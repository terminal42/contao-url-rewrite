<?php

namespace Terminal42\UrlRewriteBundle\ConfigProvider;

use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Terminal42\UrlRewriteBundle\RewriteConfig;

class DatabaseConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $key = 'database';

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
     * @inheritDoc
     */
    public function find(string $id): ?RewriteConfig
    {
        list($key, $id) = explode(':', $id);

        // Return if the key is not supported
        if ($key !== $this->key) {
            return null;
        }

        try {
            $data = $this->connection->fetchAssoc('SELECT * FROM tl_url_rewrite WHERE id=?', [$id]);
        } catch (\PDOException | TableNotFoundException $e) {
            return null;
        }

        if (false === $data) {
            return null;
        }

        return $this->createConfig($data);
    }

    /**
     * @inheritDoc
     */
    public function findAll(): array
    {
        try {
            $records = $this->connection->fetchAll('SELECT * FROM tl_url_rewrite');
        } catch (\PDOException | TableNotFoundException $e) {
            return [];
        }

        if (count($records) === 0) {
            return [];
        }

        $configs = [];

        foreach ($records as $record) {
            if (($config = $this->createConfig($record)) !== null) {
                $configs[] = $config;
            }
        }

        return $configs;
    }

    /**
     * Create the config
     *
     * @param array $data
     *
     * @return null|RewriteConfig
     */
    private function createConfig(array $data): ?RewriteConfig
    {
        if (!isset($data['id'], $data['type'], $data['requestPath'], $data['responseCode'])) {
            return null;
        }

        $config = new RewriteConfig($this->key . ':' . $data['id'], $data['requestPath'], (int) $data['responseCode']);

        // Hosts
        if (isset($data['requestHosts'])) {
            $config->setRequestHosts(StringUtil::deserialize($data['requestHosts'], true));
        }

        switch ($data['type']) {
            // Basic type
            case 'basic':
                if (isset($data['requestRequirements'])) {
                    $requirements = [];

                    foreach (StringUtil::deserialize($data['requestRequirements'], true) as $requirement) {
                        if ($requirement['key'] !== '' && $requirement['value'] !== '') {
                            $requirements[$requirement['key']] = $requirement['value'];
                        }
                    }

                    $config->setRequestRequirements($requirements);
                }
                break;
            // Expert type
            case 'expert':
                $config->setRequestCondition($data['requestCondition']);
                break;
            default:
                throw new \RuntimeException(sprintf('Unsupported database record config type: %s', $data['type']));
        }

        return $config;
    }
}
