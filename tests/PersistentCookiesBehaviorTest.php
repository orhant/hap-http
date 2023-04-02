<?php
/*
 * @copyright 2019-2021 Orhant http://hap.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 19.04.21 17:02:14
 */

declare(strict_types = 1);

namespace dicr\tests;

use dicr\http\PersistentCookiesBehavior;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\caching\TagDependency;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\web\Cookie;
use yii\web\CookieCollection;

/**
 * Class PersistentCookiesBehaviorTest
 */
class PersistentCookiesBehaviorTest extends TestCase
{
    /**
     * Тест определения домена из запроса.
     */
    public function testDomain(): void
    {
        $client = new Client();
        $request = $client->get('https://www.google.com');
        self::assertSame('www.google.com', PersistentCookiesBehavior::domain($request));
    }

    /**
     * Тест загрузки/сохранения куков.
     */
    public function testLoadCookies(): void
    {
        $testData = new CookieCollection([
            'name1' => new Cookie(['name' => 'name1', 'value' => 'value1']),
            'name2' => new Cookie(['name' => 'name2', 'value' => 'value2'])
        ]);

        $domain = 'test.com';

        $behavior = new PersistentCookiesBehavior();

        // сохраняем пустое значение
        $behavior->saveCookies($domain, new CookieCollection());
        self::assertNull($behavior->loadCookies($domain));

        // сохраняем тестовые данные
        $behavior->saveCookies($domain, $testData);
        $cookies = $behavior->loadCookies($domain);
        self::assertEquals($testData, $cookies);
    }

    /**
     * Проверка паузы запроса.
     *
     * @throws Exception
     * @noinspection PhpUnitMissingTargetForTestInspection
     */
    public function testRequest(): void
    {
        $client = new Client([
            'as cookies' => [
                'class' => PersistentCookiesBehavior::class
            ]
        ]);

        // очищаем куки из кэша
        TagDependency::invalidate(Yii::$app->cache, 'www.google.com');

        // делаем первый запрос
        $request = $client->get('https://dicr.org/');
        $response = $request->send();

        // в запросе не должно быть куков
        self::assertSame(0, $request->cookies->count);

        // в ответе должны быть куки
        self::assertGreaterThan(0, $response->cookies->count);

        // запоминаем сколько куков нам прислали
        $cookiesCount = $response->cookies->count;

        // делаем второй запрос
        $request = $client->get('https://dicr.org/');
        $request->send();

        // в запросе должны быть предыдущие куки
        self::assertSame($cookiesCount, $request->cookies->count);
    }
}
