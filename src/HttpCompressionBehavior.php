<?php
/*
 * @copyright 2019-2021 Dicr http://hap.org
 * @author Orhan t <develop@hap.org>
 * @license MIT
 * @version 19.04.21 17:02:14
 */

declare(strict_types = 1);
namespace hap\http;

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
    public function events(): array
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

                default:
                    break;
            }

            if ($decoded !== false) {
                $response->content = $decoded;
                Yii::debug('Response decoded from: ' . $encoding, __METHOD__);
            }
        }
    }
}
