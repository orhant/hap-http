<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 19.08.20 23:19:30
 */

declare(strict_types = 1);
namespace dicr\http;

use Exception;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;

/**
 * Задержка запросов для yii\httpclient\Client
 */
class RequestDelayBehavior extends Behavior
{
    /** @var float minimum delay value, seconds */
    public $delayMin = 0.0;

    /** @var float maximum delay value, seconds */
    public $delayMax = 2.0;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        $this->delayMin = (float)$this->delayMin;
        if ($this->delayMin < 0) {
            throw new InvalidConfigException('delayMin');
        }

        $this->delayMax = (float)$this->delayMax;
        if ($this->delayMax < 0) {
            throw new InvalidConfigException('delayMax');
        }

        if ($this->delayMin > $this->delayMax) {
            throw new InvalidConfigException('delayMin > delayMax');
        }
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            Client::EVENT_BEFORE_SEND => '_beforeSend',
        ];
    }

    /**
     * Adjust request.
     *
     * @noinspection PhpMethodNamingConventionInspection
     * @throws Exception
     */
    public function _beforeSend(): void
    {
        $min = (int)round($this->delayMin * 1000000);
        $max = (int)round($this->delayMax * 1000000);

        $delay = $min === $max ? $min : random_int($min, $max);
        if ($delay > 0) {
            Yii::debug(sprintf('Ожидаем паузу: %.1fs', $delay / 1000000), __METHOD__);
            usleep($delay);
        }
    }
}
