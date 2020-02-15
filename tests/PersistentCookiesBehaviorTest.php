<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 14.02.20 18:24:38
 */

declare(strict_types = 1);

namespace dicr\tests;

use dicr\http\PersistentCookiesBehavior;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\caching\FileCache;
use yii\caching\TagDependency;
use yii\httpclient\Client;
use yii\web\Cookie;
use yii\web\CookieCollection;

/**
 * Class PersistentCookiesBehaviorTest
 *
 * @package dicr\tests
 */
class PersistentCookiesBehaviorTest extends TestCase
{
    /**
     * @throws \yii\base\InvalidConfigException
     */
    public static function setUpBeforeClass()
    {
        Yii::$app->set('cache', [
            'class' => FileCache::class,
        ]);
    }

    /**
     * Тест определения домена из запроса.
     */
    public function testDomain()
    {
        $client = new Client();
        $request = $client->get('https://www.google.com');
        self::assertSame('www.google.com', PersistentCookiesBehavior::domain($request));
    }

    /**
     * Тест загрузки/сохранения куков.
     */
    public function testLoadCookies()
    {
        $testData = new CookieCollection([
            'name1' => new Cookie(['name' => 'name1', 'value' => 'value1']),
            'name2' => new Cookie(['name' => 'name2', 'value' => 'value2'])
        ]);

        $domain = 'test.com';

        $behavior = new PersistentCookiesBehavior([
            'store' => 'cache'
        ]);

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
     * @throws \yii\httpclient\Exception
     */
    public function testRequest()
    {
        $client = new Client([
            'as cookies' => [
                'class' => PersistentCookiesBehavior::class,
                'store' => 'cache',
            ]
        ]);

        // очищаем куки из кэша
        TagDependency::invalidate(Yii::$app->cache, 'www.google.com');

        // делаем первый запрос
        $request = $client->get('https://www.google.com/');
        $response = $request->send();
        self::assertSame(0, $request->cookies->count);
        self::assertGreaterThan(0, $response->cookies->count);

        // делаем второй запрос
        $request = $client->get('https://www.google.com/');
        $response = $request->send();

        self::assertGreaterThan(0, $request->cookies->count);
        self::assertSame(0, $response->cookies->count);
    }
}
