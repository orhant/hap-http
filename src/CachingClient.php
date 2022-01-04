<?php
/*
 * @copyright 2019-2022 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license BSD-3-Clause
 * @version 04.01.22 22:23:24
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
use function preg_quote;
use function strtoupper;

/**
 * HTTP-клиент с кэшированием ответов.
 *
 * Для управлением кэшем используется заголовок Cache-Control со следующими значениями:
 * - no-cache - не кэшировать данный запрос
 * - max-age=seconds - установить время кэширования, секунд
 * При данных значениях заголовок Cache-Control удаляется из запроса.
 */
class CachingClient extends Client
{
    public string|CacheInterface $cache = 'cache';

    /** @var string */
    public const CACHE_CONTROL = 'Cache-Control';

    /** @var string не кэшировать запрос */
    public const CACHE_NO_CACHE = 'no-cache';

    /** @var string время кэширования */
    public const CACHE_MAX_AGE = 'max-age';

    /** @var int cache time, s */
    public int $cacheDuration = 86400;

    /**
     * @var bool if true, then cache key calculated with cookies. If false, then browsing is incognito.
     * Use this only when response depends on cookies.
     */
    public bool $cacheCookies = false;

    /** @var string[] методы запроса для кэширования */
    public array $cacheMethods = ['GET'];

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        $this->cache = Instance::ensure($this->cache, CacheInterface::class);

        if ($this->cacheDuration < 0) {
            throw new InvalidConfigException('cacheDuration');
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
     * Возвращает время кэширования запроса, заданное в заголовке Cache-Control.
     *
     * @param Request $request
     * @return ?int
     */
    private static function cacheDuration(Request $request): ?int
    {
        $vals = $request->headers->get(self::CACHE_CONTROL, null, false);
        $modified = false;
        $cacheTime = null;
        $matches = null;

        foreach ($vals ?: [] as $i => $val) {
            if ($val === self::CACHE_NO_CACHE) {
                $cacheTime = 0;
                unset($vals[$i]);
                $modified = true;
            } elseif (preg_match('~^' . preg_quote(self::CACHE_MAX_AGE, '~') . '=(\d+)$~ui', $val, $matches)) {
                $cacheTime = (int)$matches[1];
                unset($vals[$i]);
                $modified = true;
            }
        }

        // устанавливаем новые значения
        if ($modified) {
            $request->headers->remove(self::CACHE_CONTROL);

            foreach ($vals as $val) {
                $request->headers->add(self::CACHE_CONTROL, $val);
            }
        }

        return $cacheTime;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function send($request): Response
    {
        // время кэширования
        $cacheDuration = self::cacheDuration($request) ?? $this->cacheDuration;

        /** @var ?array $cacheKey ключ кэша если кэширование запроса разрешено */
        $cacheKey = $cacheDuration !== 0 && in_array(strtoupper($request->method), $this->cacheMethods, true) ?
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
            $this->cache->set($cacheKey, $cachingResponse, $cacheDuration, new TagDependency([
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
