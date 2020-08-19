<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 19.08.20 23:41:21
 */

declare(strict_types = 1);
namespace dicr\http;

use Yii;
use yii\base\Behavior;
use yii\httpclient\Client;
use yii\httpclient\RequestEvent;

/**
 * HTTP-compression support for yii\httpclient\Client
 */
class HttpCompressionBehavior extends Behavior
{
    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Client::EVENT_BEFORE_SEND => '_beforeSend',
            Client::EVENT_AFTER_SEND => '_afterSend'
        ];
    }

    /**
     * Adjust request.
     *
     * @param RequestEvent $event
     * @noinspection PhpMethodNamingConventionInspection
     */
    public function _beforeSend(RequestEvent $event): void
    {
        // add accept-encoding header
        $headers = $event->request->headers;
        if (! $headers->has('accept-encoding')) {
            $headers->set('accept-encoding', 'gzip, deflate, compress');
        }
    }

    /**
     * Adjust response.
     *
     * @param RequestEvent $event
     * @noinspection PhpMethodNamingConventionInspection
     * @noinspection PhpUsageOfSilenceOperatorInspection
     */
    public function _afterSend(RequestEvent $event): void
    {
        $response = $event->response;
        $encoding = $response->headers->get('content-encoding');

        if ($encoding !== null) {
            $decoded = false;

            switch (strtolower($encoding)) {
                case 'deflate':
                    $decoded = @gzinflate($response->content);
                    break;

                case 'compress':
                    $decoded = @gzuncompress($response->content);
                    break;

                case 'gzip':
                    $decoded = @gzdecode($response->content);
                    break;
            }

            if ($decoded !== false) {
                $response->content = $decoded;
                Yii::debug('Ответ декодирован из: ' . $encoding, __METHOD__);
            }
        }
    }
}
