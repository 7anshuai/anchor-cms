<?php

namespace Forms\Installer;

class L10n extends \Forms\Form {

	public function init() {
		$this->addElement(new \Forms\Elements\Select('lang', [
			'label' => 'Language',
			'options' => ['en_GB' => 'English']
		]));

		$list = \DateTimeZone::listIdentifiers();

		$this->addElement(new \Forms\Elements\Select('timezone', [
			'label' => 'Timezone',
			'options' => array_combine($list, $list),
		]));

		$this->addElement(new \Forms\Elements\Submit('submit', [
			'value' => 'Next',
			'attributes' => ['class' => 'button primary']
		]));
	}


}
