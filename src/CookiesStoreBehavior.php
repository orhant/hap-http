<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 07.02.20 21:24:45
 */

declare(strict_types = 1);
namespace dicr\http;

use yii\base\Behavior;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;
use yii\di\Instance;
use yii\httpclient\Client;
use yii\httpclient\Request;
use yii\httpclient\RequestEvent;
use yii\web\CookieCollection;

/**
 * Сохранение Cookies в кэше между запросами.
 *
 * @noinspection PhpUnused
 */
class CookiesStoreBehavior extends Behavior
{
    /** @var \yii\caching\CacheInterface кэш куков (ключ привязывается к домену запроса) */
    public $cache = 'cache';

    /** @var int|null время хранения в кэше */
    public $cacheDuration;

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        $this->cache = Instance::ensure($this->cache, CacheInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            Client::EVENT_BEFORE_SEND => '_beforeSend',
            Client::EVENT_AFTER_SEND => '_afterSend'
        ];
    }

    /**
     * Ключ кэширования куков для домена.
     *
     * @param string $domain
     * @return array
     */
    protected static function cacheKey(string $domain)
    {
        return [__CLASS__, $domain];
    }

    /**
     * Загружает их кэша куки для домена.
     *
     * @param string $domain
     * @return \yii\web\CookieCollection|null
     */
    public function loadCookies(string $domain)
    {
        $key = static::cacheKey($domain);

        return $this->cache->get($key) ?: null;
    }

    /**
     * Сохраняет куки для домена. Если значение пустое, то удаляет.
     *
     * @param string $domain
     * @param CookieCollection $cookies ассоциативный массив name => Cookie
     */
    public function saveCookies(string $domain, CookieCollection $cookies)
    {
        $key = static::cacheKey($domain);

        if ($cookies === null || $cookies->count < 1) {
            $this->cache->delete($key);
        } else {
            $this->cache->set($key, $cookies, $this->cacheDuration, new TagDependency([
                'tags' => [__CLASS__, $domain]
            ]));
        }
    }

    /**
     * Возвращает домен запроса.
     *
     * @param \yii\httpclient\Request $request
     * @return string
     */
    public static function domain(Request $request)
    {
        return parse_url($request->fullUrl, PHP_URL_HOST);
    }

    /**
     * Добавляет куки к запросу.
     *
     * @param RequestEvent $event
     * @noinspection PhpUnused
     * @noinspection PhpMethodNamingConventionInspection
     */
    public function _beforeSend(RequestEvent $event)
    {
        // запрос
        $request = $event->request;

        // домен
        $domain = static::domain($request);

        // загружаем куки
        $cookies = $this->loadCookies($domain);

        // добавляем к запросу
        if ($cookies !== null && $cookies->count > 0) {
            $request->addCookies($cookies->toArray());
        }
    }

    /**
     * Забирает куки из ответа.
     *
     * @param RequestEvent $event
     * @noinspection PhpUnused
     * @noinspection PhpMethodNamingConventionInspection
     */
    public function _afterSend(RequestEvent $event)
    {
        $response = $event->response;
        if ($response->cookies->count > 0) {
            // запрос
            $request = $event->request;

            // домен
            $domain = static::domain($request);

            // загружаем текущие куки
            $cookies = $this->loadCookies($domain);

            if ($cookies === null) {
                $cookies = $response->cookies;
            } else {
                foreach ($response->cookies->toArray() as $cookie) {
                    $cookies->add($cookie);
                }
            }

            // сохраняем куки в кеше
            $this->saveCookies($domain, $cookies);
        }
    }
}
