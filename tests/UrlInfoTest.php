<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 20.08.20 02:39:44
 */

declare(strict_types = 1);
namespace dicr\tests;

use dicr\helper\Url;
use dicr\http\UrlInfo;
use PHPUnit\Framework\TestCase;

/**
 * UrlInfo Test
 */
class UrlInfoTest extends TestCase
{
    /** @var string[] */
    public const TEST_NORMALIZE_HOST = [
        '' => '',
        'site.ru' => 'site.ru',
        '//site.ru' => 'site.ru',
        'ftp://site.ru' => 'site.ru',
        'xn--80aswg.xn--p1ai/' => 'сайт.рф',
        'http://xn--80aswg.xn--p1ai' => 'сайт.рф',
        'site.ru?test=1' => 'site.ru',
        'site.ru/path/to' => 'site.ru'
    ];

    /**
     * Test UrlInfo::normalizeHost
     *
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function testNormalizeHost()
    {
        foreach (self::TEST_NORMALIZE_HOST as $dom => $res) {
            self::assertSame($res, Url::normalizeHost($dom));
        }
    }

    /** @var string[] */
    public const TEST_NORMALIZE_PATH = [
        '' => '',
        '/' => '/',
        '//./' => '/',
        './/' => '/',
        '../../' => '../../',
        '/../..' => '/',
        '/../../' => '/',
        '/../../../path' => '/path',
        '/path/./../' => '/',
        '/path' => '/path',
        'path/' => 'path/',
        'path/to/test' => 'path/to/test',
        'path/./' => 'path/',
    ];

    /**
     * Test UrlInfo::normalizePath
     *
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function testNormalizePath()
    {
        foreach (self::TEST_NORMALIZE_PATH as $path => $res) {
            self::assertSame($res, Url::normalizePath($path));
        }
    }

    /** @var array */
    public const TEST_NORMALIZE_QUERY = [
        '' => [],
        '?' => [],
        '&' => [],
        'a' => ['a' => ''],
        'a=' => ['a' => ''],
        'a[3]=5' => ['a' => [3 => 5]],
        '?a=1&a=2' => ['a' => 2],
        'a[]=1&a[]=2' => ['a' => [1, 2]],
        'b=1&a=2' => ['a' => 2, 'b' => 1],
        '?&&&c=3' => ['c' => 3]
    ];

    /**
     * Test UrlInfo::normalizeQuery
     *
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function testNormalizeQuery()
    {
        foreach (self::TEST_NORMALIZE_QUERY as $src => $res) {
            self::assertEquals($res, Url::normalizeQuery($src), 'src: ' . $src);
        }
    }

    /** @var array */
    public const TEST_SUBDOMAIN = [
        ['site.ru', 'site.ru', ''],
        ['site.ru', 'test.site.u', null],
        ['test.site.ru', 'site.ru', 'test'],
        ['test.site.ru', 'site2.ru', null],
    ];

    /**
     * Тест определения поддомена
     */
    public function testSubdomain()
    {
        $urlInfo = new UrlInfo();
        foreach (self::TEST_SUBDOMAIN as [$domain, $parent, $result]) {
            $urlInfo->host = $domain;
            echo 'Testing: ' . $domain . "\m";
            self::assertSame($result, $urlInfo->getSubdomain($parent), $domain . '|' . $parent);
        }
    }

    /** @var array[] */
    public const TEST_SAME_SITE = [
        ['mailto:test@site.ru', '//test@site.ru', false],
        ['//test@site.ru', 'mailto:test@site.ru', false],
        ['//test@site.ru', '//@site.ru', false],
        ['/path', '/path/', true],
        ['//site.ru/path', '/path/', true],
        ['//site.ru', '/', true],
        ['//site.ru', '//site.ru:80', false],
        ['//site.ru:443', 'https://site.ru', false],
        ['//site.ru:80', '//site.ru:81', false],
        ['https://site.ru', '/', true],
        ['https://site.ru', '//site.ru', true],
        ['https://site.ru', 'http://site.ru', false],
        ['//user@site.ru', '//site.ru', false],
        ['//user@site.ru', '//user@site.ru', true],
        ['//user:pass@site.ru', '//user@site.ru', false],
        ['//user:pass@site.ru', '//user:pass@site.ru', true],
        ['//user:pass@site.ru', '//user:pass@site.ru:80', false],
        ['http://user:pass@site.ru', '//user:pass@site.ru:80', false],
        ['https://user:pass@site.ru:83', '/page', true],
        ['//site.ru', '//test.site.ru', true],
        ['//site.ru', '//login@test.site.ru', false],
        ['//site.ru', '//test.site.ru:23', false],
        ['//test1.site.ru', '//test2.site.ru', false],
        ['https://site.ru', '//test.site.ru', true],
    ];

    /**
     * Тестирование функции sameSite
     */
    public function testSameSite()
    {
        /*
        $u1 = new UrlInfo('https://site.ru');
        $u2 = new UrlInfo('//site.ru');
        var_dump($u1->isSameSite($u2)); exit;
        */

        foreach (self::TEST_SAME_SITE as [$url1, $url2, $res]) {
            $urlInfo1 = new UrlInfo($url1);
            $urlInfo2 = new UrlInfo($url2);

            self::assertSame($res, $urlInfo1->isSameSite($urlInfo2, [
                'subdoms' => true
            ]), $url1 . '|' . $url2);
        }
    }

    /** @var string[] */
    public const TEST_ABSOLUTE = [
        // полная лесенка
        'http://l1:w1@h1.com:81/p1?q1=v1&q11=v11#f1' => [
            '' => 'http://l1:w1@h1.com:81/p1?q1=v1&q11=v11#f1',
            '#f2' => 'http://l1:w1@h1.com:81/p1?q1=v1&q11=v11#f2',
            '?q2=v2' => 'http://l1:w1@h1.com:81/p1?q2=v2',
            '?q2=v2#f2' => 'http://l1:w1@h1.com:81/p1?q2=v2#f2',
            'p2' => 'http://l1:w1@h1.com:81/p2',
            '/p2' => 'http://l1:w1@h1.com:81/p2',
            '/p2/' => 'http://l1:w1@h1.com:81/p2/',
            '/p2?q2=v2' => 'http://l1:w1@h1.com:81/p2?q2=v2',
            '/p2?q2=v2#f2' => 'http://l1:w1@h1.com:81/p2?q2=v2#f2',
            '//h2.com' => 'http://h2.com',
            '//h2.com/' => 'http://h2.com',
            '//h2.com/p2' => 'http://h2.com/p2',
            '//h2.com/p2?q2=v2' => 'http://h2.com/p2?q2=v2',
            '//h2.com/p2?q2=v2#f2' => 'http://h2.com/p2?q2=v2#f2',
            '//h2.com:88' => 'http://h2.com:88',
            '//h2.com:88/p2' => 'http://h2.com:88/p2',
            '//h2.com:88/p2?q2=v2' => 'http://h2.com:88/p2?q2=v2',
            '//h2.com:88/p2?q2=v2#f2' => 'http://h2.com:88/p2?q2=v2#f2',
            '//l2:w2@h2.com' => 'http://l2:w2@h2.com',
            '//l2:w2@h2.com/p2' => 'http://l2:w2@h2.com/p2',
            '//l2:w2@h2.com:88' => 'http://l2:w2@h2.com:88',
            '//l2:w2@h2.com:88/p2' => 'http://l2:w2@h2.com:88/p2',
            'https://h2.com' => 'https://h2.com',
            'https://h2.com/p2' => 'https://h2.com/p2',
            'https://h2.com/p2?q2=v2' => 'https://h2.com/p2?q2=v2',
            'https://l2:w2@h2.com' => 'https://l2:w2@h2.com',
            'https://l2:w2@h2.com:88' => 'https://l2:w2@h2.com:88',
            'https://l2:w2@h2.com:88/p2' => 'https://l2:w2@h2.com:88/p2'
        ],

        // порты
        'https://site.com' => [
            '//site2.com:443' => 'https://site2.com',
            '//site2.com:444' => 'https://site2.com:444',
            '//site2.com:80' => 'https://site2.com:80'
        ],

        // пути
        'http://s.com' => [
            '' => 'http://s.com',
            '/' => 'http://s.com',
            'p2' => 'http://s.com/p2',
            'p2/' => 'http://s.com/p2/',
            '/p2' => 'http://s.com/p2',
            '/p2/' => 'http://s.com/p2/',
        ],

        // пути2
        'http://s.com/p' => [
            '' => 'http://s.com/p',
            '/' => 'http://s.com',
            'p2' => 'http://s.com/p2',
            'p2/' => 'http://s.com/p2/',
            '/p2' => 'http://s.com/p2',
            '/p2/' => 'http://s.com/p2/'
        ],

        // пути3
        'http://s.com/p/' => [
            '' => 'http://s.com/p/',
            '/' => 'http://s.com',
            'p2' => 'http://s.com/p/p2',
            'p2/' => 'http://s.com/p/p2/',
            '/p2' => 'http://s.com/p2',
            '/p2/' => 'http://s.com/p2/'
        ],

        // короткий IDN домен с минимальным количеством параметров
        'http://сайт.рф' => [
            '' => 'http://сайт.рф',
            '#test' => 'http://сайт.рф#test',
            '?b=c&a=b' => 'http://сайт.рф?a=b&b=c',
            '/path/' => 'http://сайт.рф/path/',
            '../path' => 'http://сайт.рф/path',
            '../../' => 'http://сайт.рф',
            '..' => 'http://сайт.рф',
        ],

        /*
        // полный basePath с файлом в пути
        'http://site.ru/path/to.php?prod=1#link' => [
            '' => 'http://site.ru/path/to.php?prod=1#link',        // пустая
            '#qwe' => 'http://site.ru/path/to.php?prod=1#qwe',    // fragment
            '?a=b' => 'http://site.ru/path/to.php?a=b',            // query
            '/new/index' => 'http://site.ru/new/index',            // absolute path
            'new/index#zzz' => 'http://site.ru/path/new/index#zzz',    // relative path
            '//site2.ru?a=b' => 'http://site2.ru?a=b',            // host
            'https://site.ru/' => 'https://site.ru',            // scheme
            '//site2.ru' => 'http://site2.ru'                    // other host with same scheme
        ],

        // basePath с директорией в конце
        'http://site.ru/path/to/' => [
            '/new/index' => 'http://site.ru/new/index',        // absolute path
            'new/index#zzz' => 'http://site.ru/path/to/new/index#zzz',    // relative path
            './new/path' => 'http://site.ru/path/to/new/path',
            '../new/path' => 'http://site.ru/path/new/path',
            '../../new/path' => 'http://site.ru/new/path',
            '../../../new/path' => 'http://site.ru/new/path',
            '../../../new/path/' => 'http://site.ru/new/path/',
            '../' => 'http://site.ru/path/',
            '..' => 'http://site.ru/path'
        ],

        // еще тесты
        'https://site.ru/anketirovali/' => [
            '' => 'https://site.ru/anketirovali/',
            '/' => 'https://site.ru',
            '#' => 'https://site.ru/anketirovali/',
            '#a' => 'https://site.ru/anketirovali/#a',
            'tel:+7 342 2 111 563' => 'tel:+7 342 2 111 563',
            'mailto:info@site.ru' => 'mailto:info@site.ru',
            '//s.w.org' => 'https://s.w.org',
            'https://site.ru/wp-json/' => 'https://site.ru/wp-json/',
            'https://site.ru/?p=136' => 'https://site.ru?p=136',
            '/seo/' => 'https://site.ru/seo/'
        ],

        // проблемы при работе с URL
        'https://grad-snab.ru' => [
            'grad-snab.ru' => 'https://grad-snab.ru/grad-snab.ru',
            '//grad-snab.ru' => 'https://grad-snab.ru'
        ]
        */
    ];

    /**
     * Test UrlInfo::toAbsolute
     */
    public function testAbsolute()
    {
        /*
        $u1 = new UrlInfo('http://site.ru/path/to.php?prod=1#link');
        $u2 = new UrlInfo('//site2.ru?a=b');
        //var_dump($u2->toAbsolute($u1)->toString()); exit;
        */

        foreach (self::TEST_ABSOLUTE as $base => $tests) {
            $baseUrl = new UrlInfo($base);
            foreach ($tests as $src => $res) {
                $srcUrl = new UrlInfo($src);
                $resUrl = $srcUrl->toAbsolute($baseUrl);
                self::assertSame($res, $resUrl->toString(), 'BASE: ' . $base . '; SRC: ' . $src);
            }
        }
    }

    /** @var array non-http links */
    public const TEST_NONHTTP = [
        'javascript:' => 'javascript:',
        'javascript:void(0)' => 'javascript:void(0)',
        'mailto:' => 'mailto:',
        'mailto:test@site.ru' => 'mailto:test@site.ru',
        'tel:' => 'tel:',
        'tel:123-45-67' => 'tel:123-45-67'
    ];

    /**
     * Тестирует ссылки с не http-протоколом
     *
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function testNonHttp()
    {
        foreach (self::TEST_NONHTTP as $src => $dst) {
            $url = UrlInfo::fromString($src);
            self::assertSame($dst, (string)$url, $src);
        }
    }
}
