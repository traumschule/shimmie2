<?php

class PrivMsgTheme extends Themelet {
	//
	// Thread mode
	//
	public function display_threads(Page $page, $threads) {
		global $user;

		$html = "
			<table id='pms' class='zebra sortable'>
				<thead><tr>
					<th style='text-align: left;'>From</th>
					<th style='text-align: right;'>Last Message</th>
				</tr></thead>
				<tbody>";
		$unread = null;
		foreach($threads as $thread) {
			if($thread->unread != $unread) {
				if(!is_null($unread)) {
					$html .= "<tr><td colspan='2'>Read</td></tr>";
				}
				$unread = $thread->unread;
			}
			$h_from = html_escape($thread->them->name);
			$h_date = autodate($thread->last_date);
			$u_thread = make_link("pm/thread/".$thread->them->name);
			$hb = $thread->them->can("hellbanned") ? "hb" : "";
			$html .= "<tr class='$hb'>
			<td style='text-align: left;'><a href='$u_thread'>$h_from</a></td>
			<td style='text-align: right;'>$h_date</td>
			</tr>";
		}
		$html .= "
				</tbody>
			</table>
		";
		$page->add_block(new Block("Threads", $html, "main", 40, "private-messages"));
	}

	public function display_thread(Page $page, User $me, User $them, /*PM[]*/ $pms) {
		$this->display_composer($page, $me, $them, "Re: ".$pm->subject);
		$h_them = html_escape($them->name);
		$page->set_title("Messages with $h_them");
		$page->set_heading("Messages with $h_them");
		$page->add_block(new NavBlock());

		$html = "";
		foreach($pms as $pm) {
			$from_user = User::by_id($pm['from_id']);
			$h_name = html_escape($from_user->name);
			$h_userlink = '<a class="username" href="'.make_link('user/'.$h_name).'">'.$h_name.'</a>';
			$h_avatar = "";
			if(!empty($from_user->email)) {
				$hash = md5(strtolower($from_user->email));
				$cb = date("Y-m-d");
				$h_avatar = "<img src=\"http://www.gravatar.com/avatar/$hash.jpg?cacheBreak=$cb\"><br>";
			}
			$h_timestamp = autodate($pm['sent_date']);
			$html .= "
				<div class='comment'>
					<div class=\"info\">
					$h_avatar
					$h_timestamp
					</div>
					$h_userlink: " . format_text($pm['message']) . "
				</div>
			";
		}
		$page->add_block(new Block(null, $html, "main", 10, "comment-list-user"));
	}

	//
	// Message mode
	//
	public function display_pms(Page $page, $pms) {
		global $user;

		$html = "
			<table id='pms' class='zebra sortable'>
				<thead><tr><th>R?</th><th>Subject</th><th>From</th><th>Date</th><th>Action</th></tr></thead>
				<tbody>";
		foreach($pms as $pm) {
			$h_subject = html_escape($pm->subject);
			if(strlen(trim($h_subject)) == 0) $h_subject = "(No subject)";
			$from = User::by_id($pm->from_id);
			$from_name = $from->name;
			$h_from = html_escape($from_name);
			$from_url = make_link("user/".url_escape($from_name));
			$pm_url = make_link("pm/read/".$pm->id);
			$del_url = make_link("pm/delete");
			$h_date = html_escape($pm->sent_date);
			$readYN = "Y";
			if(!$pm->is_read) {
				$h_subject = "<b>$h_subject</b>";
				$readYN = "N";
			}
			$hb = $from->can("hellbanned") ? "hb" : "";
			$html .= "<tr class='$hb'>
			<td>$readYN</td>
			<td><a href='$pm_url'>$h_subject</a></td>
			<td><a href='$from_url'>$h_from</a></td>
			<td>$h_date</td>
			<td><form action='$del_url' method='POST'>
				<input type='hidden' name='pm_id' value='{$pm->id}'>
				".$user->get_auth_html()."
				<input type='submit' value='Delete'>
			</form></td>
			</tr>";
		}
		$html .= "
				</tbody>
			</table>
		";
		$page->add_block(new Block("Private Messages", $html, "main", 40, "private-messages"));
	}

	public function display_composer(Page $page, User $from, User $to, $subject="") {
		global $user, $config;
		$post_url = make_link("pm/send");
		$h_subject = html_escape($subject);
		$to_id = $to->id;
		$auth = $user->get_auth_html();
		if($config->get_bool("pm_threaded")) {
			$subject_row = "<input type='hidden' name='subject' value='shm_pm'>";
		}
		else {
			$subject_row = "<tr><th>Subject:</th><td><input type='text' name='subject' value='$h_subject'></td></tr>";
		}
		$html = <<<EOD
<form action="$post_url" method="POST">
$auth
<input type="hidden" name="to_id" value="$to_id">
<table style="width: 400px;" class="form">
$subject_row
<tr><td colspan="2"><textarea style="width: 100%" rows="6" name="message"></textarea></td></tr>
<tr><td colspan="2"><input type="submit" value="Send"></td></tr>
</table>
</form>
EOD;
		$page->add_block(new Block("Write a PM", $html, "main", 50));
	}

	public function display_message(Page $page, User $from, User $to, PM $pm) {
		$this->display_composer($page, $to, $from, "Re: ".$pm->subject);
		$page->set_title("Private Message");
		$page->set_heading(html_escape($pm->subject));
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Message from {$from->name}", format_text($pm->message), "main", 10));
	}
}

