<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 14.02.20 18:26:51
 */

declare(strict_types = 1);

namespace dicr\tests;

use dicr\http\PersistentCookiesBehavior;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\caching\ApcCache;
use yii\caching\TagDependency;
use yii\httpclient\Client;

/**
 * Class HttpCompressionBehaviorTest
 *
 * @package dicr\tests
 */
class HttpCompressionBehaviorTest extends TestCase
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
    }
}
