<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 30.10.20 20:39:41
 */

declare(strict_types = 1);

namespace dicr\tests;

use dicr\http\HttpCompressionBehavior;
use PHPUnit\Framework\TestCase;
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
    public function testCompression() : void
    {
        $client = new Client([
            'as compression' => [
                'class' => HttpCompressionBehavior::class
            ]
        ]);

        $request = $client->get('https://www.google.com/');
        $response = $request->send();

        self::assertSame('gzip', $response->headers->get('content-encoding'));
    }
}
