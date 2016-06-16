<?php

namespace Anchorcms\Services;

use PDO;
use Doctrine\DBAL\{
	Configuration,
	DriverManager
};

class Installer {

	protected $paths;

	protected $session;

	public function __construct(array $paths, $session) {
		$this->paths = $paths;
		$this->session = $session;
	}

	public function isInstalled(): bool {
		$pattern = sprintf('%s/*.json', $this->paths['config']);
		$files = glob($pattern);

		return count($files) > 0;
	}

	public function installerRunning(): bool {
		return $this->session->started() && $this->session->has('install');
	}

	public function buildDns(array $params) {
		$parts = [];

		if($params['db_driver'] == 'pdo_sqlite') {
			$parts[] = $this->paths['storage'] . '/' . $params['db_path'];
		}

		if($params['db_driver'] == 'pdo_mysql') {
			$parts[] = sprintf('host=%s', $params['db_host']);
			$parts[] = sprintf('port=%s', $params['db_port']);
			$parts[] = sprintf('dbname=%s', $params['db_dbname']);
		}

		return sprintf('%s:%s', substr($params['db_driver'], 4), implode(';', $parts));
	}

	public function connectDatabase(array $params) {
		$dns = $this->buildDns($params);

		return new PDO($dns, $params['db_user'], $params['db_password']);
	}

	public function run(array $input) {
		$input['app_secret'] = bin2hex(random_bytes(32));

		if($input['db_path']) {
			$input['db_path'] = $this->paths['storage'] . '/' . $params['db_path'];
		}

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

		$pattern = sprintf('%s/*.json', dirname($path) . '/config-samples');

		foreach(glob($pattern) as $src) {
			$file = pathinfo($src);
			$dst = sprintf('%s/%s', $path, $file['basename']);
			$contents = file_get_contents($src);
			$params = json_decode($contents, true);

			foreach(array_keys($params) as $key) {
				$var = sprintf('%s_%s', $file['filename'], $key);
				if(array_key_exists($var, $input)) {
					$params[$key] = $input[$var];
				}
			}

			file_put_contents($dst, json_encode($params, JSON_PRETTY_PRINT));
		}
	}

	protected function runSchema(PDO $pdo, array $input) {
		$path = $this->paths['resources'] . '/schema_' . substr($input['db_driver'], 4) . '.sql';
		$schema = file_get_contents($path);

		// replace table prefix
		$schema = str_replace('{prefix}', $input['db_table_prefix'], $schema);

		$pdo->beginTransaction();

		foreach(explode(';', $schema) as $sql) {
			$pdo->exec($sql);
		}

		$pdo->commit();
	}

	protected function setupDatabase(PDO $pdo, array $input) {
		$config = new Configuration();
		$conn = DriverManager::getConnection(['pdo' => $pdo], $config);

		$conn->insert($input['db_table_prefix'].'categories', [
			'title' => 'Uncategorised',
			'slug' => 'uncategorised',
			'description' => 'Ain\'t no category here.',
		]);

		$category = $conn->lastInsertId();

		$conn->insert($input['db_table_prefix'].'users', [
			'username' => $input['account_username'],
			'password' => password_hash($input['account_password'], PASSWORD_DEFAULT, ['cost' => 12]),
			'email' => $input['account_email'],
			'name' => $input['account_username'],
			'bio' => 'The bouse',
			'status' => 'active',
			'role' => 'admin',
			'token' => '',
		]);

		$user = $conn->lastInsertId();

		$conn->insert($input['db_table_prefix'].'pages', [
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

		$page = $conn->lastInsertId();

		$conn->insert($input['db_table_prefix'].'posts', [
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

		$post = $conn->lastInsertId();

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
			$conn->insert($input['db_table_prefix'].'meta', [
				'key' => $key,
				'value' => $value,
			]);
		}
	}

}
