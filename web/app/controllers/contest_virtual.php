<?php
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJContest::cur()->userCanView(Auth::user(), ['ensure' => true, 'allow_virtual' => true]);

if (!UOJContest::cur()->canStartVirtualParticipation(Auth::user())) {
	UOJResponse::message("<h1>虚拟比赛尚不可用</h1><p>比赛未结束，暂时无法开始虚拟比赛。</p>");
}

$virtual_info = UOJContest::cur()->getVirtualParticipationInfo(Auth::user());

$start_form = new UOJForm('start_virtual');
$start_form->handle = function () {
	UOJContest::cur()->startVirtualParticipation(Auth::user());
};
$start_form->config['submit_button']['class'] = 'btn btn-secondary';
$start_form->config['submit_button']['text'] = UOJLocale::get('contests::virtual contest start');
$start_form->config['submit_container']['class'] = 'mt-3';
$start_form->succ_href = UOJContest::cur()->getUri();
$start_form->runAtServer();

?>

<?php echoUOJPageHeader(UOJLocale::get('contests::virtual contest rules') . ' - ' . UOJContest::info('name')) ?>

<div class="card mw-100 mx-auto" style="width: 900px">
	<div class="card-body">
		<h1 class="card-title mb-3">
			<?= UOJLocale::get('contests::virtual contest rules') ?>
		</h1>

		<p>
			<?= UOJLocale::get('contests::virtual contest intro') ?>
		</p>

		<ul>
			<li>比赛时长为 <?= UOJContest::info('last_min') ?> 分钟。</li>
			<li>比赛中允许提交，同一题目按最后一次不是 Compile Error 的提交计分。</li>
			<li>每道题目提交次数没有限制，但两次提交间隔至少为 10 秒。</li>
			<?php if (UOJContest::cur()->basicRule() == 'OI') : ?>
				<li>本场比赛为 OI 赛制，比赛中只显示测样例的结果。</li>
			<?php elseif (UOJContest::cur()->basicRule() == 'IOI') : ?>
				<li>本场比赛为 IOI 赛制，比赛时显示的得分即最终得分。</li>
			<?php elseif (UOJContest::cur()->basicRule() == 'ACM') : ?>
				<li>本场比赛为 ACM 赛制，每次失败提交将带来 20 分钟罚时。</li>
			<?php endif ?>
			<li>比赛过程中不能切换账号，选手之间不能交流或抄袭代码。</li>
		</ul>

<?php if ($virtual_info && $virtual_info['is_active']) : ?>
	<div class="alert alert-success">
		<?= UOJLocale::get('contests::virtual contest in progress') ?>
		<div class="mt-2">
			<span class="countdown" data-rest="<?= $virtual_info['remaining_seconds'] ?>"></span>
			<span class="ms-2 text-muted"><?= UOJLocale::get('contests::virtual contest time remaining') ?></span>
		</div>
	</div>
	<a class="btn btn-secondary" href="<?= UOJContest::cur()->getUri() ?>">
		<?= UOJLocale::get('contests::back to the contest') ?>
	</a>
<?php elseif ($virtual_info) : ?>
			<div class="alert alert-secondary">
				<?= UOJLocale::get('contests::virtual contest finished') ?>
			</div>
		<?php else : ?>
			<p><?= UOJLocale::get('contests::virtual contest start hint') ?></p>
			<?php $start_form->printHTML() ?>
		<?php endif ?>
	</div>
</div>

<?php echoUOJPageFooter() ?>
