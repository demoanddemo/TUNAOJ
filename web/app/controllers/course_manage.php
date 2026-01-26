<?php
requirePHPLib('form');
requirePHPLib('data');

Auth::check() || redirectToLogin();
UOJCourse::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJCourse::userCanManage(Auth::user()) || UOJResponse::page403();

$cur_tab = UOJRequest::get('tab', 'is_string', 'profile');

$tabs_info = [
	'profile' => [
		'name' => '基本信息',
		'url' => '/course/' . UOJCourse::info('id') . '/manage/profile',
	],
	'chapters' => [
		'name' => '章节管理',
		'url' => '/course/' . UOJCourse::info('id') . '/manage/chapters',
	],
];

if (!isset($tabs_info[$cur_tab])) {
	UOJResponse::page404();
}

$list_id_validator = function ($list_id, &$vdata, $key) {
	if ($list_id === '' || $list_id === null) {
		$vdata[$key] = null;
		return '';
	}
	if (!validateUInt($list_id)) {
		return '题单 ID 不合法';
	}
	$list = UOJList::query($list_id);
	if (!$list) {
		return '题单不存在';
	}
	$vdata[$key] = $list->info['id'];
	return '';
};

if ($cur_tab == 'profile') {
	$update_profile_form = new UOJForm('update_profile');
	$update_profile_form->addInput('name', [
		'label' => '标题',
		'default_value' => HTML::unescape(UOJCourse::info('title')),
		'validator_php' => function ($title, &$vdata) {
			if ($title == '') {
				return '标题不能为空';
			}

			if (strlen($title) > 100) {
				return '标题过长';
			}

			$title = HTML::escape($title);
			if ($title === '') {
				return '无效编码';
			}

			$vdata['title'] = $title;

			return '';
		},
	]);
	$update_profile_form->addCheckboxes('is_hidden', [
		'div_class' => 'mt-3',
		'label' => '可见性',
		'label_class' => 'me-3',
		'options' => [
			0 => '公开',
			1 => '隐藏',
		],
		'select_class' => 'd-inline-block',
		'option_div_class' => 'form-check d-inline-block ms-2',
		'default_value' => UOJCourse::info('is_hidden'),
	]);
	$update_profile_form->addMarkdownEditor('description_md', [
		'div_class' => 'mt-3',
		'label' => '课程简介',
		'default_value' => UOJCourse::info('description_md'),
		'validator_php' => function ($description_md, &$vdata) {
			if (strlen($description_md) > 5000) {
				return '课程简介过长';
			}

			$vdata['description_md'] = $description_md;

			return '';
		},
	]);
	$update_profile_form->handle = function ($vdata) {
		DB::update([
			"update courses",
			"set", [
				"title" => $vdata['title'],
				"is_hidden" => $_POST['is_hidden'],
				"description_md" => $vdata['description_md'],
			],
			"where", [
				"id" => UOJCourse::info('id'),
			],
		]);

		dieWithJsonData(['status' => 'success', 'message' => '修改成功']);
	};
	$update_profile_form->setAjaxSubmit(<<<EOD
		function(res) {
			if (res.status === 'success') {
				$('#result-alert')
					.html('课程信息修改成功！')
					.addClass('alert-success')
					.removeClass('alert-danger')
					.show();
			} else {
				$('#result-alert')
					.html('课程信息修改失败。' + (res.message || ''))
					.removeClass('alert-success')
					.addClass('alert-danger')
					.show();
			}

			$(window).scrollTop(0);
		}
	EOD);
	$update_profile_form->config['submit_button']['class'] = 'btn btn-secondary';
	$update_profile_form->config['submit_button']['text'] = '更新';
	$update_profile_form->runAtServer();
} elseif ($cur_tab == 'chapters') {
	$chapters = UOJCourse::cur()->queryChapters();
	$add_chapter_form = new UOJForm('add_chapter');
	$add_chapter_form->addInput('title', [
		'label' => '章节标题',
		'div_class' => 'mb-3',
		'validator_php' => function ($title, &$vdata) {
			if ($title == '') {
				return '章节标题不能为空';
			}
			if (strlen($title) > 100) {
				return '章节标题过长';
			}
			$vdata['title'] = HTML::escape($title);
			return '';
		},
	]);
	$add_chapter_form->addInput('display_order', [
		'label' => '显示顺序',
		'type' => 'number',
		'default_value' => 0,
		'div_class' => 'mb-3',
		'validator_php' => function ($value, &$vdata) {
			if (!is_numeric($value)) {
				return '显示顺序不合法';
			}
			$vdata['display_order'] = (int)$value;
			return '';
		},
	]);
	$add_chapter_form->addMarkdownEditor('description_md', [
		'label' => '章节简介',
		'div_class' => 'mb-3',
		'validator_php' => function ($description_md, &$vdata) {
			if (strlen($description_md) > 3000) {
				return '章节简介过长';
			}
			$vdata['description_md'] = $description_md;
			return '';
		},
	]);
	$add_chapter_form->addInput('example_list_id', [
		'label' => '例题题单 ID',
		'div_class' => 'mb-3',
		'help' => '填写题单 ID 后，本章例题将展示为题单。',
		'validator_php' => function ($list_id, &$vdata) use ($list_id_validator) {
			return $list_id_validator($list_id, $vdata, 'example_list_id');
		},
	]);
	$add_chapter_form->addInput('practice_list_id', [
		'label' => '练习题题单 ID',
		'div_class' => 'mb-3',
		'help' => '填写题单 ID 后，本章练习题将展示为题单。',
		'validator_php' => function ($list_id, &$vdata) use ($list_id_validator) {
			return $list_id_validator($list_id, $vdata, 'practice_list_id');
		},
	]);
	$add_chapter_form->handle = function ($vdata) {
		DB::insert([
			"insert into course_chapters",
			DB::bracketed_fields(['course_id', 'title', 'description_md', 'display_order', 'example_list_id', 'practice_list_id']),
			"values",
			DB::tuple([
				UOJCourse::info('id'),
				$vdata['title'],
				$vdata['description_md'],
				$vdata['display_order'],
				$vdata['example_list_id'],
				$vdata['practice_list_id'],
			]),
		]);
	};
	$add_chapter_form->config['submit_button']['text'] = '添加章节';
	$add_chapter_form->runAtServer();

	$chapter_forms = [];
	foreach ($chapters as $chapter) {
		$form = new UOJForm('update_chapter_' . $chapter['id']);
		$form->addInput('title_' . $chapter['id'], [
			'label' => '章节标题',
			'div_class' => 'mb-2',
			'default_value' => HTML::unescape($chapter['title']),
			'validator_php' => function ($title, &$vdata) use ($chapter) {
				if ($title == '') {
					return '章节标题不能为空';
				}
				if (strlen($title) > 100) {
					return '章节标题过长';
				}
				$vdata['title'] = HTML::escape($title);
				$vdata['chapter_id'] = $chapter['id'];
				return '';
			},
		]);
		$form->addInput('display_order_' . $chapter['id'], [
			'label' => '显示顺序',
			'type' => 'number',
			'div_class' => 'mb-2',
			'default_value' => $chapter['display_order'],
			'validator_php' => function ($value, &$vdata) {
				if (!is_numeric($value)) {
					return '显示顺序不合法';
				}
				$vdata['display_order'] = (int)$value;
				return '';
			},
		]);
		$form->addMarkdownEditor('description_md_' . $chapter['id'], [
			'label' => '章节简介',
			'div_class' => 'mb-2',
			'default_value' => $chapter['description_md'],
			'validator_php' => function ($description_md, &$vdata) {
				if (strlen($description_md) > 3000) {
					return '章节简介过长';
				}
				$vdata['description_md'] = $description_md;
				return '';
			},
		]);
		$form->addInput('example_list_id_' . $chapter['id'], [
			'label' => '例题题单 ID',
			'div_class' => 'mb-2',
			'default_value' => $chapter['example_list_id'] ?: '',
			'validator_php' => function ($list_id, &$vdata) use ($list_id_validator) {
				return $list_id_validator($list_id, $vdata, 'example_list_id');
			},
		]);
		$form->addInput('practice_list_id_' . $chapter['id'], [
			'label' => '练习题题单 ID',
			'div_class' => 'mb-2',
			'default_value' => $chapter['practice_list_id'] ?: '',
			'validator_php' => function ($list_id, &$vdata) use ($list_id_validator) {
				return $list_id_validator($list_id, $vdata, 'practice_list_id');
			},
		]);
		$form->handle = function ($vdata) {
			DB::update([
				"update course_chapters",
				"set", [
					"title" => $vdata['title'],
					"description_md" => $vdata['description_md'],
					"display_order" => $vdata['display_order'],
					"example_list_id" => $vdata['example_list_id'],
					"practice_list_id" => $vdata['practice_list_id'],
				],
				"where", [
					"id" => $vdata['chapter_id'],
				],
			]);
		};
		$form->config['submit_button']['text'] = '更新章节';
		$form->runAtServer();
		$chapter_forms[$chapter['id']] = $form;
	}

	$delete_forms = [];
	foreach ($chapters as $chapter) {
		$delete_form = new UOJForm('delete_chapter_' . $chapter['id']);
		$delete_form->addHidden('chapter_id_' . $chapter['id'], $chapter['id'], function ($id, &$vdata) {
			if (!validateUInt($id)) {
				return '章节不合法';
			}
			$vdata['chapter_id'] = $id;
			return '';
		}, null);
		$delete_form->handle = function ($vdata) {
			DB::delete([
				"delete from course_chapters",
				"where", [
					"id" => $vdata['chapter_id'],
					"course_id" => UOJCourse::info('id'),
				],
			]);
		};
		$delete_form->config['submit_button']['class'] = 'btn btn-outline-danger';
		$delete_form->config['submit_button']['text'] = '删除章节';
		$delete_form->config['confirm']['smart'] = true;
		$delete_form->runAtServer();
		$delete_forms[$chapter['id']] = $delete_form;
	}
}
?>

<?php echoUOJPageHeader(UOJCourse::info('title') . ' - 课程管理') ?>

<div class="row">
	<div class="col-lg-9">
		<ul class="nav nav-tabs mb-3">
			<?php foreach ($tabs_info as $tab => $tab_info) : ?>
				<li class="nav-item">
					<a class="nav-link <?= $cur_tab == $tab ? 'active' : '' ?>" href="<?= HTML::url($tab_info['url']) ?>"><?= $tab_info['name'] ?></a>
				</li>
			<?php endforeach ?>
		</ul>

		<div class="alert alert-danger" id="result-alert" style="display: none"></div>

		<?php if ($cur_tab == 'profile') : ?>
			<div class="card mb-3">
				<div class="card-body">
					<?php $update_profile_form->printHTML() ?>
				</div>
			</div>
		<?php elseif ($cur_tab == 'chapters') : ?>
			<div class="card mb-3">
				<div class="card-header">添加章节</div>
				<div class="card-body">
					<?php $add_chapter_form->printHTML() ?>
				</div>
			</div>

			<?php foreach ($chapters as $chapter) : ?>
				<div class="card mb-3">
					<div class="card-header d-flex justify-content-between align-items-center">
						<strong><?= $chapter['title'] ?></strong>
						<div>
							<?php $delete_forms[$chapter['id']]->printHTML() ?>
						</div>
					</div>
					<div class="card-body">
						<?php $chapter_forms[$chapter['id']]->printHTML() ?>
					</div>
				</div>
			<?php endforeach ?>
		<?php endif ?>
	</div>

	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php uojIncludeView('sidebar') ?>
	</aside>
</div>

<?php echoUOJPageFooter() ?>
