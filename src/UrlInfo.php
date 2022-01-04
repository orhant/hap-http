<?php
/*
 * @copyright 2019-2022 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license BSD-3-Clause
 * @version 04.01.22 22:23:15
 */

declare(strict_types = 1);
namespace dicr\http;

use dicr\helper\Url;
use InvalidArgumentException;
use LogicException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;

use function gettype;
use function in_array;
use function is_array;
use function is_string;
use function mb_substr;

/**
 * Модель ссылки.
 *
 * @property ?string $scheme
 * @property ?string $user
 * @property ?string $pass
 * @property ?string $host
 * @property ?int $port
 * @property ?string $path
 * @property array $query
 * @property ?string $fragment
 *
 * Virtual
 *
 * @property-read ?string $hostInfo user:pass@host:port
 * @property-read ?string $baseUrl scheme://hostInfo
 * @property-read ?string $requestUri /path?query#fragment
 * @property-read bool $isAbsolute признак абсолютной ссылки
 */
class UrlInfo extends Model
{
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

    /** @var ?string схема */
    private ?string $_scheme = null;

    /** @var ?string логин */
    private ?string $_user = null;

    /** @var ?string пароль */
    private ?string $_pass = null;

    /** @var ?string сервер (домен в utf8) */
    private ?string $_host = null;

    /** @var ?int порт */
    private ?int $_port = null;

    /** @var ?string путь */
    private ?string $_path = null;

    /** @var ?array параметры key => $val */
    private ?array $_query = null;

    /** @var ?string фрагмент */
    private ?string $_fragment = null;

    /**
     * Конструктор
     *
     * @param string|array $urlConfig url или конфиг
     * @throws InvalidArgumentException
     */
    public function __construct(string|array $urlConfig = [])
    {
        // конвертируем из строки
        if (is_string($urlConfig)) {
            $config = $urlConfig === '' ? [] : parse_url($urlConfig);
            if ($config === false) {
                throw new InvalidArgumentException('url: ' . $urlConfig);
            }

            $urlConfig = (array)$config;
        } elseif (! is_array($urlConfig)) {
            throw new InvalidArgumentException('неизвестный тип url: ' . gettype($urlConfig));
        }

        parent::__construct($urlConfig);
    }

    /**
     * {@inheritDoc}
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        // если указана схема, то должен быть указан хост
        if ($this->_host === null && $this->_scheme !== null && !
            in_array($this->_scheme, self::SCHEME_NONHTTP, true)) {
            throw new InvalidConfigException('host не указан');
        }

        // если указан пароль, то должен быть указан логин
        if ($this->_pass !== null && $this->_user === null) {
            throw new InvalidConfigException('user не указан');
        }

        // если указан логин или порт, то должен быть указан хост
        if (($this->_user !== null || $this->_port !== null) && $this->_host === null) {
            throw new InvalidConfigException('host не указан');
        }

        // если указан хост, то путь должен начинаться с /
        if ($this->_host !== null) {
            if ($this->_path === null) {
                $this->_path = '/';
            } elseif ($this->_path[0] !== '/') {
                throw new InvalidConfigException('path должен начинаться с "/"');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function attributes(): array
    {
        return ['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'];
    }

    /**
     * @inheritDoc
     * (для Arrayable)
     */
    public function extraFields(): array
    {
        return ['hostInfo', 'requestUri', 'isAbsolute'];
    }

    /**
     * Создает экземпляр из строки
     *
     * @param string $url адрес URL
     * @return ?static
     */
    public static function fromString(string $url): ?self
    {
        $self = null;

        try {
            $self = new static($url);
        } catch (Throwable $ex) {
            Yii::debug($ex, __METHOD__);
        }

        return $self;
    }

    /**
     * Возвращает схему по номеру порта
     *
     * @param int $port
     * @return ?string
     */
    public static function schemeByPort(int $port): ?string
    {
        foreach (self::SERVICES as $scheme => $p) {
            if ($p === $port) {
                return $scheme;
            }
        }

        return null;
    }

    /**
     * Возвращает номер порта по схеме сервиса
     *
     * @param string $scheme
     * @return ?int
     */
    public static function portByScheme(string $scheme): ?int
    {
        foreach (self::SERVICES as $sch => $port) {
            if ($sch === $scheme) {
                return $port;
            }
        }

        return null;
    }

    /**
     * Возвращает схему
     *
     * @return ?string
     */
    public function getScheme(): ?string
    {
        return $this->_scheme ?? ($this->_port === null ? null : static::schemeByPort($this->_port));
    }

    /**
     * Устанавливает схему
     *
     * @param ?string $scheme
     * @return $this
     */
    public function setScheme(?string $scheme): self
    {
        $scheme = (string)$scheme;
        $this->_scheme = $scheme === '' ? null : strtolower($scheme);

        return $this;
    }

    /**
     * Возвращает логин
     *
     * @return ?string
     */
    public function getUser(): ?string
    {
        return $this->_user;
    }

    /**
     * Устанавливает пользователя
     *
     * @param ?string $user
     * @return $this
     */
    public function setUser(?string $user): self
    {
        $user = (string)$user;
        $this->_user = $user === '' ? null : $user;

        return $this;
    }

    /**
     * Возвращает пароль
     *
     * @return ?string
     */
    public function getPass(): ?string
    {
        return $this->_pass;
    }

    /**
     * Устанавливает пароль
     *
     * @param ?string $pass
     * @return $this
     */
    public function setPass(?string $pass): self
    {
        $pass = (string)$pass;
        $this->_pass = $pass === '' ? null : $pass;

        return $this;
    }

    /**
     * Возвращает хост
     *
     * @param bool $toAscii преобразовать из UTF-8 в ASCII IDN
     * @return ?string хост
     */
    public function getHost(bool $toAscii = false): ?string
    {
        return $this->_host !== null && $toAscii ? Url::idnToAscii($this->_host) : $this->_host;
    }

    /**
     * Устанавливает хост
     *
     * @param ?string $host
     * @return $this
     */
    public function setHost(?string $host): self
    {
        $host = (string)$host;
        $this->_host = $host !== '' ? Url::normalizeHost($host) : null;

        return $this;
    }

    /**
     * Возвращает порт
     *
     * @return ?int порт
     */
    public function getPort(): ?int
    {
        return $this->_port ?? ($this->_scheme === null ? null : static::portByScheme($this->_scheme));
    }

    /**
     * Устанавливает порт
     *
     * @param ?int $port
     * @return $this
     */
    public function setPort(?int $port): self
    {
        if ($port !== null && ($port < 0 || $port > 65535)) {
            throw new InvalidArgumentException('port');
        }

        $this->_port = $port;

        return $this;
    }

    /**
     * Возвращает путь
     *
     * @return ?string
     */
    public function getPath(): ?string
    {
        // если задан хост, то нормализуем путь
        if ($this->_host !== null) {
            return '/' . ltrim((string)$this->_path, '/');
        }

        return $this->_path;
    }

    /**
     * Устанавливает путь.
     *
     * @param ?string $path
     * @return $this
     */
    public function setPath(?string $path): self
    {
        $path = Url::normalizePath((string)$path);
        $this->_path = $path === '' ? null : $path;

        return $this;
    }

    /**
     * Возвращает параметры запроса.
     *
     * @param bool $toString преобразовать в строку
     * @return array|string|null параметры запроса
     */
    public function getQuery(bool $toString = false): array|string|null
    {
        if ($this->_query === null) {
            return null;
        }

        return $toString ? Url::buildQuery($this->_query) : $this->_query;
    }

    /**
     * Устанавливает параметры запроса
     *
     * @param array|string|null $query
     * @return $this
     */
    public function setQuery(array|string|null $query): self
    {
        $this->_query = $query === null ? null : (Url::normalizeQuery($query) ?: null);

        return $this;
    }

    /**
     * Возвращает фрагмент
     *
     * @return ?string фрагмент
     */
    public function getFragment(): ?string
    {
        return $this->_fragment;
    }

    /**
     * Устанавливает фрагмент
     *
     * @param ?string $fragment
     * @return $this
     */
    public function setFragment(?string $fragment): self
    {
        $fragment = ltrim((string)$fragment, '#');
        $this->_fragment = $fragment === '' ? null : $fragment;

        return $this;
    }

    /**
     * Возвращает hostInfo: user:pass@host:port часть URL
     *
     * @param bool $toAscii преобразовать домен из UTF-8 в ASCII
     * @return ?string
     */
    public function getHostInfo(bool $toAscii = false): ?string
    {
        $hostInfo = '';

        if ($this->_host !== null) {
            if ($this->_user !== null) {
                $hostInfo .= $this->_user;
                if ($this->_pass !== null) {
                    $hostInfo .= ':' . $this->_pass;
                }

                $hostInfo .= '@';
            }

            $hostInfo .= $this->getHost($toAscii);

            if ($this->_port !== null && $this->_scheme !== null &&
                $this->_port !== static::portByScheme($this->_scheme)) {
                $hostInfo .= ':' . $this->_port;
            }
        }

        return $hostInfo ?: null;
    }

    /**
     * Базовый URL (scheme :// hostInfo)
     *
     * @param bool $toAscii
     * @return string|null
     */
    public function getBaseUrl(bool $toAscii = false): ?string
    {
        $parts = [];

        $scheme = $this->scheme;
        if ($scheme !== null) {
            $parts[] = $scheme . '://';
        }

        $hostInfo = $this->getHostInfo($toAscii);
        if ($hostInfo !== null) {
            $parts[] = $hostInfo;
        }

        return empty($parts) ? null : implode('', $parts);
    }

    /**
     * Возвращает строку запроса
     *
     * @param bool $fragment добавить #fragment
     * @return string|null путь?параметры#фрагмент
     */
    public function getRequestUri(bool $fragment = true): ?string
    {
        $uri = '';
        if ($this->_path !== null) {
            $uri .= $this->_path;
        }

        // добавляем параметры
        $query = (string)$this->getQuery(true);
        if ($query !== '') {
            $uri .= '?' . $query;
        }

        if ($fragment && $this->_fragment !== null) {
            $uri .= '#' . $this->_fragment;
        }

        return $uri ?: null;
    }

    /**
     * Возвращает строковое представление
     *
     * @param bool $toAscii преобразовать домен из UTF в ASCII IDN
     * @return string полный url
     */
    public function toString(bool $toAscii = false): string
    {
        $url = '';

        if ($this->_scheme !== null) {
            $url .= $this->_scheme . ':';
        }

        $hostInfo = $this->getHostInfo($toAscii);
        if ($hostInfo !== null) {
            if ($this->_scheme !== null &&
                ! in_array($this->_scheme, self::SCHEME_NONHTTP, true)) {
                $url .= '//';
            }

            $url .= $hostInfo;
        }

        $requestUri = $this->requestUri;
        if ($this->_path === '/') {
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
    public function __toString(): string
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
     */
    public function getIsAbsolute(): bool
    {
        return $this->_scheme !== null;
    }

    /**
     * Возвращает абсолютный URL по базовому
     *
     * @param static $base базовый абсолютный URL
     * @return static полный URL
     */
    public function toAbsolute(self $base): self
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
        $start = null;
        if ($this->_scheme !== null) {
            $start = 'scheme';
        } elseif ($this->hostInfo !== null) {
            $start = 'hostinfo';
        } elseif ($this->_path !== null) {
            $start = 'path';
        } elseif (! empty($this->_query)) {
            $start = 'query';
        } elseif ($this->_fragment !== null) {
            $start = 'fragment';
        }

        // перезаписываем, начиная с заданного компонента
        switch ($start) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'scheme':
                $full->scheme = $this->scheme;

            /** @noinspection PhpMissingBreakStatementInspection */
            case 'hostinfo':
                $full->user = $this->user;
                $full->pass = $this->pass;
                $full->host = $this->host;
                $full->port = $this->port;

            /** @noinspection PhpMissingBreakStatementInspection */
            case 'path':
                $thisPath = (string)$this->path;

                if ($thisPath !== '') {
                    $basePath = $base->path ?? '/';

                    if ($basePath === '/' || $thisPath[0] === '/') {
                        // если базовый пустой или относительный путь полный, то переписываем весь путь
                        $full->path = '/' . ltrim($thisPath, '/');
                    } elseif (mb_substr($basePath, -1, 1) === '/') {
                        // если базовый заканчивается на '/', то добавляем относительный
                        $full->_path .= $thisPath;
                    } else {
                        // удаляем последний компонент из базового
                        $path = (array)preg_split('~/+~um', $basePath, -1, PREG_SPLIT_NO_EMPTY);
                        if (! empty($path)) {
                            array_pop($path);
                        }

                        // добавляем относительный путь
                        $path[] = $thisPath;

                        $full->path = '/' . ltrim(implode('/', $path), '/');
                    }
                }

            /** @noinspection PhpMissingBreakStatementInspection */
            case 'query':
                $full->query = $this->query;

            /** @noinspection PhpMissingBreakStatementInspection */
            case 'fragment':
                $full->fragment = $this->fragment;

            default:
                break;
        }

        $full->path = Url::normalizePath($full->path);

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
     * @return ?string string - имя поддомена,
     *         null - если $domain не является поддоменом родительского
     */
    public function getSubdomain(string $parent): ?string
    {
        return $parent === '' || $this->_host === null ? null :
            Url::getSubdomain($this->_host, $parent);
    }

    /**
     * Проверяет является ли поддоменом $parent
     *
     * @param string $parent родительский домен
     * @return bool true если $domain != $parent и является поддоменом $parent
     */
    public function isSubdomain(string $parent): bool
    {
        if ($this->_host === null) {
            return false;
        }

        return ! empty(Url::isSubdomain($this->_host, $parent));
    }

    /**
     * Проверяет имеет ли домен взаимоотношение родительский-дочерний с $domain
     *
     * @param string $domain сравниваемый домен
     * @return bool true, если $domain1 == $domain2 или один из них является поддоменом другого
     */
    public function isDomainRelated(string $domain): bool
    {
        if ($this->_host === null) {
            return false;
        }

        return Url::isDomainsRelated($this->_host, $domain);
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
    public function isSameSite(self $other, array $options = []): bool
    {
        $subdoms = ! empty($options['subdoms']); // разрешать поддомены
        $subpath = ! empty($options['subpath']); // разрешать только подкаталоги в пути

        // достраиваем ссылки друг по другу
        $u1 = $this;
        $u2 = $other;

        // сравниваем схемы
        if ($u1->_scheme !== $u2->_scheme && (
                ($u1->_scheme !== null && $u2->_scheme !== null) ||
                ($u1->_scheme !== null && in_array($u1->_scheme, self::SCHEME_NONHTTP, true)) ||
                ($u2->_scheme !== null && in_array($u2->_scheme, self::SCHEME_NONHTTP, true))
            )) {
            return false;
        }

        // сравниваем hostInfo
        if ($u1->_host !== null && $u2->_host !== null) {
            if ($u1->_user !== $u2->_user || $u1->_pass !== $u2->_pass || $u1->_port !== $u2->_port ||
                (! $subdoms && $u1->_host !== $u2->_host) || ($subdoms && ! $u1->isDomainRelated($u2->_host))) {
                return false;
            }
        }

        // проверяем путь
        return ! (
            $subpath && $u1->_path !== null &&
            ($u2->_path === null || ! str_starts_with($u2->_path, $u1->_path))
        );
    }

    /**
     * Проверяет совпадение маски правила robots.txt с данным URL
     *
     * @param string $mask маска может содержать специальные символы '*' и '$' как в robots.txt
     * @return bool true если совпадает
     * @throws LogicException url не абсолютный
     * @link https://yandex.ru/support/webmaster/controlling-robot/robots-txt.html
     */
    public function matchRobotsMask(string $mask): bool
    {
        if ($mask === '') {
            return false;
        }

        $regex =
            '~^' .
            str_replace(['\*', '\$'], ['.*', '$'], preg_quote($mask, '~')) .
            '~us';

        return (bool)preg_match($regex, $this->requestUri);
    }
}
