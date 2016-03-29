<?php

namespace Mappers;

class Pages extends Mapper {

	protected $primary = 'id';

	protected $name = 'pages';

	public function id($id) {
		return $this->where('id', '=', $id)->fetch();
	}

	public function slug($slug) {
		return $this->where('slug', '=', $slug)->fetch();
	}

	public function menu() {
		return $this->where('status', '=', 'published')
			->where('show_in_menu', '=', '1')
			->sort('menu_order', 'asc')
			->get();
	}

	public function filter(array $input) {
		if($input['status']) {
			$this->where('status', '=', $input['status']);
		}

		if($input['search']) {
			$term = sprintf('%%%s%%', $input['search']);

			$this->whereNested(function($where) use($term) {
				$where('title', 'LIKE', $term)->or('content', 'LIKE', $term);
			});
		}

		return $this;
	}

	public function dropdownOptions(array $options = []) {
		$options[0] = 'None';

		foreach($this->where('status', '=', 'published')->sort('title')->get() as $page) {
			$options[$page->id] = sprintf('%s (%s)', $page->name, $page->title);
		}

		return $options;
	}

}
