<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 08.02.20 07:13:43
 */

declare(strict_types = 1);

namespace dicr\tests;

use dicr\http\PersistentCookiesBehavior;
use dicr\http\RequestDelayBehavior;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\caching\ApcCache;
use yii\caching\TagDependency;
use yii\httpclient\Client;

/**
 * Class HttpCompressionBehaviortest
 *
 * @package dicr\tests
 */
class HttpCompressionBehaviortest extends TestCase
{
    /**
     * Проверка паузы запроса.
     *
     * @throws \yii\httpclient\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function testCompression()
    {
        Yii::$app->set('cache', [
            'class' => ApcCache::class,
        ]);

        $client = new Client([
            'as cookies' => [
                'class' => PersistentCookiesBehavior::class,
                'cache' => 'cache',
            ]
        ]);

        TagDependency::invalidate(Yii::$app->cache, 'www.google.com');
        $request = $client->get('https://www.google.com/');
        $response = $request->send();

        var_dump($request->cookies, $response->cookies);
        exit;

    }
}
