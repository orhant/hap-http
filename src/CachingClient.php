<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 08.02.20 09:02:34
 */

declare(strict_types = 1);

namespace dicr\http;

use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;
use yii\di\Instance;
use yii\httpclient\Client;
use yii\httpclient\Request;
use yii\httpclient\Response;
use function is_int;

/**
 * Client with response caching.
 *
 * @noinspection PhpUnused
 */
class CachingClient extends Client
{
    /** @var \yii\caching\CacheInterface */
    public $cache;

    /** @var bool учитывать куки в кеше. Включать если контент в ответе различается от куков */
    public $cacheCookies = false;

    /** @var int cache time, s */
    public $cacheDuration;

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidConfigException
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
    }

    /**
     * Return cache key for request.
     *
     * @param \yii\httpclient\Request $request
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
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
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
                return $response;
            }
        }

        // fetch from server
        $response = parent::send($request);

        // store in cache
        if ($response->isOk && ! empty($this->cache)) {
            $cacheResponse = $this->cacheCookies ? $response : (clone $response)->setCookies([]);

            // save response
            $this->cache->set($cacheKey, $cacheResponse, $this->cacheDuration, new TagDependency([
                'tags' => [__CLASS__]
            ]));
        }

        return $response;
    }
}