<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 07.02.20 20:32:58
 */

declare(strict_types = 1);
namespace dicr\http;

use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;

/**
 * Задержка запросов для yii\httpclient\Client
 *
 * @property-read float $delay следующая задержка, сек
 * @noinspection PhpUnused
 */
class RequestDelayBehavior extends Behavior
{
    /** @var int minimum delay value, microseconds */
    public $delayMin = 0;

    /** @var int maximum delay value, microseconds */
    public $delayMax = 3000;

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (! is_numeric($this->delayMin) || $this->delayMin < 0) {
            throw new InvalidConfigException('delayMin');
        }

        $this->delayMin = (int)$this->delayMin;

        if (! is_numeric($this->delayMax) || $this->delayMax < 0) {
            throw new InvalidConfigException('delayMax');
        }

        $this->delayMax = (int)$this->delayMax;

        if ($this->delayMin > $this->delayMax) {
            throw new InvalidConfigException('delayMin > delayMax');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            Client::EVENT_BEFORE_SEND => '_beforeSend',
        ];
    }

    /**
     * Следующее случайное значение задержки.
     *
     * @return int, microseconds
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function getDelay()
    {
        if ($this->delayMin === $this->delayMax) {
            return $this->delayMin;
        }

        return random_int($this->delayMin, $this->delayMax);
    }

    /**
     * Adjust request.
     *
     * @noinspection PhpUnused
     * @noinspection PhpMethodNamingConventionInspection
     */
    public function _beforeSend()
    {
        $delay = $this->delay;

        if ($delay > 0) {
            usleep($delay);
        }
    }
}