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
 * Сохранение Cookies в кэше между запросами.
 *
 * @noinspection PhpUnused
 */
class PersistentCookiesBehavior extends Behavior
{
    /** @var CacheInterface кэш куков (ключ привязывается к домену запроса) */
    public $store = 'cache';

    /** @var int|null время хранения в кэше */
    public $cacheDuration;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init()
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
    public static function cacheKey(string $domain)
    {
        return [__CLASS__, $domain];
    }

    /**
     * Загружает их кэша куки для домена.
     *
     * @param string $domain
     * @return CookieCollection|null
     */
    public function loadCookies(string $domain)
    {
        $key = static::cacheKey($domain);

        return $this->store->get($key) ?: null;
    }

    /**
     * Сохраняет куки для домена. Если значение пустое, то удаляет.
     *
     * @param string $domain
     * @param CookieCollection $cookies ассоциативный массив name => Cookie
     * @return $this
     */
    public function saveCookies(string $domain, CookieCollection $cookies)
    {
        $key = static::cacheKey($domain);

        if ($cookies === null || $cookies->count < 1) {
            $this->store->delete($key);
        } else {
            $this->store->set($key, $cookies, $this->cacheDuration, new TagDependency([
                'tags' => [__CLASS__, $domain]
            ]));
        }

        return $this;
    }

    /**
     * Возвращает домен запроса.
     *
     * @param Request $request
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
            Yii::debug('Добавлено ' . $cookies->count . ' куков', __METHOD__);
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
                /** @var Cookie $cookie */
                foreach ($response->cookies->toArray() as $cookie) {
                    $cookies->add($cookie);
                    Yii::debug('Получен cookie: ' . $cookie->name . '=' . $cookie->value, __METHOD__);
                }
            }

            // сохраняем куки в кеше
            $this->saveCookies($domain, $cookies);
        }
    }
}
