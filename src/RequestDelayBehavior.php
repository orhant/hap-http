<?php
/*
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 19.04.21 17:02:13
 */

declare(strict_types = 1);
namespace dicr\http;

use Exception;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;

use function is_numeric;

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
    public function init(): void
    {
        parent::init();

        if (! is_numeric($this->delayMin) || $this->delayMin < 0) {
            throw new InvalidConfigException('delayMin');
        }

        if (! is_numeric($this->delayMax) || $this->delayMax < 0) {
            throw new InvalidConfigException('delayMax');
        }

        if ($this->delayMin > $this->delayMax) {
            throw new InvalidConfigException('delayMin > delayMax');
        }
    }

    /**
     * @inheritdoc
     */
    public function events(): array
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
