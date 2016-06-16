<?php

namespace Anchorcms\Controllers\Installer;

use Psr\Http\Message\{
	ServerRequestInterface,
	ResponseInterface
};
use Anchorcms\Controllers\AbstractController;

class Install extends AbstractController {

	protected function renderTemplate(ResponseInterface $response, string $layout, string $template, array $vars = []) {
		$vars['messages'] = $this->container['messages']->render();
		$vars['webroot'] = $this->container['url']->to('/');
		$vars['body'] = $this->container['view']->render($template, $vars);

		$output = $this->container['view']->render($layout, $vars);

		$response->getBody()->write($output);
	}

	public function getIndex(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
		return $this->redirect($response, '/l10n');
	}

	public function getL10n(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
		$form = new \Anchorcms\Forms\Installer\L10n([
			'method' => 'post',
			'action' => $this->container['url']->to('/l10n'),
			'autocomplete' => 'off',
		]);
		$form->init();

		// populate from session
		$data = $this->container['session']->get('install', []);
		$form->setValues($data);

		$vars['title'] = 'Installin\' Anchor CMS';
		$vars['form'] = $form;

		$this->renderTemplate($response, 'installer/layout', 'installer/l10n', $vars);

		return $response;
	}

	public function postL10n(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
		$form = new \Anchorcms\Forms\Installer\L10n;

		$input = filter_input_array(INPUT_POST, $form->getFilters());

		$data = $this->container['session']->get('install', []);
		$this->container['session']->put('install', array_merge($data, $input));

		$validator = $this->container['validation']->create($input, $form->getRules());

		if(false === $validator->isValid()) {
			$this->container['messages']->error($validator->getMessages());
			return $this->redirect($response, '/l10n');
		}

		return $this->redirect($response, '/database');
	}

	public function getDatabase(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
		$form = new \Anchorcms\Forms\Installer\Database([
			'method' => 'post',
			'action' => $this->container['url']->to('/database'),
			'autocomplete' => 'off',
		]);
		$form->init();

		// populate from session
		$data = $this->container['session']->get('install', []);
		$form->setValues($data);

		$vars['title'] = 'Installin\' Anchor CMS';
		$vars['form'] = $form;
		$vars['backUrl'] = $this->container['url']->to('/l10n');

		$this->renderTemplate($response, 'installer/layout', 'installer/database', $vars);

		return $response;
	}

	public function postDatabase(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
		$form = new \Anchorcms\Forms\Installer\Database;

		$input = filter_input_array(INPUT_POST, $form->getFilters());

		$data = $this->container['session']->get('install', []);
		$this->container['session']->put('install', array_merge($data, $input));

		$validator = $this->container['validation']->create($input, $form->getRules());

		try {
			$pdo = $this->container['services.installer']->connectDatabase($input);
		}
		catch(\PDOException $e) {
			$validator->setInvalid($e->getMessage());
		}

		if(false === $validator->isValid()) {
			$this->container['messages']->error($validator->getMessages());
			return $this->redirect($response, '/database');
		}

		return $this->redirect($response, '/metadata');
	}

	public function getMetadata(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
		$form = new \Anchorcms\Forms\Installer\Metadata([
			'method' => 'post',
			'action' => $this->container['url']->to('/metadata'),
			'autocomplete' => 'off',
		]);
		$form->init();

		$form->getElement('site_path')->setValue($this->container['url']->to('/'));

		// populate from session
		$data = $this->container['session']->get('install', []);
		$form->setValues($data);

		$vars['title'] = 'Installin\' Anchor CMS';
		$vars['form'] = $form;
		$vars['backUrl'] = $this->container['url']->to('/database');

		$this->renderTemplate($response, 'installer/layout', 'installer/metadata', $vars);

		return $response;
	}

	public function postMetadata(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
		$form = new \Anchorcms\Forms\Installer\Metadata;

		$input = filter_input_array(INPUT_POST, $form->getFilters());

		$data = $this->container['session']->get('install', []);
		$this->container['session']->put('install', array_merge($data, $input));

		$validator = $this->container['validation']->create($input, $form->getRules());

		if(false === $validator->isValid()) {
			$this->container['messages']->error($validator->getMessages());
			return $this->redirect($response, '/metadata');
		}

		return $this->redirect($response, '/account');
	}

	public function getAccount(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
		$form = new \Anchorcms\Forms\Installer\Account([
			'method' => 'post',
			'action' => $this->container['url']->to('/account'),
			'autocomplete' => 'off',
		]);
		$form->init();

		// populate from session
		$data = $this->container['session']->get('install', []);
		$form->setValues($data);

		$vars['title'] = 'Installin\' Anchor CMS';
		$vars['form'] = $form;
		$vars['backUrl'] = $this->container['url']->to('/metadata');

		$this->renderTemplate($response, 'installer/layout', 'installer/account', $vars);

		return $response;
	}

	public function postAccount(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
		$form = new \Anchorcms\Forms\Installer\Account;

		$input = filter_input_array(INPUT_POST, $form->getFilters());

		$data = $this->container['session']->get('install', []);
		$this->container['session']->put('install', array_merge($data, $input));

		$validator = $this->container['validation']->create($input, $form->getRules());

		if(false === $validator->isValid()) {
			$this->container['messages']->error($validator->getMessages());
			return $this->redirect($response, '/account');
		}

		$data = $this->container['session']->get('install', []);
		$this->container['services.installer']->run($data);

		return $this->redirect($response, '/complete');
	}

	public function getComplete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
		$vars['title'] = 'Installin\' Anchor CMS';
		$vars['adminUrl'] = $this->container['url']->to('/admin');
		$vars['siteUrl'] = $this->container['url']->to('/');

		$this->container['session']->remove('install');

		$this->renderTemplate($response, 'installer/layout', 'installer/finished', $vars);

		return $response;
	}

}
