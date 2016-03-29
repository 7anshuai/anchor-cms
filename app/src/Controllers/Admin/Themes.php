<?php

namespace Controllers\Admin;

class Themes extends Backend {

	public function getIndex() {
		$vars['title'] = 'Themes';
		$vars['themes'] = $this->container['services.themes']->getThemes();

		return $this->renderTemplate('layouts/default', 'themes/index', $vars);
	}
}
