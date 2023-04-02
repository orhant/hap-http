<?php
/*
 * @copyright 2019-2021 Orhant http://hap.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 19.04.21 17:02:14
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
     * @noinspection PhpUnitMissingTargetForTestInspection
     */
    public function testCompression(): void
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
