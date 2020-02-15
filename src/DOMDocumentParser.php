<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 16.02.20 04:08:16
 */

declare(strict_types = 1);

namespace dicr\http;

use DOMDocument;
use yii\base\BaseObject;
use yii\httpclient\ParserInterface;
use yii\httpclient\Response;
use const LIBXML_DTDLOAD;
use const LIBXML_NOERROR;
use const LIBXML_NOWARNING;
use const LIBXML_PARSEHUGE;

/**
 * Парсер HTML-текста в \DOMDocument.
 *
 * @noinspection PhpUnused
 */
class DOMDocumentParser extends BaseObject implements ParserInterface
{
    /** @var string формат ответа */
    public const FORMAT = 'dom-document';

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

        // получаем кодировку ответа
        $contentType = $response->getHeaders()->get('content-type', '');
        $encoding = preg_match('~charset=(.+)~i', $contentType, $matches) ? $matches[1] : 'UTF-8';

        // если у документа тег с кодировкой стоит после того как utf-8 символы, то он не учитывается, поэтому добавляем насильно.
        // @link https://www.php.net/manual/en/domdocument.loadhtml.php
        $content =
            // объявление XML-документа. Здесь encoding не влияет на распознавание текста
            '<?xml version="1.0" encoding="' . $encoding . '" standalone="yes"?>' .
            // meta старого типа (http-equiv) устанавливает кодировку текста. Она должны быть раньше чем текст в документе.
            '<meta http-equiv="Content-Type" content="text/html; charset=' . $encoding . '"/>' .
            '<meta charset="' . $encoding . '"/>' .
            $content;

        // создаем документ
        $doc = new DOMDocument('1.0', $encoding);
        $doc->resolveExternals = false;
        $doc->recover = true;
        $doc->strictErrorChecking = false;
        $doc->validateOnParse = false;
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->substituteEntities = false;

        // пытаемся загрузить HTML
        if (! $doc->loadHTML($content, LIBXML_DTDLOAD | LIBXML_PARSEHUGE | LIBXML_NOWARNING |
            LIBXML_NOERROR | LIBXML_NOCDATA | LIBXML_NONET | LIBXML_NOXMLDECL)) {
            return null;
        }

        $doc->normalizeDocument();

        return $doc;
    }
}
