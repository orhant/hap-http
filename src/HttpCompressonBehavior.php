<?php 
namespace dicr\http;

use yii\base\Behavior;
use yii\base\Exception;
use yii\httpclient\Client;
use yii\httpclient\RequestEvent;

/**
 * Включает поддержку компрессии в HTTP.
 * 
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 180505
 */
class HttpCompressionBehavior extends Behavior {
	
	/**
	 * {@inheritDoc}
	 * @see \yii\base\Behavior::events()
	 */
	public function events() {
		return [
			Client::EVENT_BEFORE_SEND => 'beforeSend',
			Client::EVENT_AFTER_SEND => 'afterSend'
		];
	}
	
	/**
	 * Добавляет заголовок поддержки сжатия.
	 * 
	 * @param RequestEvent $event
	 */
	public function beforeSend(RequestEvent $event) {
		$headers = $event->request->headers;
		if (!$headers->has('accept-encoding')) {
			$headers->set('accept-encoding', 'gzip, deflate, compress');
		}
	}

	/**
	 * Распаковывает контент.
	 * 
	 * @param RequestEvent $event
	 * @throws Exception
	 */
	public function afterSend(RequestEvent $event) {
		$response = $event->response;
		$encoding = $response->headers->get('content-encoding');
		if (!empty($encoding)) {
			$content = $response->getContent();
			$ctx = null;
			switch (strtolower($encoding)) {
				case 'deflate': $ctx = @gzinflate($content); break;
				case 'compress': $ctx = @gzuncompress($content); break;
				case 'gzip': $ctx = @gzdecode($content); break;
				default: throw new Exception('unknown encode method: '.$encoding);
			}
			if (!empty($ctx)) $response->setContent($ctx);
		}
	}
}