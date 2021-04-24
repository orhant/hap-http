<?php
/*
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 25.04.21 00:57:01
 */

declare(strict_types = 1);

namespace dicr\http;

use Yii;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;
use yii\di\Instance;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\httpclient\Request;
use yii\httpclient\Response;

use function in_array;
use function is_array;
use function is_int;
use function strtoupper;

/**
 * Client with response caching.
 *
 * For caching repeatable requests, headers (Cookies, User-Agent, ...) must be similar for next requests
 */
class CachingClient extends Client
{
    /** @var CacheInterface */
    public $cache = 'cache';

    /** @var int cache time, s */
    public $cacheDuration;

    /**
     * @var bool if true, then cache key calculated with cookies. If false, then browsing is incognito.
     * Use this only when response depends on cookies.
     */
    public $cacheCookies = false;

    /** @var string[] методы запроса для кэширования */
    public $cacheMethods = ['GET'];

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        $this->cache = Instance::ensure($this->cache, CacheInterface::class);

        if (empty($this->cacheDuration)) {
            $this->cacheDuration = null;
        } elseif (! is_int($this->cacheDuration) || $this->cacheDuration < 0) {
            throw new InvalidConfigException('cacheDuration');
        }

        if (! is_array($this->cacheMethods)) {
            throw new InvalidConfigException('cacheMethods');
        }

        $this->cacheMethods = array_map('\strtoupper', $this->cacheMethods);

        // настраиваем дополнительные парсеры
        $this->parsers = array_merge([
            DOMDocumentParser::FORMAT => DOMDocumentParser::class,
            HTMLDocumentParser::FORMAT => HTMLDocumentParser::class
        ], $this->parsers ?: []);
    }

    /**
     * Return cache key for request.
     *
     * @param Request $request
     * @return string[]
     */
    protected function cacheKey(Request $request): array
    {
        $keyRequest = clone $request;

        // очищаем куки
        if (! $this->cacheCookies) {
            $keyRequest->setCookies([]);
        }

        return [__METHOD__, $keyRequest->toString()];
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function send($request): Response
    {
        /** @var ?array $cacheKey ключ кэша если кэширование запроса разрешено */
        $cacheKey = in_array(strtoupper($request->method), $this->cacheMethods, true) ?
            $this->cacheKey($request) : null;

        /** @var Response|false $response */
        $response = $cacheKey ? $this->cache->get($cacheKey) : false;

        // запрос имеется в кэше
        if ($response instanceof Response) {
            Yii::debug('Used cached response for request: ' . $request->fullUrl, __METHOD__);

            // восстанавливаем связь с клиентом
            $response->client = $request->client;

            return $response;
        }

        // отправляем запрос на сервер
        $response = parent::send($request);

        // store in cache
        if ($cacheKey && $response->isOk) {
            // клонируем объект для подготовки к кэшу
            $cachingResponse = clone $response;

            // clean connection to client to not save it in cache
            $cachingResponse->client = null;

            // удаляем куки
            if (! $this->cacheCookies) {
                $cachingResponse->setCookies([]);
            }

            // save response
            $this->cache->set($cacheKey, $cachingResponse, $this->cacheDuration, new TagDependency([
                'tags' => [__CLASS__]
            ]));
        }

        return $response;
    }

    /**
     * Invalidate http-response cache.
     */
    public function invalidateCache(): void
    {
        if (! empty($this->cache)) {
            TagDependency::invalidate($this->cache, [__CLASS__]);
        }
    }
}
