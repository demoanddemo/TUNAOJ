<?php
requirePHPLib('form');
requirePHPLib('data');

$can_manage_courses = UOJCourse::userCanManage(Auth::user());

if ($can_manage_courses) {
	$new_course_form = new UOJForm('new_course');
	$new_course_form->handle = function () {
		DB::insert([
			"insert into courses",
			DB::bracketed_fields(['title', 'description_md', 'is_hidden']),
			"values",
			DB::tuple(['未命名课程', '', 1]),
		]);
		$course_id = DB::insert_id();
		redirectTo("/course/{$course_id}/manage");
		die();
	};
	$new_course_form->config['submit_container']['class'] = 'text-end';
	$new_course_form->config['submit_button']['class'] = 'btn btn-primary';
	$new_course_form->config['submit_button']['text'] = '创建课程';
	$new_course_form->config['confirm']['smart'] = true;
	$new_course_form->runAtServer();
}

function getCourseTR($info) {
	$course = new UOJCourse($info);
	$html = HTML::tag_begin('tr', ['class' => 'text-center']);
	$html .= HTML::tag('td', [], "#{$course->info['id']}");
	$html .= HTML::tag_begin('td', ['class' => 'text-start']);
	$html .= $course->getLink();
	if ($course->info['is_hidden']) {
		$html .= ' <span class="badge text-bg-danger"><i class="bi bi-eye-slash-fill"></i> ' . UOJLocale::get('hidden') . '</span> ';
	}
	$html .= HTML::tag_end('td');
	$html .= HTML::tag('td', [], $course->info['chapters_count']);
	$html .= HTML::tag_end('tr');
	return $html;
}

$cond = [];
if (!$can_manage_courses) {
	$cond[] = ["is_hidden" => 0];
}

if (empty($cond)) {
	$cond = '1';
}

$header = HTML::tag('tr', [], [
	HTML::tag('th', ['class' => 'text-center', 'style' => 'width:5em'], 'ID'),
	HTML::tag('th', [], '课程'),
	HTML::tag('th', ['class' => 'text-center', 'style' => 'width:7em'], '章节数'),
]);

$pag = new Paginator([
	'col_names' => [
		'courses.*',
		DB::rawvalue('(select count(*) from course_chapters where course_id = courses.id) as chapters_count')
	],
	'table_name' => 'courses',
	'cond' => $cond,
	'tail' => "order by id desc",
	'page_len' => 50,
	'post_filter' => function ($info) {
		return (new UOJCourse($info))->userCanView(Auth::user());
	}
]);
?>

<?php echoUOJPageHeader('课程') ?>

<div class="row">
	<div class="col-lg-9">
		<div class="d-flex justify-content-between">
			<h1>课程</h1>

			<?php if (isset($new_course_form)) : ?>
				<div class="text-end">
					<?php $new_course_form->printHTML(); ?>
				</div>
			<?php endif ?>
		</div>

		<?= $pag->pagination() ?>

		<div class="card my-3">
			<?=
			HTML::responsive_table($header, $pag->get(), [
				'table_attr' => [
					'class' => ['table', 'uoj-table', 'mb-0'],
				],
				'tr' => function ($row, $idx) {
					return getCourseTR($row);
				}
			]);
			?>
		</div>

		<?= $pag->pagination() ?>
	</div>

	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php uojIncludeView('sidebar') ?>
	</aside>
</div>

<?php echoUOJPageFooter() ?>
