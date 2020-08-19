<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 19.08.20 23:16:10
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
 */
class HTMLDocumentParser extends BaseObject implements ParserInterface
{
    /** @var string формат ответа */
    public const FORMAT = 'html-document';

    /** @var bool преобразовывать названия тегов в маленькие буквы */
    public $lowerTags = true;

    /** @var bool принудительное закрытие всех тегов, например <br></br> */
    public $forceTagsClosed = false;

    /** @var bool удалять пробелы и переносы текста между тегами и в тексте */
    public $removeWhitespace = false;

    /** @var string plaintext для <br/> (по-умолчанию \r\n) */
    public $brText;

    /** @var string текст для span (по-умолчанию " ") */
    public $spanText;

    /** @var int дополнительные опции simplehtmldom */
    public $options;

    /**
     * Парсит HTML-контент.
     *
     * @param string $content
     * @return HtmlDocument
     */
    public function parseContent(string $content): HtmlDocument
    {
        return new HtmlDocument($content, (bool)$this->lowerTags,
            (bool)$this->forceTagsClosed, 'UTF-8', $this->removeWhitespace,
            $this->brText, $this->spanText, $this->options
        );
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Response $response)
    {
        return $this->parseContent((string)$response->content);
    }
}
