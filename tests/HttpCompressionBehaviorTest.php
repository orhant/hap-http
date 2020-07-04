<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.07.20 12:16:14
 */

declare(strict_types = 1);

namespace dicr\tests;

use dicr\http\PersistentCookiesBehavior;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\caching\TagDependency;
use yii\httpclient\Client;
use yii\httpclient\Exception;

/**
 * Class HttpCompressionBehaviorTest
 */
class HttpCompressionBehaviorTest extends TestCase
{
    /**
     * Проверка паузы запроса.
     *
     * @throws Exception
     */
    public function testCompression()
    {
        $client = new Client([
            'as cookies' => [
                'class' => PersistentCookiesBehavior::class,
                'cache' => 'cache',
            ]
        ]);

        TagDependency::invalidate(Yii::$app->cache, 'www.google.com');
        $request = $client->get('https://www.google.com/');
        $request->send();
    }
}
