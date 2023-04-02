<?php
/*
 * @copyright 2019-2022 Dicr http://hap.org
 * @author Orhan t <develop@hap.org>
 * @license BSD-3-Clause
 * @version 04.01.22 22:24:42
 */

declare(strict_types = 1);

namespace hap\http;

use simplehtmldom\HtmlDocument;
use yii\base\BaseObject;
use yii\httpclient\ParserInterface;
use yii\httpclient\Response;

/**
 * HTML-text parser in \simplehtmldom\HtmlDocument.
 *
 * @see https://sourceforge.net/projects/simplehtmldom/
 * @see https://simplehtmldom.sourceforge.io
 */
class HTMLDocumentParser extends BaseObject implements ParserInterface
{
    /** @var string response format */
    public const FORMAT = 'html-document';

    /** @var bool convert tag names to small letters */
    public bool $lowerTags = true;

    /** @var bool force close all tags, e.g. <br></br> */
    public bool $forceTagsClosed = false;

    /** @var bool remove spaces and text breaks between tags and in text */
    public bool $removeWhitespace = false;

    /** @var string|null plaintext for <br/> (default \r\n) */
    public ?string $brText = null;

    /** @var string|null text for span (default " ") */
    public ?string $spanText = null;

    /** @var int|null additional simplehtmldom options */
    public ?int $options = null;

    /**
     * Parses HTML content.
     *
     * @param string $content
     * @return HtmlDocument
     */
    public function parseContent(string $content): HtmlDocument
    {
        return new HtmlDocument($content, $this->lowerTags,
            $this->forceTagsClosed, 'UTF-8', $this->removeWhitespace,
            $this->brText, $this->spanText, $this->options
        );
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Response $response): HtmlDocument
    {
        return $this->parseContent($response->content);
    }
}
