<?php

namespace Session;

use Psr\Http\Message\ResponseInterface;

class Session {

	protected $cookies;

	protected $storage;

	protected $data;

	protected $id;

	protected $options;

	protected $started;

	public function __construct($cookies, $storage, array $options = []) {
		$this->cookies = $cookies;
		$this->storage = $storage;
		$defaults = [
			'name' => 'PHPSESSID',
			'expire' => 0,
			'path' => '',
			'domain' => '',
			'secure' => false,
			'httponly' => false,
		];
		$this->options = array_merge($defaults, $options);
		$this->data = [];
		$this->started = false;
	}

	protected function generateId() {
		return bin2hex(random_bytes(32));
	}

	public function start() {
		if($this->cookies->has($this->options['name'])) {
			$this->id = $this->cookies->get($this->options['name']);
		}
		else {
			$this->id = $this->generateId();
		}

		$this->data = $this->storage->read($this->id);

		$this->started = true;
	}

	public function started() {
		return $this->started;
	}

	public function close(ResponseInterface $response) {
		if( ! $this->started) {
			throw new RuntimeException('Session has not been started');
		}

		$response->withAddedHeader('Set-Cookie', $this->getCookie());
		$this->storage->write($this->id, $this->data);
	}

	protected function getCookie() {
		$pairs = [
			sprintf('%s=%s', $this->options['name'], $this->id),
		];

		if($this->options['expire']) {
			$time = $this->options['expire'] + time();
			$pairs[] = sprintf('expires=%s', date(DATE_RFC2822, $time));
		}

		if($this->options['path']) {
			$pairs[] = sprintf('path=%s', $this->options['path']);
		}

		if($this->options['domain']) {
			$pairs[] = sprintf('domain=%s', $this->options['domain']);
		}

		if($this->options['secure']) {
			$pairs[] = 'secure';
		}

		if($this->options['httponly']) {
			$pairs[] = 'HttpOnly';
		}

		return implode('; ', $pairs);
	}

	public function has($key) {
		return array_key_exists($key, $this->data);
	}

	public function get($key, $default = null) {
		return $this->data[$key] ?: $default;
	}

	public function put($key, $value) {
		$this->data[$key] = $value;

		return $this;
	}

	public function push($key, $value) {
		$this->data[$key][] = $value;

		return $this;
	}

	public function rotate() {
		$this->data['_stash_out'] = $this->data['_stash_in'] ?? [];

		return $this;
	}

	public function getStash($key, $default = null) {
		return $this->data['_stash_out'][$key] ?? $default;
	}

	public function putStash($key, $value) {
		$this->data['_stash_in'][$key] = $value;

		return $this;
	}

}
