<?php

class UOJCourse {
	use UOJDataTrait;

	public static function query($id) {
		if (!isset($id) || !validateUInt($id)) {
			return null;
		}
		$info = DB::selectFirst([
			"select * from courses",
			"where", ["id" => $id]
		]);
		if (!$info) {
			return null;
		}
		return new UOJCourse($info);
	}

	public static function userCanManage(array $user = null) {
		return isSuperUser($user);
	}

	public function __construct($info) {
		$this->info = $info;
	}

	public function getUri($where = '') {
		return "/course/{$this->info['id']}{$where}";
	}

	public function getLink($cfg = []) {
		$cfg += [
			'where' => '',
			'class' => '',
			'text' => $this->info['title'],
			'with' => 'id',
		];

		return HTML::tag('a', [
			'href' => $this->getUri($cfg['where']),
			'class' => $cfg['class'],
		], $cfg['text']);
	}

	public function userCanView(array $user = null, array $cfg = []) {
		$cfg += ['ensure' => false];

		if ($this->info['is_hidden'] && !self::userCanManage($user)) {
			$cfg['ensure'] && UOJResponse::page404();
			return false;
		}

		return true;
	}

	public function queryChapters() {
		return DB::selectAll([
			"select * from course_chapters",
			"where", ["course_id" => $this->info['id']],
			"order by display_order, id"
		]);
	}
}
