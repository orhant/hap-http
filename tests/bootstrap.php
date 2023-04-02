<?php
/**
 * @copyright 2019-2020 Orhant http://hap.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 19.08.20 23:36:27
 */

declare(strict_types = 1);

/** DEBUG */
defined('YII_DEBUG') || define('YII_DEBUG', true);

/** ENV */
defined('YII_ENV') || define('YII_ENV', 'dev');

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';

/** @noinspection PhpUnhandledExceptionInspection */
new yii\console\Application([
    'id' => 'test',
    'basePath' => __DIR__,
    'components' => [
        'cache' => [
            'class' => yii\caching\ArrayCache::class
        ],
    ]
]);









