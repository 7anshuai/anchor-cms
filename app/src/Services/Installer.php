<?php

namespace Anchorcms\Services;

class Installer {

	protected $paths;

	protected $session;

	public function __construct(array $paths, $session) {
		$this->paths = $paths;
		$this->session = $session;
	}

	public function isInstalled() {
		$path = $this->paths['config'];

		// check config files
		foreach(['db', 'app', 'mail'] as $file) {
			$dest = $path . sprintf('/%s.php', $file);

			// missing config file, assume not installed
			if(false === is_file($dest)) return false;
		}

		return true;
	}

	public function installerRunning() {
		return $this->session->started() && $this->session->has('install');
	}

	public function buildDns(array $input) {
		$parts = [];

		if($input['driver'] == 'sqlite') {
			$parts['dbname'] = $this->paths['storage'] . '/' . $input['dbname'];

			return sprintf('%s:%s', $input['driver'], implode(';', $parts));
		}

		foreach(['host', 'port', 'dbname'] as $part) {
			if(array_key_exists($part, $input)) {
				$parts[] = sprintf('%s=%s', $part, $input[$part]);
			}
		}

		return sprintf('%s:%s', $input['driver'], implode(';', $parts));
	}

	public function connectDatabase(array $params) {
		$dns = $this->buildDns($params);

		return new \PDO($dns, $params['user'], $params['pass']);
	}

	public function run(array $input) {
		$input['secret'] = bin2hex(random_bytes(32));

		$this->copySampleConfig($input);
		$pdo = $this->connectDatabase($input);
		$this->runSchema($pdo, $input);
		$this->setupDatabase($pdo, $input);
	}

	protected function copySampleConfig(array $input) {
		$path = $this->paths['config'];

		if(false === is_dir($path)) {
			mkdir($path, 0700, true);
		}

		$pattern = sprintf('%s/*.json', $path);

		foreach(glob($pattern) as $src) {
			$dst = sprintf('%s/%s', $path, basename($src));
			$contents = file_get_contents($src);
			$params = json_decode($contents);
			dd($params);
		}
	}

	protected function runSchema(\PDO $pdo, array $input) {
		$path = $this->paths['storage'] . '/schema_' . $input['driver'] . '.sql';
		$schema = file_get_contents($path);

		// replace table prefix
		$schema = str_replace('{prefix}', $input['prefix'], $schema);

		$pdo->beginTransaction();

		foreach(explode(';', $schema) as $sql) {
			$pdo->exec($sql);
		}

		$pdo->commit();
	}

	protected function setupDatabase(\PDO $pdo, array $input) {
		$query = new \DB\Query($pdo);

		$category = $query->table($input['prefix'].'categories')->insert([
			'title' => 'Uncategorised',
			'slug' => 'uncategorised',
			'description' => 'Ain\'t no category here.',
		]);

		$user = $query->table($input['prefix'].'users')->insert([
			'username' => $input['username'],
			'password' => password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]),
			'email' => $input['email'],
			'name' => $input['username'],
			'bio' => 'The bouse',
			'status' => 'active',
			'role' => 'admin',
			'token' => '',
		]);

		$page = $query->table($input['prefix'].'pages')->insert([
			'parent' => 0,
			'slug' => 'posts',
			'name' => 'Posts',
			'title' => 'My posts and thoughts',
			'content' => 'Welcome!',
			'html' => '<p>Welcome!</p>',
			'status' => 'published',
			'redirect' => '',
			'show_in_menu' => 1,
			'menu_order' => 0
		]);

		$post = $query->table($input['prefix'].'posts')->insert([
			'title' => 'Hello World',
			'slug' => 'hello-world',
			'content' => 'This is the first post.',
			'html' => '<p>Hello World!</p>'."\r\n\r\n".'<p>This is the first post.</p>',
			'created' => date('Y-m-d H:i:s'),
			'modified' => date('Y-m-d H:i:s'),
			'published' => date('Y-m-d H:i:s'),
			'author' => $user,
			'category' => $category,
			'status' => 'published',
		]);

		$meta = [
			'home_page' => $page,
			'posts_page' => $page,
			'posts_per_page' => 6,
			'admin_posts_per_page' => 10,
			'comment_notifications' => 0,
			'comment_moderation_keys' => '',
			'sitename' => $input['site_name'],
			'description' => $input['site_description'],
			'theme' => 'default',
		];

		foreach($meta as $key => $value) {
			$query->table($input['prefix'].'meta')->insert(['key' => $key, 'value' => $value]);
		}
	}

}
