<?php
/**
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 06.03.20 19:27:00
 */

/** @noinspection PhpUnused */
declare(strict_types = 1);

namespace dicr\http;

/**
 * User-Agent.
 */
class UserAgent
{
    /** @var string[] Bots and spiders */
    public const BOTS = [
        'AdsBot-Google' => 'AdsBot-Google (+http://www.google.com/adsbot.html)',
        'AdsBot-Google-Mobile' => 'Mozilla/5.0 (Linux; Android 5.0; SM-G920A) AppleWebKit (KHTML, like Gecko) Chrome Mobile Safari (compatible; AdsBot-Google-Mobile;  +http://www.google.com/mobile/adsbot.html)',
        'AhrefsBot' => 'Mozilla/5.0 (compatible; AhrefsBot/6.1; +http://ahrefs.com/robot/)',
        'Baiduspider' => 'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)',
        'Barkrowler' => 'Barkrowler/0.9 (+https://babbar.tech/crawler)',
        'bingbot' => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
        'Clarabot' => 'Mozilla/5.0 (compatible; Clarabot/1.4; +http://www.clarabot.info/bots)',
        'Cliqzbot' => 'Mozilla/5.0 (compatible; Cliqzbot/3.0; +http://cliqz.com/company/cliqzbot)',
        'coccocbot-image' => 'Mozilla/5.0 (compatible; coccocbot-image/1.0; +http://help.coccoc.com/searchengine)',
        'coccocbot-web' => 'Mozilla/5.0 (compatible; coccocbot-web/1.0; +http://help.coccoc.com/searchengine)',
        'DotBot' => 'Mozilla/5.0 (compatible; DotBot/1.1; http://www.opensiteexplorer.org/dotbot, help@moz.com)',
        'facebookexternalhit' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
        'FeedFetcher-Google' => 'FeedFetcher-Google; (+http://www.google.com/feedfetcher.html)',
        'Google-AdWords-Express' => 'Google-AdWords-Express',
        'Google-Ads-Creatives-Assistant' => 'Google-Ads-Creatives-Assistant',
        'Googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'Googlebot-Image' => 'Googlebot-Image/1.0',
        'GoogleDocs' => 'Mozilla/5.0 (compatible; GoogleDocs; apps-spreadsheets; +http://docs.google.com)',
        'HTTP Banner Detection' => 'HTTP Banner Detection (https://security.ipip.net)',
        'Jooblebot' => 'Mozilla/5.0 (compatible; Jooblebot/2.0; Windows NT 6.1; WOW64; +http://jooble.org/jooblebot) AppleWebKit/537.36 (KHTML, like Gecko) Safari/537.36',
        'Konturbot' => 'Mozilla/5.0 (compatible; Konturbot/1.0; +http://kontur.ru; n.ilinykh@skbkontur.ru)',
        'LetsearchBot' => 'Mozilla/5.0 (compatible; LetsearchBot/1.0; +https://letsearch.ru/bots)',
        'LightspeedSystemsCrawler' => 'LightspeedSystemsCrawler Mozilla/5.0 (Windows; U; MSIE 9.0; Windows NT 9.0; en-US)',
        'msnbot' => 'msnbot/2.0b (+http://search.msn.com/msnbot.htm)',
        'MegaIndex' => 'Mozilla/5.0 (compatible; MegaIndex.ru/2.0; +http://megaindex.com/crawler)',
        'Mail.RU_Bot' => 'Mozilla/5.0 (compatible; Linux x86_64; Mail.RU_Bot/2.0; +http://go.mail.ru/help/robots)',
        'Mail.RU_Bot/Fast' => 'Mozilla/5.0 (compatible; Linux x86_64; Mail.RU_Bot/Fast/2.0; +http://go.mail.ru/help/robots)',
        'Mail.RU_Bot/Img' => 'Mozilla/5.0 (compatible; Linux x86_64; Mail.RU_Bot/Img/2.0; +http://go.mail.ru/help/robots)',
        'Mail.RU_Bot/Robots' => 'Mozilla/5.0 (compatible; Linux x86_64; Mail.RU_Bot/Robots/2.0; +http://go.mail.ru/help/robots)',
        'MJ12bot' => 'Mozilla/5.0 (compatible; MJ12bot/v1.4.8; http://mj12bot.com/)',
        'oBot' => 'Mozilla/5.0 (compatible; oBot/2.3.1; http://www.xforce-security.com/crawler/)',
        'openstat.ru' => 'Mozilla/5.0 (compatible; openstat.ru/Bot)',
        'OdklBot' => 'Mozilla/5.0 (compatible; OdklBot/1.0 like Linux; klass@odnoklassniki.ru)',
        'Pinterestbot' => 'Mozilla/5.0 (compatible; Pinterestbot/1.0; +http://www.pinterest.com/bot.html)',
        'SeeGoneBot' => 'Mozilla/5.0 (compatible; SeeGoneBot/0.8; +https://www.seegone.com/bot/)',
        'Sogou web spider' => 'Sogou web spider/4.0(+http://www.sogou.com/docs/help/webmasters.htm#07)',
        'SemrushBot' => 'Mozilla/5.0 (compatible; SemrushBot/6~bl; +http://www.semrush.com/bot.html)',
        'SeznamBot' => 'Mozilla/5.0 (compatible; SeznamBot/3.2; +http://napoveda.seznam.cz/en/seznambot-intro/)',
        'statdom.ru' => 'Mozilla/5.0 (compatible; statdom.ru/Bot; +http://statdom.ru/bot.html)',
        'SMTBot' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.75 Safari/537.36 (compatible; SMTBot/1.0; +http://www.similartech.com/smtbot)',
        'TelegramBot' => 'TelegramBot (like TwitterBot)',
        'Tumblr' => 'Tumblr/14.0.835.186',
        'tracemyfile' => 'Mozilla/5.0 (compatible; tracemyfile/1.0; +bot@tracemyfile.com)',
        'TurnitinBot' => 'TurnitinBot (https://turnitin.com/robot/crawlerinfo.html)',
        'Twitterbot' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/601.2.4 (KHTML, like Gecko) Version/9.0.1 Safari/601.2.4 facebookexternalhit/1.1 Facebot  Twitterbot/1.0',
        'UptimeRobot' => 'Mozilla/5.0+(compatible; UptimeRobot/2.0; http://www.uptimerobot.com/)',
        'WebartexBot' => 'Mozilla/5.0 (compatible; WebartexBot; +http://webartex.ru/)',
        'YaDirectFetcher' => 'Mozilla/5.0 (compatible; YaDirectFetcher/1.0; +http://yandex.com/bots)',
        'YaK' => 'Mozilla/5.0 (compatible; YaK/1.0; http://linkfluence.com/; bot@linkfluence.com)',
        'YandexAccessibilityBot' => 'Mozilla/5.0 (compatible; YandexAccessibilityBot/3.0; +http://yandex.com/bots)',
        'YandexAntivirus' => 'Mozilla/5.0 (compatible; YandexAntivirus/2.0; +http://yandex.com/bots)',
        'YandexBot' => 'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)',
        'YandexDirectDyn' => 'Mozilla/5.0 (compatible; YandexDirectDyn/1.0; +http://yandex.com/bots)',
        'YandexImageResizer' => 'Mozilla/5.0 (compatible; YandexImageResizer/2.0; +http://yandex.com/bots)',
        'YandexImages' => 'Mozilla/5.0 (compatible; YandexImages/3.0; +http://yandex.com/bots)',
        'YandexMarket' => 'Mozilla/5.0 (compatible; YandexMarket/2.0; +http://yandex.com/bots)',
        'YandexMetrika' => 'Mozilla/5.0 (compatible; YandexMetrika/2.0; +http://yandex.com/bots yabs01)',
        'YandexTurbo' => 'Mozilla/5.0 (compatible; YandexTurbo/1.0; +http://yandex.com/bots)',
        'YandexMobileBot' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 8_1 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) Version/8.0 Mobile/12B411 Safari/600.1.4 (compatible; YandexMobileBot/3.0; +http://yandex.com/bots)',
        'ZoominfoBot' => 'ZoominfoBot (zoominfobot at zoominfo dot com)',
    ];

    /** @var string[] browsers */
    public const BROWSERS = [
        'AOL' => 'Mozilla/4.0 (compatible; MSIE 6.0; America Online Browser 1.1; rev1.2; Windows NT 5.1; SV1; .NET CLR 1.1.4322)',
        'Amigo' => 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.5.2526.173 Amigo/47.5.2526.173 MRCHROME SOC Safari/537.36',
        'Avant' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; Avant Browser [avantbrowser.com]; Hotbar 4.4.5.0)',
        'CLR' => 'Mozilla/5.0 (Windows; U; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)',
        'Creative' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows 98; Win 9x 4.90; Creative)',
        'Chrome' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.142 Safari/537.36',
        'Edge' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36 Edge/15.15063',
        'Firefox' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:72.0) Gecko/20100101 Firefox/72.0',
        'MiuiBrowser' => 'Mozilla/5.0 (Linux; U; Android 7.1.2; ru-ru; Redmi 4A Build/N2G47H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/71.0.3578.141 Mobile Safari/537.36 XiaoMi/MiuiBrowser/10.1.2',
        'Netscape' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1) Netscape/8.0.4',
        'Opera' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; ru) Opera 8.01',
        'OPR55' => 'Mozilla/5.0 (Linux; Android 9; SM-G955F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.116 Mobile Safari/537.36 OPR/55.2.2719.50740',
        'OPR66' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36 OPR/66.0.3515.72 (Edition Yx)',
        'Presto' => 'Opera/9.80 (J2ME/MIDP; Opera Mini/5.0.18635/28.2766; U; ru) Presto/2.8.119 Version/11.10',
        'Safari' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
        'SafariMobile' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.4 Mobile/15E148 Safari/604.1',
        'SafariMacintosh' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.5 Safari/605.1.15',
        'SamsungBrowser' => 'Mozilla/5.0 (Linux; Android 9; SAMSUNG SM-A505FN) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/10.2 Chrome/71.0.3578.99 Mobile Safari/537.36',
        'Trident' => 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0;  Trident/5.0)',
        'UCBrowser' => 'Mozilla/5.0(Linux;U;Android 5.1.1;zh-CN;OPPO A33 Build/LMY47V) AppleWebKit/537.36(KHTML,like Gecko) Version/4.0 Chrome/40.0.2214.89 UCBrowser/11.7.0.953 Mobile Safari/537.36',
        'YaBrowser' => 'Mozilla/5.0 (Linux; arm_64; Android 8.1.0; BKK-L21) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.143 YaBrowser/19.7.7.115.00 Mobile Safari/537.36',
        'YaSearchBrowser' => 'Mozilla/5.0 (Linux; Android 9; SM-J701F Build/PPR1.180610.011) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.136 Mobile Safari/537.36 YaApp_Android/9.99 YaSearchBrowser/9.99',
        'Yowser' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.136 YaBrowser/20.2.1.248 Yowser/2.5 Safari/537.36',
    ];

    /** @var string[] прочие агенты */
    public const OTHERS = [
        '1C' => '1C+Enterprise/8.3',
        '2Gis' => '2GIS/DrugDigger',
        'AndroidDownloadManager' => 'AndroidDownloadManager/5.0.2 (Linux; U; Android 5.0.2; Lenovo S90-A Build/LRX22G)',
        'Apache-HttpAsyncClient' => 'Apache-HttpAsyncClient/4.1.4 (Java/1.8.0_202)',
        'Athens15_TD' => 'Athens15_TD/V2 Linux/3.0.13 Android/4.0 Release/02.15.2012 Browser/AppleWebKit534.30 Mobile Safari/534.30 MBBMS/2.2 System/Android 4.0.1;',
        'Boost.Beast' => 'Boost.Beast/248',
        'Dalvik' => 'Dalvik/2.1.0 (Linux; U; Android 7.0; Redmi Note 4 MIUI/V11.0.2.0.NCFMIXM)',
        'Instagram' => 'Mozilla/5.0 (Linux; Android 9; JSN-L21 Build/HONORJSN-L21; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/80.0.3987.87 Mobile Safari/537.36 Instagram 127.0.0.30.121 Android (28/9; 540dpi; 1080x2137; HUAWEI/HONOR; JSN-L21; HWJSN-H; kirin710; ru_RU; 196643814)',
        'Kinza' => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36 Kinza/4.9.1',
        'Microsoft Office' => 'Microsoft Office/16.0 (Windows NT 6.1; Microsoft Word 16.0.4954; Pro)',
        'MRA' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; MRA 4.6 (build 01425); MRSPUTNIK 1, 5, 0, 19 SW)',
        'Viber' => 'Viber/12.3.0.1 CFNetwork/1121.2.2 Darwin/19.3.0',
        'vkShare' => 'Mozilla/5.0 (compatible; vkShare; +http://vk.com/dev/Share)',
        'WhatsApp' => 'WhatsApp/2.20.11 i',
    ];
}
