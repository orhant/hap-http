<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.07.20 12:06:46
 */

declare(strict_types = 1);
namespace dicr\http;

use ArrayAccess;
use dicr\helper\ArrayAccessTrait;
use dicr\helper\Url;
use InvalidArgumentException;
use LogicException;
use Throwable;
use Yii;
use yii\base\Arrayable;
use yii\base\ArrayableTrait;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use function gettype;
use function in_array;
use function is_array;
use function is_string;

/**
 * Модель ссылки.
 *
 * @property string $scheme
 * @property string $user
 * @property string $pass
 * @property string $host
 * @property int $port
 * @property string $path
 * @property array $query
 * @property string $fragment
 *
 * @property-read string $hostInfo user:pass@host:port
 * @property-read string $requestUri строка запроса (путь?параметры#фрагмент)
 * @property-read bool $isAbsolute признак абсолютной ссылки
 *
 * @noinspection PhpUnused
 */
class UrlInfo extends BaseObject implements Arrayable, ArrayAccess
{
    use ArrayableTrait;
    use ArrayAccessTrait;

    /** @var array стандартные сервисы и порты */
    public const SERVICES = [
        'http' => 80,
        'https' => 443,
        'ftp' => 21,
        'ssh' => 22,
        'smb' => 445
    ];

    /** @var array специальные схемы, которым не обязателен хост */
    public const SCHEME_NONHTTP = [
        'javascript',
        'mailto',
        'tel'
    ];

    /** @var string схема */
    private $_scheme = '';

    /** @var string логин */
    private $_user = '';

    /** @var string пароль */
    private $_pass = '';

    /** @var string сервер (домен в utf8) */
    private $_host = '';

    /** @var int порт */
    private $_port = 0;

    /** @var string путь */
    private $_path = '';

    /** @var array параметры key => $val */
    private $_query = [];

    /** @var string фрагмент */
    private $_fragment = '';

    /**
     * Конструктор
     *
     * @param string|array $url
     * @throws InvalidArgumentException
     */
    public function __construct($url = [])
    {
        // конвертируем из строки
        if (is_string($url)) {
            $config = $url === '' ? [] : parse_url($url);
            if ($config === false) {
                throw new InvalidArgumentException('url: ' . $url);
            }

            $url = (array)$config;
        } elseif (! is_array($url)) {
            throw new InvalidArgumentException('неизвестный тип url: ' . gettype($url));
        }

        parent::__construct($url);
    }

    /**
     * Создает экземпляр из строки
     *
     * @param string $url адрес URL
     * @return static|false
     */
    public static function fromString(string $url)
    {
        try {
            return new static($url);
        } catch (Throwable $ex) {
            Yii::debug($ex, __METHOD__);
        }

        return false;
    }

    /**
     * Возвращает схему по номеру порта
     *
     * @param int $port
     * @return string
     */
    public static function schemeByPort(int $port)
    {
        foreach (self::SERVICES as $scheme => $p) {
            if ($p === $port) {
                return $scheme;
            }
        }

        return '';
    }

    /**
     * Возвращает номер порта по схеме сервиса
     *
     * @param string $scheme
     * @return int
     */
    public static function portByScheme(string $scheme)
    {
        foreach (self::SERVICES as $sch => $port) {
            if ($sch === $scheme) {
                return $port;
            }
        }

        return 0;
    }

    /**
     * {@inheritDoc}
     * @throws InvalidConfigException
     * @see \yii\base\BaseObject::init()
     */
    public function init()
    {
        parent::init();

        // если указана схема, то должен быть указан хост
        if ($this->host === '' && $this->_scheme !== '' && ! in_array($this->_scheme, self::SCHEME_NONHTTP, true)) {
            throw new InvalidConfigException('host не указан');
        }

        // если указан пароль, то должен быть указан логин
        if ($this->_pass !== '' && $this->_user === '') {
            throw new InvalidConfigException('user не указан');
        }

        // если указан логин или порт, то должен быть указан хост
        if (($this->_user !== '' || $this->_port !== 0) && $this->_host === '') {
            throw new InvalidConfigException('host не указан');
        }

        // если указан хост, то путь должен начинаться с /
        if ($this->_host !== '') {
            if ($this->_path === '') {
                $this->_path = '/';
            } elseif ($this->_path[0] !== '/') {
                throw new InvalidConfigException('path должен начинаться с "/"');
            }
        }
    }

    /**
     * Возвращает список аттрибутов модели.
     *
     * @return string[]
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function attributes()
    {
        return ['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'];
    }

    /**
     * {@inheritDoc}
     * @see \yii\base\Arrayable::fields()
     */
    public function fields()
    {
        $fields = $this->attributes();

        return array_combine($fields, $fields);
    }

    /**
     * {@inheritDoc}
     * @see \yii\base\Arrayable::extraFields()
     */
    public function extraFields()
    {
        return ['hostInfo', 'requestUri', 'isAbsolute'];
    }

    /**
     * Возвращает схему
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getScheme()
    {
        return $this->_scheme === '' && $this->_port !== 0 ? static::schemeByPort($this->_port) : $this->_scheme;
    }

    /**
     * Устанавливает схему
     *
     * @param string $scheme
     * @return static
     * @noinspection PhpUnused
     */
    public function setScheme(string $scheme)
    {
        $this->_scheme = $scheme !== '' ? strtolower($scheme) : '';

        return $this;
    }

    /**
     * Возвращает логин
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Устанавливает пользователя
     *
     * @param string $user
     * @return static
     * @noinspection PhpUnused
     */
    public function setUser(string $user)
    {
        $this->_user = $user;

        return $this;
    }

    /**
     * Возвращает пароль
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getPass()
    {
        return $this->_pass;
    }

    /**
     * Устанавливает пароль
     *
     * @param string $pass
     * @return static
     * @noinspection PhpUnused
     */
    public function setPass(string $pass)
    {
        $this->_pass = $pass;

        return $this;
    }

    /**
     * Возвращает хост
     *
     * @param bool $toAscii преобразовать из UTF-8 в ASCII IDN
     * @return string хост
     */
    public function getHost(bool $toAscii = false)
    {
        return $this->_host !== '' && $toAscii ? Url::idnToAscii($this->_host) : $this->_host;
    }

    /**
     * Устанавливает хост
     *
     * @param string $host
     * @return static
     * @throws InvalidArgumentException
     * @noinspection PhpUnused
     */
    public function setHost(string $host)
    {
        $this->_host = $host !== '' ? Url::normalizeHost($host) : '';

        return $this;
    }

    /**
     * Возвращает порт
     *
     * @return int порт
     * @noinspection PhpUnused
     */
    public function getPort()
    {
        return $this->_port === 0 && $this->_scheme !== '' ? static::portByScheme($this->_scheme) : $this->_port;
    }

    /**
     * Устанавливает порт
     *
     * @param int $port
     * @return static
     * @throws InvalidArgumentException
     * @noinspection PhpUnused
     */
    public function setPort(int $port)
    {
        if ($port < 0 || $port > 65535) {
            throw new InvalidArgumentException('port');
        }

        $this->_port = $port;

        return $this;
    }

    /**
     * Возвращает путь
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_host !== '' && $this->_path === '' ? '/' : $this->_path;
    }

    /**
     * Устанавливает путь.
     *
     * @param string $path
     * @return static
     * @noinspection PhpUnused
     */
    public function setPath(string $path)
    {
        if ($this->_host !== '' && $path === '') {
            $path = '/';
        }

        $this->_path = $path !== '' && $path !== '/' ? Url::normalizePath($path) : $path;

        return $this;
    }

    /**
     * Возвращает параметры запроса.
     *
     * @param bool $toString преобразовать в строку
     * @return array|string параметры запроса
     */
    public function getQuery(bool $toString = false)
    {
        return ! empty($this->_query) && $toString ? Url::buildQuery($this->_query) : $this->_query;
    }

    /**
     * Устанавливает параметры запроса
     *
     * @param array|string $query
     * @return static
     * @noinspection PhpUnused
     */
    public function setQuery($query)
    {
        $this->_query = empty($query) ? [] : Url::normalizeQuery($query);

        return $this;
    }

    /**
     * Возвращает фрагмент
     *
     * @return string фрагмент
     * @noinspection PhpUnused
     */
    public function getFragment()
    {
        return $this->_fragment;
    }

    /**
     * Устанавливает фрагмент
     *
     * @param string $fragment
     * @return static
     * @noinspection PhpUnused
     */
    public function setFragment(string $fragment)
    {
        $this->_fragment = $fragment !== '' ? ltrim($fragment, '#') : '';

        return $this;
    }

    /**
     * Возвращает hostinfo: user:pass@host:port часть URL
     *
     * @param bool $toAscii преобразовать домен из UTF-8 в ASCII
     * @return string
     */
    public function getHostInfo(bool $toAscii = false)
    {
        $hostInfo = '';

        if ($this->host !== '') {
            if ($this->user !== '') {
                $hostInfo .= $this->user;
                if ($this->pass !== '') {
                    $hostInfo .= ':' . $this->pass;
                }

                $hostInfo .= '@';
            }

            $hostInfo .= $this->getHost($toAscii);

            if ($this->port !== 0) {
                // исключаем добавление стандартных портов
                $port = static::portByScheme($this->_scheme);

                if ($port !== $this->port) {
                    $hostInfo .= ':' . $this->port;
                }
            }
        }

        return $hostInfo;
    }

    /**
     * Возвращает строку запроса
     *
     * @param bool $fragment добавить #fragment
     * @return string|null путь?параметры#фрагмент
     */
    public function getRequestUri(bool $fragment = true)
    {
        $uri = '';
        if ($this->path !== '') {
            $uri .= $this->path;
        }

        // добавляем параметры
        if (! empty($this->query)) {
            $uri .= '?' . $this->getQuery(true);
        }

        if ($fragment && $this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    /**
     * Возвращает строковое представление
     *
     * @param bool $toAscii преобразовать домен из UTF в ASCII IDN
     * @return string полный url
     */
    public function toString(bool $toAscii = false)
    {
        $url = '';

        if ($this->scheme !== '') {
            $url .= $this->scheme . ':';
        }

        $hostInfo = $this->getHostInfo($toAscii);

        if ($hostInfo !== '') {
            if (! in_array($this->scheme, self::SCHEME_NONHTTP, true)) {
                $url .= '//';
            }

            $url .= $hostInfo;
        }

        $requestUri = $this->getRequestUri();
        if ($this->path === '/') {
            $requestUri = ltrim($requestUri, '/');
        }

        $url .= $requestUri;

        return $url;
    }

    /**
     * Возвращает строковое представление.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->toString();
        } catch (Throwable $ex) {
            Yii::error($ex, __METHOD__);
        }

        return '';
    }

    /**
     * Возвращает признак абсолютной ссылки
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function getIsAbsolute()
    {
        return $this->_scheme !== '';
    }

    /**
     * Возвращает абсолютный URL по базовому
     *
     * @param static $base базовый абсолютный URL
     * @return static полный URL
     */
    public function toAbsolute(UrlInfo $base)
    {
        if ($this->isAbsolute) {
            return clone $this;
        }

        if (! $base->isAbsolute) {
            throw new InvalidArgumentException('base не абсолютный Url');
        }

        // клонируем полную ссылку для перезаписи
        $full = clone $base;

        // определяем начало перезаписи
        $start = '';
        if ($this->scheme !== '') {
            $start = 'scheme';
        } elseif ($this->hostInfo !== '') {
            $start = 'hostinfo';
        } elseif ($this->path !== '') {
            $start = 'path';
        } elseif (! empty($this->query)) {
            $start = 'query';
        } elseif ($this->fragment !== '') {
            $start = 'fragment';
        }

        // перезаписываем, начиная с заданного компонента
        switch ($start) {
            /** @noinspection PhpMissingBreakStatementInspection */ case 'scheme':
            $full->scheme = $this->scheme;

            /** @noinspection PhpMissingBreakStatementInspection */ case 'hostinfo':
            $full->user = $this->user;
            $full->pass = $this->pass;
            $full->host = $this->host;
            $full->port = $this->port;

            /** @noinspection PhpMissingBreakStatementInspection */ case 'path':
            if ($base->path === '' || mb_strpos($this->path, '/') === 0) {
                // если базовый пустой или относительный путь полный, то переписываем весь путь
                $full->path = '/' . ltrim($this->path, '/');
            } elseif (mb_substr($base->path, - 1, 1) === '/') {
                // если базовый заканчивается на '/', то добавляем относительный
                $full->path .= $this->path;
            } else {
                // удаляем последний компонент из базового
                $path = preg_split('~[/]+~um', $base->path, - 1, PREG_SPLIT_NO_EMPTY);
                if (! empty($path)) {
                    array_pop($path);
                }

                // добавляем относительный путь
                $path[] = $this->path;

                $full->path = '/' . implode('/', $path);
            }

            /** @noinspection PhpMissingBreakStatementInspection */ case 'query':
            $full->query = $this->query;

            case 'fragment':
                $full->fragment = $this->fragment;
        }

        return $full;
    }

    /**
     * Возвращает поддомен домена.
     * Пример:
     * "test.mail.ru", "mail.ru" => "test"
     * "mail.ru", "mail.ru" => ""
     * "test.mail.ru", "yandex.ru" => false
     *
     * @param string $parent родительский
     * @return string|false string - имя поддомена,
     *         false - если $domain не является поддоменом родительского
     */
    public function getSubdomain(string $parent)
    {
        $parent = trim($parent);
        if (empty($parent)) {
            return false;
        }

        if ($this->host === '') {
            return false;
        }

        return Url::getSubdomain($this->host, $parent);
    }

    /**
     * Проверяет является ли поддоменом $parent
     *
     * @param string $parent родительский домен
     * @return bool true если $domain != $parent и является поддоменом $parent
     * @noinspection PhpUnused
     */
    public function isSubdomain(string $parent)
    {
        if ($this->host === '') {
            return false;
        }

        return ! empty(Url::isSubdomain($this->host, $parent));
    }

    /**
     * Проверяет имеет ли домен взаимоотношение родительский-дочерний с $domain
     *
     * @param string $domain сравниваемый домен
     * @return bool true, если $domain1 == $domain2 или один из них является поддоменом другого
     */
    public function isDomainRelated(string $domain)
    {
        if ($this->host === '') {
            return false;
        }

        return Url::isDomainsRelated($this->host, $domain);
    }

    /**
     * Проверяет является ли сравниваемая ссылка
     * на том же сайте что и данная.
     * Ссылка на том же сайте, если она относительная данной или
     * у нее одинаковые схемы, хосты, либо хост является поддоменом данной.
     *
     * @param static $other базовый url
     * @param array $options опции тестирования
     *        - subdoms - считать поддомены тем же сайтом = false
     *        - subpath - считать только ссылки в заданном пути (на уровень ниже) = false
     * @return bool true если тот же сайт
     */
    public function isSameSite(UrlInfo $other, array $options = [])
    {
        $subdoms = ! empty($options['subdoms']); // разрешать поддомены
        $subpath = ! empty($options['subpath']); // разрешать только подкаталоги в пути

        // достраиваем ссылки друг по другу
        $u1 = $this;
        $u2 = $other;

        // сравниваем схемы
        if ($u1->scheme !== $u2->scheme &&
            (($u1->scheme !== '' && $u2->scheme !== '') || in_array($u1->scheme, self::SCHEME_NONHTTP, true) ||
                in_array($u2->scheme, self::SCHEME_NONHTTP, true))
        ) {
            return false;
        }

        // сравниваем hostInfo
        if ($u1->hostInfo !== '' && $u2->hostInfo !== '') {

            // сравниваем user, pass, port
            if ($u1->user !== $u2->user || $u1->pass !== $u2->pass || $u1->_port !== $u2->_port) {
                return false;
            }

            if ((! $subdoms && $u1->host !== $u2->host) || ($subdoms && ! $u1->isDomainRelated($u2->host))) {
                return false;
            }
        }

        // проверяем путь
        return ! ($subpath && $u1->path !== '' && ($u2->path === '' || mb_strpos($u2->path, $u1->path) !== 0));
    }

    /**
     * Проверяет совпадение маски правила robots.txt с данным URL
     *
     * @param string $mask маска может содержать специальные символы '*' и '$' как в robots.txt
     * @return bool true если совпадает
     * @throws LogicException url не абсолютный
     * @link https://yandex.ru/support/webmaster/controlling-robot/robots-txt.html
     * @noinspection PhpUnused
     */
    public function matchRobotsMask(string $mask)
    {
        $mask = trim($mask);
        if ($mask === '') {
            return false;
        }

        $regex = '~^' . str_replace(['\*', '\$'], ['.*', '$'], preg_quote($mask, '~')) . '~us';

        return (bool)preg_match($regex, $this->getRequestUri());
    }
}
