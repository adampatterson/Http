<?php

namespace Http\Tests;


use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Psr7\Response;
use Http\Actions\HttpRequest;
use Http\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use ReflectionProperty;

#[CoversClass(Http::class)]
#[CoversClass(HttpRequest::class)]
final class CookieTest extends TestCase
{
    #[Test]
    public function supports_array_cookies_and_converts_them_to_a_cookie_jar(): void
    {
        $this->mockResponse([
            new Response(200, []),
        ]);

        $response = Http::withCookies(['session_id' => 'abc123'], 'example.com')
            ->get('https://example.com/cookies');

        $cookies = $this->container[0]['options']['cookies'];

        $this->assertInstanceOf(CookieJarInterface::class, $cookies);
        $this->assertEquals('abc123', $cookies->getCookieByName('session_id')?->getValue());
        $this->assertSame($cookies, $response->cookies());
    }

    #[Test]
    public function supports_an_existing_cookie_jar_instance(): void
    {
        $this->mockResponse([
            new Response(200, []),
        ]);

        $cookieJar = new CookieJar();
        $cookieJar->setCookie(new SetCookie([
            'Name'   => 'session_id',
            'Value'  => 'abc123',
            'Domain' => 'example.com',
        ]));

        $response = Http::withCookieJar($cookieJar)
            ->get('https://example.com/cookies');

        $this->assertSame($cookieJar, $this->container[0]['options']['cookies']);
        $this->assertSame($cookieJar, $response->cookies());
    }

    #[Test]
    public function reuses_global_cookie_jar_across_facade_calls(): void
    {
        $this->mockResponse([
            new Response(200, []),
            new Response(200, []),
        ]);

        $cookieJar = CookieJar::fromArray([
            'session_id' => 'abc123',
        ], 'example.com');

        Http::useCookieJar($cookieJar);

        Http::get('https://example.com/first');
        Http::get('https://example.com/second');

        $this->assertSame($cookieJar, Http::cookieJar());
        $this->assertSame($cookieJar, $this->container[0]['options']['cookies']);
        $this->assertSame($cookieJar, $this->container[1]['options']['cookies']);
    }

    #[Test]
    public function per_request_cookie_jar_overrides_global_cookie_jar(): void
    {
        $this->mockResponse([
            new Response(200, []),
            new Response(200, []),
        ]);

        $globalCookieJar = CookieJar::fromArray([
            'global_session' => 'global',
        ], 'example.com');

        $requestCookieJar = CookieJar::fromArray([
            'request_session' => 'request',
        ], 'example.com');

        Http::useCookieJar($globalCookieJar);

        Http::withCookieJar($requestCookieJar)
            ->get('https://example.com/first');

        Http::get('https://example.com/second');

        $this->assertSame($requestCookieJar, $this->container[0]['options']['cookies']);
        $this->assertSame($globalCookieJar, $this->container[1]['options']['cookies']);
    }

    #[Test]
    public function with_cookie_jar_false_disables_cookies_for_only_the_current_request(): void
    {
        $this->mockResponse([
            new Response(200, []),
            new Response(200, []),
        ]);

        $globalCookieJar = CookieJar::fromArray([
            'session_id' => 'abc123',
        ], 'example.com');

        Http::useCookieJar($globalCookieJar);

        Http::withCookieJar(false)
            ->get('https://example.com/first');

        Http::get('https://example.com/second');

        $this->assertFalse($this->container[0]['options']['cookies']);
        $this->assertSame($globalCookieJar, $this->container[1]['options']['cookies']);
    }

    #[Test]
    public function clear_cookie_jar_removes_global_cookie_state(): void
    {
        $this->mockResponse([
            new Response(200, []),
        ]);

        Http::useCookieJar(true);
        $this->assertInstanceOf(CookieJarInterface::class, Http::cookieJar());

        Http::clearCookieJar();

        $this->assertNull(Http::cookieJar());

        Http::get('https://example.com/no-cookies');

        $requestOptions = $this->container[0]['options'];
        $hasCookiesOption = array_key_exists('cookies', $requestOptions);
        $this->assertTrue(! $hasCookiesOption || $requestOptions['cookies'] === false);
    }

    #[Test]
    public function with_cookies_overrides_global_cookie_jar_for_the_current_request(): void
    {
        $this->mockResponse([
            new Response(200, []),
            new Response(200, []),
        ]);

        $globalCookieJar = CookieJar::fromArray([
            'global_session' => 'global',
        ], 'example.com');

        Http::useCookieJar($globalCookieJar);

        Http::withCookies([
            'request_session' => 'request',
        ], 'example.com')->get('https://example.com/override');

        Http::get('https://example.com/fallback');

        $overrideCookies = $this->container[0]['options']['cookies'];
        $fallbackCookies = $this->container[1]['options']['cookies'];

        $this->assertInstanceOf(CookieJarInterface::class, $overrideCookies);
        $this->assertEquals('request', $overrideCookies->getCookieByName('request_session')?->getValue());
        $this->assertSame($globalCookieJar, $fallbackCookies);
    }

    #[Test]
    public function it_resolves_cookie_arrays_to_localhost_for_hostless_urls(): void
    {
        $request = new HttpRequest(new Client());

        $cookiesProperty = new ReflectionProperty(HttpRequest::class, 'cookies');
        $cookiesProperty->setAccessible(true);
        $cookiesProperty->setValue($request, ['session_id' => 'abc123']);

        $cookieDomainProperty = new ReflectionProperty(HttpRequest::class, 'cookieDomain');
        $cookieDomainProperty->setAccessible(true);
        $cookieDomainProperty->setValue($request, null);

        $resolver = new ReflectionMethod(HttpRequest::class, 'resolveCookiesOption');
        $resolver->setAccessible(true);

        $resolved = $resolver->invoke($request, '/relative-path');

        $this->assertInstanceOf(CookieJarInterface::class, $resolved);
        $this->assertSame('localhost', $resolved->toArray()[0]['Domain']);
    }

    #[Test]
    public function it_returns_null_when_cookie_configuration_is_invalid(): void
    {
        $request = new HttpRequest(new Client());

        $cookiesProperty = new ReflectionProperty(HttpRequest::class, 'cookies');
        $cookiesProperty->setAccessible(true);
        $cookiesProperty->setValue($request, 'invalid-cookie-value');

        $resolver = new ReflectionMethod(HttpRequest::class, 'resolveCookiesOption');
        $resolver->setAccessible(true);

        $resolved = $resolver->invoke($request, 'https://example.com');

        $this->assertNull($resolved);
    }
}
