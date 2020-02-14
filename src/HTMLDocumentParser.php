<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 14.02.20 18:18:12
 */

declare(strict_types = 1);

namespace dicr\http;

use simplehtmldom\HtmlDocument;
use yii\base\BaseObject;
use yii\httpclient\ParserInterface;
use yii\httpclient\Response;

/**
 * Парсер HTML-текста в \simplehtmldom\HtmlDocument.
 *
 * @see https://sourceforge.net/projects/simplehtmldom/
 * @see https://simplehtmldom.sourceforge.io
 * @noinspection PhpUnused
 */
class HTMLDocumentParser extends BaseObject implements ParserInterface
{
    /** @var string формат ответа */
    public const FORMAT = __CLASS__;

    /**
     * {@inheritdoc}
     */
    public function parse(Response $response)
    {
        // получаем текст ответа
        $content = trim($response->content);
        if ($content === '') {
            return null;
        }

        return new HtmlDocument($response->content);
    }
}
