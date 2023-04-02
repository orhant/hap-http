<?php
/*
 * @copyright 2019-2022 Dicr http://hap.org
 * @author Orhan t <develop@hap.org>
 * @license BSD-3-Clause
 * @version 04.01.22 22:26:10
 */

declare(strict_types = 1);
namespace hap\http;

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
    public float $delayMin = 0.0;

    /** @var float maximum delay value, seconds */
    public float $delayMax = 2.0;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if ($this->delayMin < 0) {
            throw new InvalidConfigException('delayMin');
        }

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
            Yii::debug(sprintf('Waiting for a pause: %.1fs', $delay / 1000000), __METHOD__);
            usleep($delay);
        }
    }
}
