<?php
/*
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 19.03.21 06:11:04
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
     * @noinspection PhpUnitMissingTargetForTestInspection
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
