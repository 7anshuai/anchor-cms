<?php

namespace Controllers\Admin;

class Fields extends Backend {

	public function getIndex() {
		$input = filter_var_array($_GET, [
			'page' => FILTER_SANITIZE_NUMBER_INT,
		]);

		$total = $this->extend->count();

		$perpage = $this->meta->key('admin_posts_per_page', 10);
		$fields = $this->extend->sort('key', 'asc')->take($perpage);

		if($input['page']) {
			$offset = ($input['page'] - 1) * $perpage;
			$fields->skip($offset);
		}

		$paging = new \Paginator('/admin/fields', $input['page'], $total, $perpage, $input);

		$vars['title'] = 'Custom Fields';
		$vars['fields'] = $fields->get();
		$vars['paging'] = $paging;

		return $this->renderTemplate('main', ['fields/index'], $vars);
	}

	public function getCreate() {
		$form = new \Forms\CustomField(['method' => 'post', 'action' => '/admin/fields/save']);
		$form->init();
		$form->getElement('token')->setValue($this->csrf->token());

		// re-populate submitted data
		$form->setValues($this->session->getFlash('input', []));

		$vars['title'] = 'Creating a new custom field';
		$vars['messages'] = $this->messages->get();
		$vars['form'] = $form;

		return $this->renderTemplate('main', ['fields/create'], $vars);
	}

	public function postSave() {
		$form = new \Forms\CustomField;
		$form->init();

		$input = filter_input_array(INPUT_POST, $form->getFilters());
		$validator = $this->validation->create($input, $form->getRules());

		if(false === $validator->isValid()) {
			$this->messages->error($validator->getMessages());
			$this->session->putFlash('input', $input);
			return $this->response->withHeader('location', '/admin/field/create');
		}

		$id = $this->extend->insert([
			'type' => $input['type'],
			'field' => $input['field'],
			'key' => $input['key'],
			'label' => $input['label'],
			'attributes' => '{}',
		]);

		$this->messages->success('Custom field created');
		return $this->response->withHeader('location', sprintf('/admin/field/%d/edit', $id));
	}

	public function getEdit($request) {
		$id = $request->getAttribute('id');
		$field = $this->extend->where('id', '=', $id)->fetch();

		$form = new \Forms\CustomField([
			'method' => 'post',
			'action' => sprintf('/admin/field/%d/update', $field->id)
		]);
		$form->init();
		$form->getElement('token')->setValue($this->csrf->token());

		// set default values from post
		$form->setValues($field->toArray());

		// re-populate old input
		$form->setValues($this->session->getFlash('input', []));

		$vars['title'] = sprintf('Editing &ldquo;%s&rdquo;', $field->label);
		$vars['messages'] = $this->messages->get();
		$vars['form'] = $form;

		return $this->renderTemplate('main', ['fields/edit'], $vars);
	}

	public function postUpdate($request) {
		$id = $request->getAttribute('id');
		$field = $this->extend->where('id', '=', $id)->fetch();

		$form = new \Forms\CustomField;
		$form->init();

		$input = filter_input_array(INPUT_POST, $form->getFilters());
		$validator = $this->validation->create($input, $form->getRules());

		if(false === $validator->isValid()) {
			$this->messages->error($validator->getMessages());
			$this->sesison->putFlash('input', $input);
			return $this->response->withHeader('location', sprintf('/admin/field/%d/edit', $post->id));
		}

		$this->extend->where('id', '=', $field->id)->update([
			'type' => $input['type'],
			'field' => $input['field'],
			'key' => $input['key'],
			'label' => $input['label'],
		]);

		$this->messages->success('Custom field updated');
		return $this->response->withHeader('location', sprintf('/admin/field/%d/edit', $id));
	}

	public function postDelete() {}

}
