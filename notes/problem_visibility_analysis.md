# Problem visibility toggle on statement management page

This note summarizes how the "未公开" (hidden) switch on a problem's statement management page works today.

## Frontend
- The management page renders via `web/app/controllers/problem_statement_manage.php`. It builds a `UOJBlogEditor` instance with `cur_data['is_hidden']` mirroring the current `problems.is_hidden` flag and prints the editor view.
- `web/app/views/blog-editor.php` outputs the form. It includes a checkbox `problem_is_hidden` with a Bootstrap Switch widget. The switch shows "未公开" (danger) when checked and "公开" (primary) when unchecked, labeled as "题目可见性".
- Client behavior is driven by `web/js/blog-editor/blog-editor.js`. When saving, the form is serialized (including the `*_is_hidden` checkbox) and posted via AJAX to the same URL. The toggle state is submitted as `problem_is_hidden` when on.

## Backend
- The editor's server handler in `UOJBlogEditor::receivePostData()` reads the checkbox into `$this->post_data['is_hidden']`, defaulting to `1` when the field is present and `0` otherwise, after CSRF defense and validation.
- On the statement management page, the `$problem_editor->save` callback in `problem_statement_manage.php` persists the visibility choice: it updates `problems.is_hidden`, then synchronizes `submissions.is_hidden` and `hacks.is_hidden` for the same problem to match.

## Net effect
- Toggling the switch and saving will immediately flip the problem between hidden and public, updating related submissions and hacks. The button itself is just the Bootstrap Switch around the `*_is_hidden` checkbox; the actual publish/unpublish happens in the AJAX save handled by `UOJBlogEditor` and the `problem_statement_manage.php` save callback.
