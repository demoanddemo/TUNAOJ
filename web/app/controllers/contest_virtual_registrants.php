<?php
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJContest::cur()->userCanView(Auth::user(), ['ensure' => true, 'allow_virtual' => true]);

$contest = UOJContest::info();
$virtual_participants = UOJContest::cur()->queryVirtualParticipants();

?>

<?php echoUOJPageHeader($contest['name'] . ' - ' . UOJLocale::get('contests::virtual contest registrants')) ?>

<div class="card">
	<div class="card-body">
		<h1 class="card-title mb-3">
			<?= UOJLocale::get('contests::virtual contest registrants') ?>
		</h1>
		<div class="table-responsive">
			<table class="table table-striped align-middle">
				<thead>
					<tr>
						<th>选手</th>
						<th>开始时间</th>
						<th>结束时间</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($virtual_participants)) : ?>
						<tr>
							<td colspan="3" class="text-center text-muted">暂无数据</td>
						</tr>
					<?php else : ?>
						<?php foreach ($virtual_participants as $participant) : ?>
							<tr>
								<td><?= UOJUser::getLink($participant['username']) ?></td>
								<td><?= $participant['start_time'] ?></td>
								<td><?= $participant['end_time'] ?></td>
							</tr>
						<?php endforeach ?>
					<?php endif ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php echoUOJPageFooter() ?>
