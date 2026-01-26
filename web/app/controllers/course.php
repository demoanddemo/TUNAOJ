<?php
requirePHPLib('data');
requirePHPLib('judger');

Auth::check() || redirectToLogin();
UOJCourse::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJCourse::cur()->userCanView(Auth::user(), ['ensure' => true]);

function renderMarkdown($markdown) {
	return HTML::purifier()->purify(HTML::parsedown()->text($markdown));
}

function getListData($list_id) {
	if (!$list_id || !validateUInt($list_id)) {
		return null;
	}
	$list = UOJList::query($list_id);
	if (!$list || !$list->userCanView(Auth::user())) {
		return null;
	}
	$rows = DB::selectAll([
		"select", DB::fields([
			'best_ac_submissions.submission_id as submission_id',
			'problems.*',
		]),
		"from", "problems",
		"left join best_ac_submissions",
		"on", [
			"best_ac_submissions.submitter" => Auth::id(),
			"problems.id" => DB::raw("best_ac_submissions.problem_id"),
		],
		"inner join lists_problems",
		"on", [
			"lists_problems.list_id" => $list->info['id'],
			"lists_problems.problem_id" => DB::raw("problems.id"),
		],
		"where", DB::raw(UOJProblem::sqlForUserCanView(Auth::user())),
		"order by id asc",
	]);
	$accepted = 0;
	foreach ($rows as $row) {
		if (!empty($row['submission_id'])) {
			$accepted++;
		}
	}
	return [
		'list' => $list,
		'total' => count($rows),
		'accepted' => $accepted,
		'problems' => $rows,
	];
}

function getProblemTR($info) {
	$problem = new UOJProblem($info);

	$html = HTML::tag_begin('tr', ['class' => 'text-center']);
	$html .= HTML::tag('td', ['class' => $info['submission_id'] ? 'table-success' : ''], "#{$info['id']}");
	$html .= HTML::tag_begin('td', ['class' => 'text-start align-middle']);
	$html .= $problem->getLink(['with' => 'none']);
	if ($problem->isUserOwnProblem(Auth::user())) {
		$html .= ' <span class="badge text-white bg-info align-middle">' . UOJLocale::get('problems::my problem') . '</span> ';
	}
	if ($info['type'] == 'remote') {
		$html .= ' ' . HTML::tag('a', ['class' => 'badge text-bg-success align-middle', 'href' => '/problems/remote'], '远端评测题');
	}
	if ($info['is_hidden']) {
		$html .= ' <span class="badge text-bg-danger align-middle"><i class="bi bi-eye-slash-fill"></i> ' . UOJLocale::get('hidden') . '</span> ';
	}
	$html .= HTML::tag_end('td');
	$html .= HTML::tag('td', [], $problem->getDifficultyHTML());
	$html .= HTML::tag('td', [], ClickZans::getCntBlock($problem->info['zan']));
	$html .= HTML::tag_end('tr');
	return $html;
}

$problem_header = '<tr>';
$problem_header .= '<th class="text-center" style="width:5em;">ID</th>';
$problem_header .= '<th>' . UOJLocale::get('problems::problem') . '</th>';
$problem_header .= '<th class="text-center" style="width:4em;">' . UOJLocale::get('problems::difficulty') . '</th>';
$problem_header .= '<th class="text-center" style="width:50px;">' . UOJLocale::get('appraisal') . '</th>';
$problem_header .= '</tr>';

$chapters = UOJCourse::cur()->queryChapters();
?>

<?php echoUOJPageHeader(UOJCourse::info('title') . ' - 课程') ?>

<div class="row">
	<div class="col-lg-9">
		<div class="d-flex justify-content-between align-items-start mb-3">
			<div>
				<h1><?= UOJCourse::info('title') ?></h1>
				<?php if (UOJCourse::info('description_md')) : ?>
					<div class="markdown-body">
						<?= renderMarkdown(UOJCourse::info('description_md')) ?>
					</div>
				<?php endif ?>
			</div>
			<?php if (UOJCourse::userCanManage(Auth::user())) : ?>
				<a class="btn btn-sm btn-outline-secondary" href="<?= HTML::url('/course/' . UOJCourse::info('id') . '/manage') ?>">
					管理课程
				</a>
			<?php endif ?>
		</div>

		<?php if (empty($chapters)) : ?>
			<div class="alert alert-info">暂无章节，请管理员在管理页面中添加。</div>
		<?php else : ?>
			<?php foreach ($chapters as $chapter) : ?>
				<?php
				$example_data = getListData($chapter['example_list_id']);
				$practice_data = getListData($chapter['practice_list_id']);
				?>
				<div class="card mb-3">
					<div class="card-header bg-warning-subtle d-flex justify-content-between">
						<strong><?= $chapter['title'] ?></strong>
						<span class="text-muted">章节顺序：<?= (int)$chapter['display_order'] ?></span>
					</div>
					<div class="card-body">
						<?php if ($chapter['description_md']) : ?>
							<div class="markdown-body mb-3">
								<?= renderMarkdown($chapter['description_md']) ?>
							</div>
						<?php endif ?>
						<div class="row g-3">
							<div class="col-md-6">
								<h6 class="text-secondary">例题</h6>
								<?php if ($example_data) : ?>
									<div class="d-flex justify-content-between align-items-center">
										<?= $example_data['list']->getLink(['text' => $example_data['list']->info['title']]) ?>
										<span class="text-muted">
											<?= $example_data['accepted'] ?>/<?= $example_data['total'] ?>
										</span>
									</div>
								<?php else : ?>
									<div class="text-muted">暂无题单</div>
								<?php endif ?>
							</div>
							<div class="col-md-6">
								<h6 class="text-secondary">练习题</h6>
								<?php if ($practice_data) : ?>
									<div class="d-flex justify-content-between align-items-center">
										<?= $practice_data['list']->getLink(['text' => $practice_data['list']->info['title']]) ?>
										<span class="text-muted">
											<?= $practice_data['accepted'] ?>/<?= $practice_data['total'] ?>
										</span>
									</div>
								<?php else : ?>
									<div class="text-muted">暂无题单</div>
								<?php endif ?>
							</div>
						</div>

						<?php if ($example_data || $practice_data) : ?>
							<div class="mt-3">
								<?php if ($example_data) : ?>
									<div class="card mb-3">
										<div class="card-header d-flex justify-content-between align-items-center">
											<span>例题题单：<?= $example_data['list']->getLink(['text' => $example_data['list']->info['title']]) ?></span>
											<span class="text-muted"><?= $example_data['accepted'] ?>/<?= $example_data['total'] ?></span>
										</div>
										<div class="table-responsive">
											<?=
											HTML::responsive_table($problem_header, $example_data['problems'], [
												'table_attr' => [
													'class' => ['table', 'uoj-table', 'mb-0'],
												],
												'tr' => function ($row, $idx) {
													return getProblemTR($row);
												}
											]);
											?>
										</div>
									</div>
								<?php endif ?>
								<?php if ($practice_data) : ?>
									<div class="card mb-3">
										<div class="card-header d-flex justify-content-between align-items-center">
											<span>练习题题单：<?= $practice_data['list']->getLink(['text' => $practice_data['list']->info['title']]) ?></span>
											<span class="text-muted"><?= $practice_data['accepted'] ?>/<?= $practice_data['total'] ?></span>
										</div>
										<div class="table-responsive">
											<?=
											HTML::responsive_table($problem_header, $practice_data['problems'], [
												'table_attr' => [
													'class' => ['table', 'uoj-table', 'mb-0'],
												],
												'tr' => function ($row, $idx) {
													return getProblemTR($row);
												}
											]);
											?>
										</div>
									</div>
								<?php endif ?>
							</div>
						<?php endif ?>
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
