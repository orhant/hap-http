<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 30.10.20 20:41:00
 */

declare(strict_types = 1);

namespace dicr\tests;

use dicr\http\RequestDelayBehavior;
use PHPUnit\Framework\TestCase;
use yii\httpclient\Client;
use yii\httpclient\Exception;

/**
 * Class RequestDelayBehaviorTest
 */
class RequestDelayBehaviorTest extends TestCase
{
    /**
     * Проверка паузы запроса.
     *
     * @throws Exception
     */
    public function testDelay() : void
    {
        $delay = 2.0;

        $client = new Client([
            'as delay' => [
                'class' => RequestDelayBehavior::class,
                'delayMin' => $delay,
                'delayMax' => $delay
            ]
        ]);

        $ts1 = microtime(true);

        $request = $client->get('https://www.google.com/');
        $response = $request->send();

        $ts2 = microtime(true);

        self::assertTrue($response->isOk);
        self::assertTrue($ts2 - $ts1 > $delay);
    }
}
