<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 12.07.20 13:03:26
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
     * @throws Exception
     */
    public function _beforeSend()
    {
        $min = (int)round($this->delayMin * 1000000);
        $max = (int)round($this->delayMax * 1000000);

        $delay = $min === $max ? $min : random_int($min, $max);
        if ($delay > 0) {
            Yii::debug(sprintf('Ожидаем паузу: %.1fs', $delay / 1000000), __METHOD__);
            usleep($delay);
        }

        return true;
    }
}
