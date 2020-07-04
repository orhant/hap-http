<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.07.20 12:16:14
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
use function is_int;

/**
 * Client with response caching.
 *
 * For caching repeatable requests, headers (Cookies, User-Agent, ...) must be similar for next requests
 *
 * @noinspection PhpUnused
 */
class CachingClient extends Client
{
    /** @var CacheInterface */
    public $cache = 'cache';

    /** @var string tags fro TagDependency */
    public const CACHE_TAGS = [__CLASS__];

    /**
     * @var bool if true, then cache key calculated with cookies. If false, then browsing is incognito.
     * Use this only when response depends on cookies.
     */
    public $cacheCookies = false;

    /** @var int cache time, s */
    public $cacheDuration;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (! empty($this->cache)) {
            $this->cache = Instance::ensure($this->cache, CacheInterface::class);
        }

        if (empty($this->cacheDuration)) {
            $this->cacheDuration = null;
        } elseif (! is_int($this->cacheDuration) || $this->cacheDuration < 0) {
            throw new InvalidConfigException('cacheDuration');
        }

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
    protected function cacheKey(Request $request)
    {
        $keyRequest = $this->cacheCookies ? $request : (clone $request)->setCookies([]);

        return [__METHOD__, $keyRequest->toString()];
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function send($request)
    {
        /** @var string[]|null $cacheKey key for cache */
        $cacheKey = null;

        // load from cache
        if (! empty($this->cache)) {
            $cacheKey = $this->cacheKey($request);

            // load response
            $response = $this->cache->get($cacheKey);
            if ($response instanceof Response) {
                Yii::debug('Used cached response for request: ' . $request->fullUrl, __METHOD__);

                // restore client link
                $response->client = $request->client;

                return $response;
            }
        }

        // fetch from server
        $response = parent::send($request);

        // store in cache
        if ($response->isOk && ! empty($this->cache)) {
            $cacheResponse = $this->cacheCookies ? $response : (clone $response)->setCookies([]);

            // clean connection to client to not save it in cache
            $cacheResponse->client = null;

            // save response
            $this->cache->set($cacheKey, $cacheResponse, $this->cacheDuration, new TagDependency([
                'tags' => self::CACHE_TAGS
            ]));
        }

        return $response;
    }

    /**
     * Invalidate http-response cache.
     *
     * @noinspection PhpUnused
     */
    public function invalidateCache()
    {
        TagDependency::invalidate($this->cache, self::CACHE_TAGS);
    }
}
