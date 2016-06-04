<?php

error_reporting(-1);

// Make sure this we have a good version of PHP
if( ! defined('PHP_VERSION_ID') || PHP_VERSION_ID < 7) {
	echo 'PHP 7 or later is required';
	return;
}

// Set default timezone to UTC
if( ! ini_get('date.timezone')) {
	date_default_timezone_set('UTC');
}

// Check composer is installed
if(false === is_file(__DIR__ . '/../vendor/autoload.php')) {
	echo 'Composer not installed :(';
	return;
}

require __DIR__ . '/helpers.php';
require __DIR__ . '/../vendor/autoload.php';

$app = new Pimple\Container(require __DIR__ . '/container.php');

$app['errors']->handler(function(DB\SqlException $exception) {
	while(ob_get_level()) ob_end_clean();
	http_response_code(500);
	echo sprintf('<html>
			<head>
				<title>SqlException</title>
				<style>html,body { color: #333; padding: 2rem; font: 1rem/1.5rem sans-serif; }</style>
			</head>
			<body>
				<h1>SqlException</h1>
				<p>%s in %s:%d</p>
				<h3>SQL</h3>
				<pre>%s</pre>
				<h3>Params</h3>
				<pre>%s</pre>
			</body>
		</html>',
		$exception->getMessage(),
		$exception->getFile(),
		$exception->getLine(),
		$exception->getSql(),
		json_encode($exception->getParams())
	);
});

$app['errors']->handler(function(Throwable $exception) {
	while(ob_get_level()) ob_end_clean();
	http_response_code(500);
	echo sprintf('<html>
			<head>
				<title>Uncaught Exception</title>
				<style>html,body { color: #333; padding: 2rem; font: 1rem/1.5rem sans-serif; }</style>
			</head>
			<body>
				<h1>Uncaught Exception</h1>
				<p>%s in %s:%d</p>
				<h3>Stack Trace</h3>
				<pre>%s</pre>
			</body>
		</html>',
		$exception->getMessage(),
		$exception->getFile(),
		$exception->getLine(),
		$exception->getTraceAsString()
	);
});

$app['errors']->register();

/*
if(false === $app['services.installer']->isInstalled() || true === $app['services.installer']->installerRunning()) {
	$app['http.routes']->set(require __DIR__ . '/routes/installer.php');
}
*/

$app['events']->trigger('before_response');

$factory = new Tari\Adapter\Guzzle\Factory;

$server = new Tari\Server($factory);

$server->append(function($request, $frame) {
	$response = $frame->next($request);
	return $response->withHeader('X-Powered-By', 'Tari-PHP');
});

$server->append(function($request, $frame) use($app) {
	$response = $frame->next($request);

	$response = $app['http.kernel']->getResponse($request, $response, function($class) use($app) {
		$name = '\\' . implode('\\', array_map('ucfirst', explode('\\', $class)));
		$controller = new $name;
		$controller->setContainer($app);
		return $controller;
	});

	return $response;
});

$default = function($request) use ($factory) {
	// Default to a 404 NOT FOUND response
	return $factory->createResponse(404, [], 'Not Found');
};

$response = $server->run($app['http.request'], $default);

$app['events']->trigger('after_response');

if($app['session']->started()) {
	$app['session']->rotate()->close();
}

$app['events']->trigger('before_output');

$app['http.kernel']->outputResponse($response);

$app['events']->trigger('after_output');
