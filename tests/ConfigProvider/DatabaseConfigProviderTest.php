<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\ConfigProvider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use PHPUnit\Framework\TestCase;
use Terminal42\UrlRewriteBundle\ConfigProvider\DatabaseConfigProvider;
use Terminal42\UrlRewriteBundle\Exception\TemporarilyUnavailableConfigProviderException;

class DatabaseConfigProviderTest extends TestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf(DatabaseConfigProvider::class, new DatabaseConfigProvider($this->createMock(Connection::class)));
    }

    /**
     * @dataProvider findDataProvider
     */
    public function testFind($row, $expected): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn($row)
        ;

        $provider = new DatabaseConfigProvider($connection);

        // Handle the exception
        if (\is_array($expected) && isset($expected['exception'])) {
            $this->expectException($expected['exception']);
            $provider->find('foobar');

            return;
        }

        // Compare the values
        if (\is_array($expected)) {
            $config = $provider->find('foobar');

            foreach ($expected as $method => $value) {
                $this->assertSame($value, $config->$method());
            }

            return;
        }

        $this->assertSame($expected, $provider->find('foobar'));
    }

    public static function findDataProvider(): iterable
    {
        return [
            'Row not found' => [
                false,
                null,
            ],

            'Invalid config – missing ID' => [
                ['type' => 'basic', 'requestPath' => 'foo/bar', 'responseCode' => 301],
                null,
            ],

            'Invalid config – missing type' => [
                ['id' => 123, 'requestPath' => 'foo/bar', 'responseCode' => 301],
                null,
            ],

            'Invalid config – missing request path' => [
                ['id' => 123, 'type' => 'basic', 'responseCode' => 301],
                null,
            ],

            'Invalid config – missing response code' => [
                ['id' => 123, 'type' => 'basic', 'requestPath' => 'foo/bar'],
                null,
            ],

            'Valid config – basic' => [
                [
                    'id' => 123,
                    'type' => 'basic',
                    'requestPath' => 'foo/bar',
                    'requestHosts' => serialize(['domain1.tld', 'domain2.tld']),
                    'requestRequirements' => serialize([
                        ['key' => 'foo', 'value' => '\d+'],
                        ['key' => 'bar', 'value' => '\s+'],
                    ]),
                    'responseUri' => 'foo/baz',
                    'responseCode' => 301,
                ],
                [
                    'getIdentifier' => '123',
                    'getRequestPath' => 'foo/bar',
                    'getRequestHosts' => ['domain1.tld', 'domain2.tld'],
                    'getRequestRequirements' => ['foo' => '\d+', 'bar' => '\s+'],
                    'getRequestCondition' => null,
                    'getResponseCode' => 301,
                    'getResponseUri' => 'foo/baz',
                ],
            ],

            'Valid config – expert' => [
                [
                    'id' => 123,
                    'type' => 'expert',
                    'requestPath' => 'foo/bar',
                    'requestHosts' => serialize(['domain1.tld', 'domain2.tld']),
                    'requestCondition' => "request.query.has('foobar')",
                    'responseUri' => 'foo/baz',
                    'responseCode' => 301,
                ],
                [
                    'getIdentifier' => '123',
                    'getRequestPath' => 'foo/bar',
                    'getRequestHosts' => ['domain1.tld', 'domain2.tld'],
                    'getRequestRequirements' => [],
                    'getRequestCondition' => "request.query.has('foobar')",
                    'getResponseCode' => 301,
                    'getResponseUri' => 'foo/baz',
                ],
            ],
        ];
    }

    public function testFindAll(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'id' => 123,
                    'type' => 'basic',
                    'requestPath' => 'foo/bar',
                    'responseUri' => 'foo/baz',
                    'responseCode' => 301,
                ],
                [
                    'id' => 456,
                    'type' => 'expert',
                    'requestPath' => 'foo/bar',
                    'responseUri' => 'foo/baz',
                    'responseCode' => 301,
                ],
            ])
        ;

        $provider = new DatabaseConfigProvider($connection);

        $this->assertCount(2, $provider->findAll());
    }

    public function testFindAllNoRecords(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $provider = new DatabaseConfigProvider($connection);

        $this->assertCount(0, $provider->findAll());
    }

    /**
     * @dataProvider connectionExceptionDataProvider
     */
    public function testConnectionException($method, $connMethod, $exception, $expected): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method($connMethod)
            ->willThrowException($exception)
        ;

        $provider = new DatabaseConfigProvider($connection);

        if (\is_array($expected) && isset($expected['exception'])) {
            $this->expectException($expected['exception']);
        }

        $this->assertSame($expected, $provider->$method('foobar'));
    }

    public function connectionExceptionDataProvider(): iterable
    {
        $pdoException = $this->createMock(\PDOException::class);
        $connectionException = $this->createMock(ConnectionException::class);
        $invalidFieldNameException = $this->createMock(InvalidFieldNameException::class);
        $tableNotFoundException = $this->createMock(TableNotFoundException::class);
        $runtimeException = $this->createMock(\RuntimeException::class);

        return [
            // find()
            'Find - PDO exception' => [
                'find', 'fetchAssociative', $pdoException, ['exception' => TemporarilyUnavailableConfigProviderException::class],
            ],
            'Find - Connection exception' => [
                'find', 'fetchAssociative', $connectionException, ['exception' => TemporarilyUnavailableConfigProviderException::class],
            ],
            'Find – Table exception' => [
                'find', 'fetchAssociative', $tableNotFoundException, ['exception' => TemporarilyUnavailableConfigProviderException::class],
            ],
            'Find – Invalid field name exception' => [
                'find', 'fetchAssociative', $invalidFieldNameException, ['exception' => TemporarilyUnavailableConfigProviderException::class],
            ],
            'Find – Runtime exception (uncaught)' => [
                'find', 'fetchAssociative', $runtimeException, ['exception' => \RuntimeException::class],
            ],

            // findAll()
            'Find all - PDO exception' => [
                'findAll', 'fetchAllAssociative', $pdoException, [],
            ],
            'Find all - Connection exception' => [
                'findAll', 'fetchAllAssociative', $connectionException, [],
            ],
            'Find all - Table exception' => [
                'findAll', 'fetchAllAssociative', $tableNotFoundException, [],
            ],
            'Find all - Invalid field name exception' => [
                'findAll', 'fetchAllAssociative', $invalidFieldNameException, [],
            ],
            'Find all - Runtime exception (uncaught)' => [
                'findAll', 'fetchAllAssociative', $runtimeException, ['exception' => \RuntimeException::class],
            ],
        ];
    }
}
