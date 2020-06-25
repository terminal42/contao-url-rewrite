<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2020, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Terminal42\UrlRewriteBundle\EventListener\RewriteContainerListener;

class ConvertShortUrlsCommand extends Command
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var RewriteContainerListener
     */
    private $rewriteContainerListener;

    public function __construct(Connection $connection, RewriteContainerListener $rewriteContainerListener)
    {
        $this->connection = $connection;

        parent::__construct();
        $this->rewriteContainerListener = $rewriteContainerListener;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('terminal42:url-rewrite:convert-short-urls')
            ->setDescription('Convert all rules from fritzmg/contao-short-urls.')
            ->addOption(
                'no-remove',
                '',
                InputOption::VALUE_OPTIONAL,
                'Flag to determine whether the source short urls should not be removed after conversion.',
                false
            );
    }

    /**
     * @throws DBALException
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $noRemove = (false !== $input->getOption('no-remove'));
        if ($noRemove) {
            $io->note('Short urls will not be removed after conversion!');
        }

        if (!$this->canConvert($io)) {
            return;
        }

        return $this->convert($io, $noRemove);
    }

    /**
     * @throws DBALException
     */
    private function canConvert(SymfonyStyle $io): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_url_rewrite'])) {
            $io->error('Table `tl_url_rewrite` not found.');

            return false;
        }

        if (!$schemaManager->tablesExist(['tl_short_urls'])) {
            $io->error('Table `tl_short_urls` not found.');

            return false;
        }

        $statement = $this->connection->prepare('SELECT id FROM tl_short_urls');
        $statement->execute();

        if (($count = $statement->rowCount()) > 0) {
            $io->progressStart($count);

            return true;
        }

        $io->error('No short urls found to process.');

        return false;
    }

    /**
     * @throws DBALException
     *
     * @return int
     */
    private function convert(SymfonyStyle $io, bool $noRemove)
    {
        $domains = $this->getPageDomains();
        $conversionDateTime = date('Y-m-d H:i');

        $statement = $this->connection->query('SELECT * FROM tl_short_urls');

        while (false !== ($row = $statement->fetch(\PDO::FETCH_OBJ))) {
            $statementInsert = $this->connection->prepare(
                "
                INSERT INTO tl_url_rewrite (
                    name,
                    type,
                    priority,
                    comment,
                    inactive,
                    requestHosts,
                    requestPath,
                    requestRequirements,
                    requestCondition,
                    responseCode,
                    responseUri,
                    tstamp
                ) VALUES
                (
                    :name,
                    'basic',
                    0,
                    :comment,
                    :inactive,
                    :requestHosts,
                    :requestPath,
                    NULL,
                    '',
                    :responseCode,
                    :responseUri,
                    :tstamp
                 )"
            );

            $statementInsert->execute(
                [
                    ':name' => $row->name,
                    ':comment' => sprintf('Short url ID %s [%s]', $row->id, $conversionDateTime),
                    ':inactive' => $row->disable,
                    ':requestHosts' => !empty($row->domain) && isset($domains[$row->domain]) ? serialize([$domains[$row->domain]]) : null,
                    ':requestPath' => $row->name,
                    ':responseCode' => 'temporary' === $row->redirect ? 302 : 301,
                    ':responseUri' => $row->target,
                    ':tstamp' => $row->tstamp,
                ]
            );

            if (!$noRemove) {
                $statementDelete = $this->connection->prepare(
                    'DELETE FROM tl_short_urls WHERE id = :id'
                );
                $statementDelete->execute([':id' => $row->id]);
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        $this->rewriteContainerListener->onRecordsModified();

        $io->success('All short urls processed.');

        return 0;
    }

    private function getPageDomains()
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_page'])) {
            return [];
        }
        $statement = $this->connection->query(
                "SELECT id, dns
                FROM tl_page
                WHERE type = 'root'
                  AND dns != ''"
            );

        return array_column($statement->fetchAll(), 'dns', 'id');
    }
}
