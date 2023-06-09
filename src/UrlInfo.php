<?php
/*
 * @copyright 2019-2022 OrhanT http://hap.org
 * @author Orhan t <develop@hap.org>
 * @license BSD-3-Clause
 * @version 04.01.22 22:23:15
 */

declare(strict_types = 1);
namespace hap\http;

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
    /** @var array standard services and ports */
    public const SERVICES = [
        'http' => 80,
        'https' => 443,
        'ftp' => 21,
        'ssh' => 22,
        'smb' => 445
    ];

    /** @var array special schemes that do not require a host */
    public const SCHEME_NONHTTP = [
        'javascript',
        'mailto',
        'tel'
    ];

    /** @var ?string scheme */
    private ?string $_scheme = null;

    /** @var ?string user */
    private ?string $_user = null;

    /** @var ?string password */
    private ?string $_pass = null;

    /** @var ?string server (domain in utf8) */
    private ?string $_host = null;

    /** @var ?int port */
    private ?int $_port = null;

    /** @var ?string path */
    private ?string $_path = null;

    /** @var ?array query key => $val */
    private ?array $_query = null;

    /** @var ?string fragment */
    private ?string $_fragment = null;

    /**
     * Constructor
     *
     * @param string|array $urlConfig url or config
     * @throws InvalidArgumentException
     */
    public function __construct(string|array $urlConfig = [])
    {
        // convert from string
        if (is_string($urlConfig)) {
            $config = $urlConfig === '' ? [] : parse_url($urlConfig);
            if ($config === false) {
                throw new InvalidArgumentException('url: ' . $urlConfig);
            }

            $urlConfig = (array)$config;
        } elseif (! is_array($urlConfig)) {
            throw new InvalidArgumentException('unknown url type: ' . gettype($urlConfig));
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

        // if schema is specified then host must be specified
        if ($this->_host === null && $this->_scheme !== null && !
            in_array($this->_scheme, self::SCHEME_NONHTTP, true)) {
            throw new InvalidConfigException('host not specified');
        }

        // if a password is specified, then a login must be specified
        if ($this->_pass !== null && $this->_user === null) {
            throw new InvalidConfigException('user not specified');
        }

        // if a login or port is specified, then the host must be specified
        if (($this->_user !== null || $this->_port !== null) && $this->_host === null) {
            throw new InvalidConfigException('host not specified');
        }

        // if a host is specified, then the path must start with /
        if ($this->_host !== null) {
            if ($this->_path === null) {
                $this->_path = '/';
            } elseif ($this->_path[0] !== '/') {
                throw new InvalidConfigException('path must start with "/"');
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
     * Creates an instance from a string
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
     * Returns schema by port number
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
     * Returns the port number according to the service scheme
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
     * Returns schema
     *
     * @return ?string
     */
    public function getScheme(): ?string
    {
        return $this->_scheme ?? ($this->_port === null ? null : static::schemeByPort($this->_port));
    }

    /**
     * Sets the scheme
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
     * Returns login
     *
     * @return ?string
     */
    public function getUser(): ?string
    {
        return $this->_user;
    }

    /**
     * Sets the user
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
     * Returns the password
     *
     * @return ?string
     */
    public function getPass(): ?string
    {
        return $this->_pass;
    }

    /**
     * Sets a password
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
     * Returns the host
     *
     * @param bool $toAscii convert from UTF-8 to ASCII IDN
     * @return ?string host
     */
    public function getHost(bool $toAscii = false): ?string
    {
        return $this->_host !== null && $toAscii ? Url::idnToAscii($this->_host) : $this->_host;
    }

    /**
     * Sets the host
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
     * Returns the port
     *
     * @return ?int port
     */
    public function getPort(): ?int
    {
        return $this->_port ?? ($this->_scheme === null ? null : static::portByScheme($this->_scheme));
    }

    /**
     * Sets the port
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
     * Returns the path
     *
     * @return ?string
     */
    public function getPath(): ?string
    {
        // if a host is given, then normalize the path
        if ($this->_host !== null) {
            return '/' . ltrim((string)$this->_path, '/');
        }

        return $this->_path;
    }

    /**
     * Sets the path.
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
     * Returns query parameters.
     *
     * @param bool $toString convert to string
     * @return array|string|null request parameters
     */
    public function getQuery(bool $toString = false): array|string|null
    {
        if ($this->_query === null) {
            return null;
        }

        return $toString ? Url::buildQuery($this->_query) : $this->_query;
    }

    /**
     * Sets request parameters
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
     * Returns a fragment
     *
     * @return ?string fragment
     */
    public function getFragment(): ?string
    {
        return $this->_fragment;
    }

    /**
     * Sets fragment
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
     * returns hostInfo: user:pass@host:port часть URL
     *
     * @param bool $toAscii convert domain from UTF-8 в ASCII
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
     * Base URL (scheme :// hostInfo)
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
     * Returns the query string
     *
     * @param bool $fragment добавить #fragment
     * @return string|null path?parameters#fragment
     */
    public function getRequestUri(bool $fragment = true): ?string
    {
        $uri = '';
        if ($this->_path !== null) {
            $uri .= $this->_path;
        }

        // add parameters
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
     * Returns a string representation
     *
     * @param bool $toAscii пconvert domain from UTF в ASCII IDN
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
     * Returns a string representation.
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
     * Returns the attribute of an absolute reference
     *
     * @return bool
     */
    public function getIsAbsolute(): bool
    {
        return $this->_scheme !== null;
    }

    /**
     * Returns the absolute URL based on the base URL
     *
     * @param static $base is the base absolute URL.
     * @return static full URL
     */
    public function toAbsolute(self $base): self
    {
        if ($this->isAbsolute) {
            return clone $this;
        }

        if (! $base->isAbsolute) {
            throw new InvalidArgumentException('base not absolute Url');
        }

        // clone the full link to rewrite
        $full = clone $base;

        // determine the start of rewriting
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

        // overwrite starting from the given component
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
                        // if the base is empty or the relative path is full, then rewrite the entire path
                        $full->path = '/' . ltrim($thisPath, '/');
                    } elseif (mb_substr($basePath, -1, 1) === '/') {
                        // if base ends with '/' then add relative
                        $full->_path .= $thisPath;
                    } else {
                        // remove the last component from the base
                        $path = (array)preg_split('~/+~um', $basePath, -1, PREG_SPLIT_NO_EMPTY);
                        if (! empty($path)) {
                            array_pop($path);
                        }

                        // add relative path
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
     * Returns the subdomain of a domain.
     * Example:
     * "test.mail.ru", "mail.ru" => "test"
     * "mail.ru", "mail.ru" => ""
     * "test.mail.ru", "yandex.ru" => false
     *
     * @param string $parent parent
     * @return ?string string - subdomain name,
     *         null - if $domain is not a subdomain of the parent
     */
    public function getSubdomain(string $parent): ?string
    {
        return $parent === '' || $this->_host === null ? null :
            Url::getSubdomain($this->_host, $parent);
    }

    /**
     * Checks if it is a subdomain $parent
     *
     * @param string $parent parent domain
     * @return bool true if $domain != $parent and is a subdomain of $parent
     */
    public function isSubdomain(string $parent): bool
    {
        if ($this->_host === null) {
            return false;
        }

        return ! empty(Url::isSubdomain($this->_host, $parent));
    }

    /**
     * Checks if the domain has a parent-child relationship with $domain
     *
     * @param string $domain comparison domain
     * @return bool true, if $domain1 == $domain2 or one of them is a subdomain of the other
     */
    public function isDomainRelated(string $domain): bool
    {
        if ($this->_host === null) {
            return false;
        }

        return Url::isDomainsRelated($this->_host, $domain);
    }

    /**
     * Checks if the reference being compared is
     * on the same site as this one.
     * Link on the same site, if it is relative to this or
     * it has the same schemes, hosts, or the host is a subdomain of this one.
     *
     * @param static $other base url
     * @param array $options test options
     *        - subdoms - consider subdomains as the same site= false
     *        - subpath - count only links in the given path (one level down) = false
     * @return bool true if the same site
     */
    public function isSameSite(self $other, array $options = []): bool
    {
        $subdoms = ! empty($options['subdoms']); // allow subdomains
        $subpath = ! empty($options['subpath']); // allow only subdirectories in the path

        // linking links to each other
        $u1 = $this;
        $u2 = $other;

        // compare schemes
        if ($u1->_scheme !== $u2->_scheme && (
                ($u1->_scheme !== null && $u2->_scheme !== null) ||
                ($u1->_scheme !== null && in_array($u1->_scheme, self::SCHEME_NONHTTP, true)) ||
                ($u2->_scheme !== null && in_array($u2->_scheme, self::SCHEME_NONHTTP, true))
            )) {
            return false;
        }

        // compare hostInfo
        if ($u1->_host !== null && $u2->_host !== null) {
            if ($u1->_user !== $u2->_user || $u1->_pass !== $u2->_pass || $u1->_port !== $u2->_port ||
                (! $subdoms && $u1->_host !== $u2->_host) || ($subdoms && ! $u1->isDomainRelated($u2->_host))) {
                return false;
            }
        }

        // checking the path
        return ! (
            $subpath && $u1->_path !== null &&
            ($u2->_path === null || ! str_starts_with($u2->_path, $u1->_path))
        );
    }

    /**
     * Checks if the mask of the robots.txt rule matches the given URL
     *
     * @param string $mask mask can contain special characters '*' and '$' as in robots.txt
     * @return bool true if it matches
     * @throws LogicException url not absolute
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
