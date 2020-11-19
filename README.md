# Утилиты HTTP для Yii2

Расширяют стандартные возможности пакета `yii2\httpclient`.

- `CachingClient` - клиент HTTP с поддержкой кэширования. Наследует `yii2\httpclient`;
- `PesistentCookieBehavior` - поддержка сохранения куков между запросами;
- `HttpCompressionBehavior` - поддержка сжатия (gzip, deflate, compress ...);
- `RequestDelayBehavior` - задержка между запросами;
- `DOMDocumentParser` - компонент парсера html в `DOMDocument`;
- `HTMLDocumentParser` - компонент парсера html в `simplehtmldom\HtmlDocument`;
- `UrlInfo` - модель (парсер/строитель) URL;
- `UserAgent` - стандартные значения `User-Agent`.


