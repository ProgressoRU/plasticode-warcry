<?php

use Respect\Validation\Validator as v;

$container['logger'] = function($c) use ($settings) {
    $logger = new \Monolog\Logger($settings['logger']['name']);

    $logger->pushProcessor(function($record) use ($c) {
    	$user = $c->auth->getUser();
    	if ($user) {
	    	$record['extra']['user'] = $c->auth->userString();
    	}
	    
	    $token = $c->auth->getToken();
	    if ($token) {
	    	$record['extra']['token'] = $c->auth->tokenString();
	    }
	
	    return $record;
	});

    $logger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . $settings['logger']['path'], \Monolog\Logger::DEBUG));

    return $logger;
};

$container['auth'] = function($c) {
	return new \Plasticode\Auth\Auth($c);
};

$container['captcha'] = function($c) {
	return new \Plasticode\Auth\Captcha($c, [
		'ард' => [ 'ярд' ],
		// .. your rules here. mine are mine ;)
	]);
};

$container['access'] = function($c) {
	return new \Plasticode\Auth\Access($c);
};

$container['gallery'] = function($c) {
	$settings = [
		'base_dir' => __DIR__,
		'fields' => [
			'picture_type' => 'picture_type',
			'thumb_type' => 'thumb_type',
		],
		'folders' => [
			'picture' => [
				'storage' => 'gallery_pictures',
				'public' => 'gallery_pictures_public',
			],
			'thumb' => [
				'storage' => 'gallery_thumbs',
				'public' => 'gallery_thumbs_public',
			],
		],
	];

	return new \Plasticode\Gallery\Gallery($c, $settings);
};

$container['comics'] = function($c) {
	$settings = [
		'base_dir' => __DIR__,
		'fields' => [
			'picture_type' => 'type',
			'thumb_type' => 'type',
		],
		'folders' => [
			'picture' => [
				'storage' => 'comics_pages',
				'public' => 'comics_pages_public',
			],
			'thumb' => [
				'storage' => 'comics_thumbs',
				'public' => 'comics_thumbs_public',
			],
		],
		'thumb_height' => $this->getSettings('comics.thumb_height'),
	];

	return new \Plasticode\Gallery\Comics($c, $settings);
};

$container['generatorResolver'] = function($c) {
	return new \Plasticode\Generators\GeneratorResolver($c, [ '\\App\\Generators' ]);
};

$container['cases'] = function($c) {
	$cases = new \Plasticode\Util\Cases;
	$cases->yo = false;
	
	return $cases;
};

$container['view'] = function($c) use ($settings) {
    $tws = $settings['view'];

	$path = $tws['templates_path'];
	$path = is_array($path) ? $path : [ $path ];

	$templatesPath = array_map(function($p) {
		return __DIR__ . $p;
	}, $path);

	$cachePath = $tws['cache_path'];
	if ($cachePath) {
		$cachePath = __DIR__ . $cachePath;
	}

	$view = new \Slim\Views\Twig($templatesPath, [
		'cache' => $cachePath
	]);

	$view->addExtension(new \Slim\Views\TwigExtension($c->router, $c->request->getUri()));
	$view->addExtension(new \Plasticode\Twig\Extensions\AccessRightsExtension($c));
	
	// set globals
    $globals = $settings['view_globals'];
	foreach ($globals as $key => $value) {
		$view[$key] = $value;
	}

	$check = $c->auth->check();
	
	$user = $c->auth->getUser();
	if ($user) {
		$user = $c->builder->buildUser($user);
	}

	$view['auth'] = [
		'check' => $check,
		'user' => $user,
		'role' => $c->auth->getRole(),
		'editor' => $c->auth->isEditor(),
	];
	
	$view['image_types'] = \Plasticode\Gallery\Gallery::buildTypesString();
	
	$view['tables'] = $settings['tables'];
	$view['entities'] = $settings['entities'];
	
	$view['root'] = $settings['root'];
	$view['api'] = $settings['api'];

	if (isset($settings['auth_token_key'])) {
		$view['auth_token_key'] = $settings['auth_token_key'];
	}

    return $view;
};

$container['cache'] = function($c) {
	return new \Plasticode\Core\Cache();
};

$container['session'] = function($c) use ($settings) {
    $root = $settings['root'];
    
	$name = 'sessionContainer' . $root;
	
	return new \Plasticode\Core\Session($name);
};

$container['translator'] = function($c) use ($settings) {
	return new \Plasticode\Core\Translator($settings['localization']);
};

$container['validator'] = function($c) {
	return new \Plasticode\Validation\Validator($c);
};

v::with('Plasticode\\Validation\\Rules\\');
v::with('App\\Validation\\Rules\\');

$container['AuthController'] = function($c) {
	return new \Plasticode\Controllers\Auth\AuthController($c);
};

$container['PasswordController'] = function($c) {
	return new \Plasticode\Controllers\Auth\PasswordController($c);
};

$dbs = $settings['db'];

$container['db'] = function($c) use ($dbs) {
	\ORM::configure("mysql:host={$dbs['host']};dbname={$dbs['database']}");
	\ORM::configure("username", $dbs['user']);
	\ORM::configure("password", $dbs['password']);
	\ORM::configure("driver_options", array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	
	return new \App\Data\Db($c);
};

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection([
	'driver' => 'mysql',
	'host' => $dbs['host'],
	'database' => $dbs['database'],
	'username' => $dbs['user'],
	'password' => $dbs['password'],
	'charset' => 'utf8',
	'collation' => 'utf8_general_ci',
	'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

$container['eloquent'] = function($c) use ($capsule) {
	return $capsule;
};

$container['decorator'] = function($c) {
	return new \App\Core\Decorator($c);
};

$container['builder'] = function($c) {
	return new \App\Core\Builder($c);
};

$container['linker'] = function($c) {
	return new \App\Core\Linker($c);
};

$container['parser'] = function($c) {
	return new \App\Core\Parser($c);
};

$container['newsParser'] = function($c) {
	return new \App\Parsing\NewsParser($c);
};

$container['forumParser'] = function($c) {
	return new \App\Parsing\ForumParser($c);
};

// handlers

$container['notFoundHandler'] = function($c) {
	return new \App\Handlers\NotFoundHandler($c);
};

if ($debug !== true) {
	$container['errorHandler'] = function($c) {
		return new \Plasticode\Handlers\ErrorHandler($c);
	};
}

$container['notAllowedHandler'] = function($c) {
	return new \Plasticode\Handlers\NotAllowedHandler($c);
};

// external

$container['twitch'] = function($c) {
	return new \Plasticode\External\Twitch($c);
};

$container['telegram'] = function($c) {
	return new \Plasticode\External\Telegram($c);
};
