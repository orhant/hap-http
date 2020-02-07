<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 07.02.20 20:18:08
 */

declare(strict_types = 1);

namespace dicr\http;

use Yii;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\di\Instance;
use yii\httpclient\Client;

/**
 * Загрузчик страниц и ресурсов.
 *
 * @package app\lib
 * @noinspection PhpUnused
 */
class Downloader extends Client
{
    /** @var \yii\caching\CacheInterface */
    public $cache = 'cache';

    /** @var float задержка после запроса, сек */
    public $delay = 1.0;

    /** @var \dicr\settings\AbstractSettingsStore хранилище для cookies и прочее */
    public $cookieStore;

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

        if (! empty($this->cookieStore)) {
            $this->cookieStore = Instance::ensure($this->cookieStore, AbstractSettingsStore::class);
        }

        $this->delay = (float)$this->delay;
        if ($this->delay = 0) {
            throw new InvalidConfigException('delay: ' . $this->delay);
        }
    }

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'compression' => HttpCompressionBehavior::class
        ]);
    }

    /**
     * @inheritDoc
     *
     * @param \yii\httpclient\Request $request
     * @throws \dicr\settings\SettingsException
     */
    public function beforeSend($request)
    {
        parent::beforeSend($request);

        // задержка перед запросом
        if ($this->delay > 0) {
            usleep((int)round($this->delay * 1000));
        }

        // загружаем куки
        if (! empty($this->cookieStore) && $request->cookies->count < 1) {
            $request->cookies->fromArray($this->cookieStore->get(__CLASS__, 'cookies') ?: []);
        }
    }

    /**
     * @inheritDoc
     *
     * @param \yii\httpclient\Request $request
     * @param \yii\httpclient\Response $response
     * @throws \dicr\settings\SettingsException
     */
    public function afterSend($request, $response)
    {
        parent::afterSend($request, $response);

        // сохраняем куки
        if (! empty($this->cookieStore) && $response->cookies->count > 0) {
            $this->cookieStore->set(__CLASS__, 'cookies', $response->cookies->toArray());
            Yii::debug('Сохранены новые Cookies', __METHOD__);
        }
    }
}