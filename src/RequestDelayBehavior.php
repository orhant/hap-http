<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 08.02.20 04:45:11
 */

declare(strict_types = 1);
namespace dicr\http;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;

/**
 * Задержка запросов для yii\httpclient\Client
 *
 * @noinspection PhpUnused
 */
class RequestDelayBehavior extends Behavior
{
    /** @var float minimum delay value, seconds */
    public $delayMin = 0.0;

    /** @var float maximum delay value, seconds */
    public $delayMax = 2.0;

    /**
     * @inheritDoc
     * @throws \yii\base\InvalidConfigException
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
     * @return int microseconds
     * @throws \Exception
     */
    protected function getMicroDelay()
    {
        $min = (int)round($this->delayMin * 1000000);
        $max = (int)round($this->delayMax * 1000000);

        return $min === $max ? $min : random_int($min, $max);
    }

    /**
     * Adjust request.
     *
     * @noinspection PhpUnused
     * @noinspection PhpMethodNamingConventionInspection
     * @throws \Exception
     */
    public function _beforeSend()
    {
        $delay = $this->getMicroDelay();
        if ($delay > 0) {
            Yii::debug(sprintf('Ожидаем паузу: %.1s', $delay / 1000000));
            usleep($delay);
        }
    }
}