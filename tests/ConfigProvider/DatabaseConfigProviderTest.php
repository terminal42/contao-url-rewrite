<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\ConfigProvider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Terminal42\UrlRewriteBundle\ConfigProvider\DatabaseConfigProvider;
use Terminal42\UrlRewriteBundle\Exception\TemporarilyUnavailableConfigProviderException;

final class DatabaseConfigProviderTest extends TestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf(DatabaseConfigProvider::class, new DatabaseConfigProvider($this->createStub(Connection::class)));
    }

    #[DataProvider('findDataProvider')]
    public function testFind($row, $expected): void
    {
        $connection = $this->createStub(Connection::class);
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
        yield 'Row not found' => [
            false,
            null,
        ];

        yield 'Invalid config – missing ID' => [
            ['requestPath' => 'foo/bar', 'responseCode' => 301],
            null,
        ];

        yield 'Invalid config – missing request path' => [
            ['id' => 123, 'responseCode' => 301],
            null,
        ];

        yield 'Invalid config – missing response code' => [
            ['id' => 123, 'requestPath' => 'foo/bar'],
            null,
        ];

        yield 'Valid config – basic' => [
            [
                'id' => 123,
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
        ];

        yield 'Valid config – expert' => [
            [
                'id' => 123,
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
        ];
    }

    public function testFindAll(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'id' => 123,
                    'requestPath' => 'foo/bar',
                    'responseUri' => 'foo/baz',
                    'responseCode' => 301,
                ],
            ])
        ;

        $provider = new DatabaseConfigProvider($connection);

        $this->assertCount(1, $provider->findAll());
    }

    public function testFindAllNoRecords(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $provider = new DatabaseConfigProvider($connection);

        $this->assertCount(0, $provider->findAll());
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     */
    #[DataProvider('connectionExceptionDataProvider')]
    public function testConnectionException($method, $connMethod, $exceptionClass, $expected): void
    {
        $exception = (new \ReflectionClass($exceptionClass))->newInstanceWithoutConstructor();

        $connection = $this->createStub(Connection::class);
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

    public static function connectionExceptionDataProvider(): iterable
    {
        yield 'Find - PDO exception' => [
            'find', 'fetchAssociative', \PDOException::class, ['exception' => TemporarilyUnavailableConfigProviderException::class],
        ];

        yield 'Find - Connection exception' => [
            'find', 'fetchAssociative', ConnectionException::class, ['exception' => TemporarilyUnavailableConfigProviderException::class],
        ];

        yield 'Find – Table exception' => [
            'find', 'fetchAssociative', TableNotFoundException::class, ['exception' => TemporarilyUnavailableConfigProviderException::class],
        ];

        yield 'Find – Invalid field name exception' => [
            'find', 'fetchAssociative', InvalidFieldNameException::class, ['exception' => TemporarilyUnavailableConfigProviderException::class],
        ];

        yield 'Find – Runtime exception (uncaught)' => [
            'find', 'fetchAssociative', \RuntimeException::class, ['exception' => \RuntimeException::class],
        ];

        // findAll()
        yield 'Find all - PDO exception' => [
            'findAll', 'fetchAllAssociative', \PDOException::class, [],
        ];

        yield 'Find all - Connection exception' => [
            'findAll', 'fetchAllAssociative', ConnectionException::class, [],
        ];

        yield 'Find all - Table exception' => [
            'findAll', 'fetchAllAssociative', TableNotFoundException::class, [],
        ];

        yield 'Find all - Invalid field name exception' => [
            'findAll', 'fetchAllAssociative', InvalidFieldNameException::class, [],
        ];

        yield 'Find all - Runtime exception (uncaught)' => [
            'findAll', 'fetchAllAssociative', \RuntimeException::class, ['exception' => \RuntimeException::class],
        ];
    }
}
