<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 08.02.20 04:26:26
 */

declare(strict_types = 1);

use yii\caching\FileCache;

defined('YII_DEBUG') || define('YII_DEBUG', true);
defined('YII_ENV') || define('yII_ENV', 'dev');

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';

/** @noinspection PhpUnhandledExceptionInspection */
$application = new yii\console\Application([
    'id' => 'test',
    'basePath' => dirname(__DIR__),
    'aliases' => [
        '@dicr\\http' => dirname(__DIR__) . '/src',
        '@dicr\\tests' => __DIR__
    ],
    'components' => [
        'cache' => [
            'class' => FileCache::class,
        ],
    ]
]);









