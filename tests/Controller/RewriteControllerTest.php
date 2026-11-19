<?php

declare(strict_types=1);

namespace Terminal42\UrlRewriteBundle\Tests\Controller;

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\InsertTag\ParsedSequence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Terminal42\UrlRewriteBundle\ConfigProvider\ConfigProviderInterface;
use Terminal42\UrlRewriteBundle\Controller\RewriteController;
use Terminal42\UrlRewriteBundle\Exception\TemporarilyUnavailableConfigProviderException;
use Terminal42\UrlRewriteBundle\RewriteConfig;

final class RewriteControllerTest extends TestCase
{
    public function testIndexActionNoUrlRewriteAttribute(): void
    {
        $controller = new RewriteController($this->mockConfigProvider(), $this->mockInsertTagParser(), $this->createStub(ExpressionLanguage::class));
        $request = $this->mockRequest();

        $this->expectException(RouteNotFoundException::class);

        $controller->indexAction($request);
    }

    public function testIndexActionNoUrlRewriteRecord(): void
    {
        $provider = $this->mockConfigProvider();
        $controller = new RewriteController($provider, $this->mockInsertTagParser(), $this->createStub(ExpressionLanguage::class));
        $request = $this->mockRequest(1);

        $this->expectException(RouteNotFoundException::class);

        $controller->indexAction($request);
    }

    #[DataProvider('indexActionRedirectDataProvider')]
    public function testIndexActionRedirect($provided, $expected): void
    {
        $provider = $this->mockConfigProvider($provided[0]);
        $controller = new RewriteController($provider, $this->mockInsertTagParser(), $this->createStub(ExpressionLanguage::class));
        $request = $this->mockRequest(1, $provided[1], $provided[2]);
        $response = $controller->indexAction($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($expected[0], $response->getTargetUrl());
        $this->assertSame($expected[0], $response->headers->get('Location'));
        $this->assertSame($expected[1], $response->getStatusCode());
    }

    public static function indexActionRedirectDataProvider(): iterable
    {
        $config1 = new RewriteConfig('1', 'foobar');
        $config1->setResponseUri('{{link_url::{bar}|absolute}}/foo///{baz}/{quux}');

        $config2 = new RewriteConfig('2', 'foobar', 302);
        $config2->setResponseUri('foo///{baz}/{quux}');

        return [
            'Insert tags' => [
                [
                    $config1,
                    ['bar' => 1, 'baz' => 'bar'],
                    ['quux' => 'quuz'],
                ],
                [
                    'http://domain.tld/page.html/foo/bar/quuz',
                    301,
                ],
            ],
            'Absolute ' => [
                [
                    $config2,
                    ['baz' => 'bar'],
                    ['quux' => 'quuz'],
                ],
                [
                    'http://domain.tld/foo/bar/quuz',
                    302,
                ],
            ],
        ];
    }

    #[DataProvider('unsafeWildcardDataProvider')]
    public function testRejectsDecodedControlCharacters(string $encodedValue, bool $queryWildcard): void
    {
        $config = new RewriteConfig('1', 'foobar');
        $config->setResponseUri('target/{value}');

        $controller = new RewriteController($this->mockConfigProvider($config), $this->mockInsertTagParser(), $this->createStub(ExpressionLanguage::class));

        $request = Request::create('https://domain.tld/'.($queryWildcard ? '?value='.$encodedValue : $encodedValue));
        $request->attributes->set('_url_rewrite', 1);
        $request->attributes->set('_route_params', $queryWildcard ? [] : ['value' => rawurldecode(ltrim($request->getPathInfo(), '/'))]);

        $response = $controller->indexAction($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Bad Request', $response->getContent());
        $this->assertFalse($response->headers->has('Location'));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function unsafeWildcardDataProvider(): iterable
    {
        foreach (['LF' => '.%0a.%2f.%0a.%2fetc/passwd', 'CRLF' => 'foo%0d%0aLocation:%20https://example.org', 'CR' => 'foo%0dbar', 'NUL' => 'foo%00bar', 'TAB' => 'foo%09bar', 'DEL' => 'foo%7fbar'] as $name => $value) {
            yield $name.' in route wildcard' => [$value, false];
            yield $name.' in query wildcard' => [$value, true];
        }
    }

    /**
     * @param array<string, string> $route
     * @param array<string, string> $query
     */
    #[DataProvider('escapedUriDataProvider')]
    public function testRedirectEscaping(string $target, array $route, array $query, string $expected): void
    {
        $config = new RewriteConfig('1', 'foobar');
        $config->setResponseUri($target);

        $controller = new RewriteController($this->mockConfigProvider($config), $this->mockInsertTagParser(), $this->createStub(ExpressionLanguage::class));
        $response = $controller->indexAction($this->mockRequest(1, $route, $query));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($expected, $response->headers->get('Location'));
    }

    /**
     * @return iterable<string, array{string, array<string, string>, array<string, string>, string}>
     */
    public static function escapedUriDataProvider(): iterable
    {
        yield 'Unicode and spaces in route wildcard' => ['{path}', ['path' => 'über uns/東京'], [], 'http://domain.tld/%C3%BCber%20uns/%E6%9D%B1%E4%BA%AC'];
        yield 'Unicode and spaces in query wildcard' => ['search?q={term}', [], ['term' => 'Zürich Süd'], 'http://domain.tld/search?q=Z%C3%BCrich%20S%C3%BCd'];
        yield 'Configured Unicode path' => ['/über uns', [], [], 'http://domain.tld/%C3%BCber%20uns'];
        yield 'Existing escapes' => ['/a%20b/%2f/%25/%C3%BC?x=%26#f%20g', [], [], 'http://domain.tld/a%20b/%2f/%25/%C3%BC?x=%26#f%20g'];
        yield 'Escaped wildcard' => ['{path}', ['path' => 'a%20b/%2F/%250a'], [], 'http://domain.tld/a%20b/%2F/%250a'];
        yield 'Escaped controls stay escaped' => ['/foo%0a%0D%00', [], [], 'http://domain.tld/foo%0a%0D%00'];
        yield 'Literal percent' => ['{path}', ['path' => '100%/bad%2x'], [], 'http://domain.tld/100%25/bad%252x'];
        yield 'Delimiters and repeated wildcard' => ['{path}/{path}?x={query}#fragment', ['path' => 'a/b'], ['query' => 'one=two&three=four'], 'http://domain.tld/a/b/a/b?x=one=two&three=four#fragment'];
        yield 'Query overrides route wildcard' => ['{value}/{unknown}', ['value' => 'route'], ['value' => 'query'], 'http://domain.tld/query/%7Bunknown%7D'];
        yield 'Unused control characters' => ['target', ['unused' => "a\nb"], ['unused' => "a\r\nb"], 'http://domain.tld/target'];
        yield 'Query overrides invalid route wildcard' => ['{value}', ['value' => "a\nb"], ['value' => 'query'], 'http://domain.tld/query'];
        yield 'Absolute URI' => ['https://example.org:8443/{path}?a=b#c', ['path' => 'a b'], [], 'https://example.org:8443/a%20b?a=b#c'];
        yield 'Protocol relative URI' => ['//example.org/{path}', ['path' => 'a b'], [], '//example.org/a%20b'];
    }

    public function testPreservesQueryParameters(): void
    {
        $config = new RewriteConfig('1', 'foobar');
        $config->setResponseUri('target/{value}');
        $config->setKeepQueryParams(true);

        $controller = new RewriteController($this->mockConfigProvider($config), $this->mockInsertTagParser(), $this->createStub(ExpressionLanguage::class));
        $response = $controller->indexAction($this->mockRequest(1, [], ['value' => 'über uns', 'extra' => "a/b\r\nb"]));

        $this->assertSame('http://domain.tld/target/%C3%BCber%20uns?value=%C3%BCber+uns&extra=a%2Fb%0D%0Ab', $response->headers->get('Location'));
    }

    public function testRejectsControlCharactersFromInsertTags(): void
    {
        $config = new RewriteConfig('1', 'foobar');
        $config->setResponseUri('{{link_url::1}}');

        $parser = $this->createStub(InsertTagParser::class);
        $parser
            ->method('replaceInline')
            ->willReturn("https://example.org/foo\r\nbar")
        ;

        $controller = new RewriteController($this->mockConfigProvider($config), $parser, $this->createStub(ExpressionLanguage::class));
        $response = $controller->indexAction($this->mockRequest(1));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($response->headers->has('Location'));
    }

    public function testRejectsControlCharactersInConfiguredTarget(): void
    {
        $config = new RewriteConfig('1', 'foobar');
        $config->setResponseUri("https://example.org/foo\nbar");

        $controller = new RewriteController($this->mockConfigProvider($config), $this->mockInsertTagParser(), $this->createStub(ExpressionLanguage::class));
        $response = $controller->indexAction($this->mockRequest(1));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($response->headers->has('Location'));
    }

    public function testIndexActionGone(): void
    {
        $provider = $this->mockConfigProvider(new RewriteConfig('1', 'foobar', 410));
        $controller = new RewriteController($provider, $this->mockInsertTagParser(), $this->createStub(ExpressionLanguage::class));
        $request = $this->mockRequest(1);
        $response = $controller->indexAction($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(410, $response->getStatusCode());
        $this->assertSame('Gone', $response->getContent());
    }

    public function testIndexActionInternalServerError(): void
    {
        $provider = $this->mockConfigProvider(new RewriteConfig('1', 'foobar'));
        $controller = new RewriteController($provider, $this->mockInsertTagParser(), $this->createStub(ExpressionLanguage::class));
        $request = $this->mockRequest(1);
        $response = $controller->indexAction($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Internal Server Error', $response->getContent());
    }

    public function testIndexActionServiceUnavailable(): void
    {
        $provider = $this->createStub(ConfigProviderInterface::class);
        $provider
            ->method('find')
            ->willThrowException(new TemporarilyUnavailableConfigProviderException())
        ;

        $controller = new RewriteController($provider, $this->mockInsertTagParser(), $this->createStub(ExpressionLanguage::class));
        $request = $this->mockRequest(1);
        $response = $controller->indexAction($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('Service Unavailable', $response->getContent());
    }

    private function mockRequest(int|null $urlRewrite = null, array $routeParams = [], array $query = []): Request&Stub
    {
        $attributes = ['_route_params' => $routeParams];

        if (null !== $urlRewrite) {
            $attributes['_url_rewrite'] = $urlRewrite;
        }

        $request = $this
            ->getStubBuilder(Request::class)
            ->setConstructorArgs([
                $query,
                [],
                $attributes,
            ])
            ->onlyMethods(['getSchemeAndHttpHost', 'getBasePath'])
            ->getStub()
        ;
        $request
            ->method('getSchemeAndHttpHost')
            ->willReturn('http://domain.tld')
        ;
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        return $request;
    }

    private function mockConfigProvider(RewriteConfig|null $config = null): ConfigProviderInterface&Stub
    {
        $provider = $this->createStub(ConfigProviderInterface::class);
        $provider
            ->method('find')
            ->willReturn($config)
        ;

        return $provider;
    }

    private function mockInsertTagParser(): InsertTagParser&Stub
    {
        $provider = $this->createStub(InsertTagParser::class);
        $provider
            ->method('replaceInline')
            ->willReturnCallback(static fn (ParsedSequence|string $buffer): string => str_replace('{{link_url::1|absolute}}', 'http://domain.tld/page.html', $buffer))
        ;

        return $provider;
    }
}
