<?php
/*
 * @copyright 2019-2021 Orhant http://hap.org
 * @author Orhan t <develop@hap.org>
 * @license BSD-3-Clause
 * @version 28.05.21 15:03:57
 */

declare(strict_types = 1);
namespace hap\http;

use yii\base\Behavior;
use yii\httpclient\Client;
use yii\httpclient\RequestEvent;

use function iconv;
use function preg_match;

/**
 * Конвертирует ответ в UTF-8
 */
class ResponseCharsetBehavior extends Behavior
{
    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            Client::EVENT_AFTER_SEND => '_afterSend'
        ];
    }

    /**
     * Adjust response.
     *
     * @param RequestEvent $event
     * @noinspection PhpMethodNamingConventionInspection
     */
    public function _afterSend(RequestEvent $event): void
    {
        $response = $event->response;
        $matches = null;
        $charset = null;

        // determine the encoding of the response
        $contentType = $response->headers->get('content-type');
        if (($contentType !== null
                && preg_match('~text/\w+~ui', $contentType)
                && preg_match('~[\;\s]charset=[\'\"]?(.+?)[\'\"\;\s]*$~ui', $contentType, $matches))
            || preg_match('~<meta\s+charset=[\'\"](.+?)[\'\"]~ism', $response->content, $matches)
            || preg_match('~<meta\s+[^\>]+charset=(.+?)[\'\"]~ism', $response->content, $matches)
        ) {
            $charset = $matches[1];
        }

        // перекодируем ответ
        if ($charset !== null && ! preg_match('~^utf\-?8$~ui', $charset)) {
            $response->content = iconv($charset, 'utf-8', $response->content);
        }
    }
}
