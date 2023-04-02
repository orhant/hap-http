<?php
/*
 * @copyright 2019-2022 Dicr http://hap.org
 * @author Orhan t <develop@hap.org>
 * @license BSD-3-Clause
 * @version 04.01.22 22:29:15
 */

declare(strict_types = 1);
namespace hap\http;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;
use yii\di\Instance;
use yii\httpclient\Client;
use yii\httpclient\Request;
use yii\httpclient\RequestEvent;
use yii\web\Cookie;
use yii\web\CookieCollection;

/**
 * Persistence of Cookies in the cache between requests.
 */
class PersistentCookiesBehavior extends Behavior
{
    /** @var CacheInterface|string cookie cache (the key is bound to the request domain) */
    public string|CacheInterface $store = 'cache';

    /** @var ?int cache time*/
    public ?int $cacheDuration = null;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        $this->store = Instance::ensure($this->store, CacheInterface::class);

        if (isset($this->cacheDuration)) {
            $this->cacheDuration = (int)$this->cacheDuration;
            if ($this->cacheDuration < 1) {
                throw new InvalidConfigException('cacheDuration');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function events(): array
    {
        return [
            Client::EVENT_BEFORE_SEND => '_beforeSend',
            Client::EVENT_AFTER_SEND => '_afterSend'
        ];
    }

    /**
     * Cookie caching key for the domain.
     *
     * @param string $domain
     * @return array
     */
    public static function cacheKey(string $domain): array
    {
        return [__CLASS__, $domain];
    }

    /**
     * Loads their cookie cache for the domain.
     *
     * @param string $domain
     * @return ?CookieCollection
     */
    public function loadCookies(string $domain): ?CookieCollection
    {
        $key = static::cacheKey($domain);

        return $this->store->get($key) ?: null;
    }

    /**
     * Stores cookies for the domain. If the value is empty, then deletes.
     *
     * @param string $domain
     * @param CookieCollection $cookies associative array name => Cookie
     * @return $this
     */
    public function saveCookies(string $domain, CookieCollection $cookies): PersistentCookiesBehavior
    {
        $key = static::cacheKey($domain);

        if ($cookies->count < 1) {
            $this->store->delete($key);
        } else {
            $this->store->set($key, $cookies, $this->cacheDuration, new TagDependency([
                'tags' => [__CLASS__, $domain]
            ]));
        }

        return $this;
    }

    /**
     * Returns the request domain.
     *
     * @param Request $request
     * @return string
     */
    public static function domain(Request $request): string
    {
        return parse_url($request->fullUrl, PHP_URL_HOST);
    }

    /**
     * Adds a cookie to the request.
     *
     * @param RequestEvent $event
     * @noinspection PhpMethodNamingConventionInspection
     */
    public function _beforeSend(RequestEvent $event): void
    {
        // request
        $request = $event->request;

        // domain
        $domain = static::domain($request);

        // loading cookies
        $cookies = $this->loadCookies($domain);

        // add to request
        if ($cookies !== null && $cookies->count > 0) {
            $request->addCookies($cookies->toArray());
            Yii::debug('Added ' . $cookies->count . ' cookies', __METHOD__);
        }
    }

    /**
     * Takes cookies from the response.
     *
     * @param RequestEvent $event
     * @noinspection PhpMethodNamingConventionInspection
     */
    public function _afterSend(RequestEvent $event): void
    {
        $response = $event->response;
        if ($response->cookies->count > 0) {
            // request
            $request = $event->request;

            // domain
            $domain = static::domain($request);

            // loading cookies
            $cookies = $this->loadCookies($domain);

            if ($cookies === null) {
                $cookies = $response->cookies;
            } else {
                /** @var Cookie $cookie */
                foreach ($response->cookies->toArray() as $cookie) {
                    $cookies->add($cookie);
                    Yii::debug('Получен cookie: ' . $cookie->name . '=' . $cookie->value, __METHOD__);
                }
            }

            // save cookies in cache
            $this->saveCookies($domain, $cookies);
        }
    }
}
