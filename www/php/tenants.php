<?php

function send_email($addresses, $subject, $message) {
	if (!empty($addresses)) {
		global $mailutilizator, $mailparola;
		$mail = new PHPMailer();
		$mail->CharSet = 'UTF-8';
		$mail->IsSMTP();
		$mail->Host = 'localhost';
		$mail->SMTPAuth = true;
		$mail->Username = $mailutilizator;
		$mail->Password = $mailparola;
		$mail->SetFrom('office@localhost', 'AdminOnline');
		$mail->Subject = $subject;
		foreach ($addresses as $a) {
			$mail->AddBCC($a);
		}
		$mail->MsgHTML($message);
		$mail->Send();
	}
}

function crash($message) {
	header('HTTP/1.1 500 Internal Server Error');
	die($message);
}

function is_user_read() {
	if (array_search($_SESSION['id_stair'], $_SESSION['user']->stairs) === FALSE) {
		crash('Credentiale invalide.');
	}
}

function is_user_readwrite() {
	if (array_search($_SESSION['id_stair'], $_SESSION['user']->admin_stairs) === FALSE) {
		crash('Credentiale invalide.');
	} else {
		return true;
	}
}

function is_user_readwrite_no_die() {
	return array_search($_SESSION['id_stair'], $_SESSION['user']->admin_stairs) !== FALSE;
}

function get_user($username, $password) {
	global $db;
	$username = $db->real_escape_string($username);
	$password = $db->real_escape_string($password);
	$result = run_query("select u.id, u.admin, p.id id_person, p.name full_name from users u left join users_persons_map upm "
		. "on u.id = upm.id_user left join persons p on upm.id_person = p.id where u.username = '$username' "
		. "and u.password = MD5('$password')");
	if ($result) {
		$user = $result->fetch_object();
	}
	return $user;
}

function get_user_details() {
	$user = $_SESSION['user'];
	$result = run_query("select usm.id_stair from users_stairs_map usm where usm.id_user = {$user->id}");
	$user->admin_stairs = array();
	if ($result) {
		while ($row = $result->fetch_row()) {
			$user->admin_stairs[] = intval($row[0]);
		}
	}
	$result = run_query("select a.id_stair, a.id from users_apartments_map uam join apartments a on uam.id_apartment = a.id where uam.id_user = {$user->id}");
	$user->stairs = $user->admin_stairs;
	$user->apartments = array();
	if ($result) {
		while ($row = $result->fetch_row()) {
			$user->stairs[] = intval($row[0]);
			$user->apartments[] = intval($row[1]);
		}
	}
	$user->stairs = array_unique($user->stairs);
}

function query_to_object($query, $single = FALSE, $json = FALSE) {
	$js = '';
	$result = run_query($query);
	$data = array();
	$finfo = $result->fetch_fields();
	if ($json && !$single) {
		$js .= '[';
	}
	$cfinfo = count($finfo);
	$r = $result->num_rows;
	$cr = 0;
	while ($row = $result->fetch_assoc()) {
		if ($json) {
			$js .= '{';
		}
		$k = 0;
		foreach ($finfo as $i) {
			if ($row[$i->name] != NULL) {
				if ($i->type == 3 || $i->type == 8) {
					$row[$i->name] = intval($row[$i->name]);
				} elseif ($i->type == 4 or $i->type == 5) {
					$row[$i->name] = floatval($row[$i->name]);
				} elseif ($i->type == 1) {
					$row[$i->name] = (bool) $row[$i->name];
				}
				if ($json) {
					$j = json_encode($row[$i->name]);
					$js .= "\"{$i->name}\":$j";
				}
			} else if ($json) {
				$js .= "\"{$i->name}\":null";
			}
			if ($json && $k++ < $cfinfo - 1) {
				$js .= ',';
			}
		}
		if ($json) {
			$js .= '}';
			if ($cr++ < $r - 1) {
				$js .= ',';
			}
		}
		if ($single) {
			return $json ? $js : $row;
		}
		$data[] = $row;
	}
	return $json ? $js . ']' : $data;
}

function query_to_json($query, $single = FALSE) {
	return query_to_object($query, $single, TRUE);
}

function run_query($query, $commit = FALSE) {
	global $db;
	$result = $db->query($query);
	if ($result === FALSE) {
		$message = 'Eroare in baza de date.';
		if ($db->errno == 1062) {
			$message .= ' Ati introdus o inregistrare duplicata.';
		}
		crash($message);
	}
	if ($commit) {
		$db->commit();
	}
	return $result;
}

function grid_to_csv($grid, $filename) {
	header('Content-Type: application/csv');
	header('Content-Disposition: attachement; filename="' . $filename . '.csv";');
	if (!empty($grid)) {
		$f = fopen('php://output', 'w');
		fputcsv($f, array_keys($grid[0]));
		foreach ($grid as $row) {
			fputcsv($f, $row);
		}
	}
}

function get_stairs() {
	$id_user = $_SESSION['user']->id;
	$q = 'select distinct s.id id_stair, s.name name_stair, '
		. 'a.id id_association, a.name name_association, s.errors_column, s.payments_column from stairs s '
		. 'join associations a on s.id_association = a.id left join users_stairs_map usm '
		. 'on s.id = usm.id_stair left join apartments t on s.id = t.id_stair left join '
		. 'users_apartments_map uam on t.id = uam.id_apartment where '
		. "usm.id_user = $id_user or uam.id_user = $id_user";
	return query_to_object($q);
}

function save_stair($id) {
	if ($id == $_SESSION['id_stair']) {
		global $app;
		$json = json_decode($app->request->getBody());
		$errors_column = intval($json->errors_column);
		$payments_column = intval($json->payments_column);
		run_query("update stairs set errors_column = $errors_column, payments_column = $payments_column where id = $id", TRUE);
		echo json_encode((object) array('id' => $id));
	}
}

function get_series() {
	if (array_key_exists('date_upkeep', $_SESSION) && !empty($_SESSION['date_upkeep'])) {
	echo query_to_json('select ir.id, ir.name, IFNULL(max(pv.number), 0) number from invoice_series ir '
		. 'left join payment_values pv on ir.id = pv.id_invoice_series '
		. "left join upkeeps u on pv.id_upkeep = u.id and u.start_date < FROM_UNIXTIME($_SESSION[date_upkeep]) "
		. "where ir.id_stair = $_SESSION[id_stair] "
		. 'group by ir.id, ir.name order by ir.id desc');
	}
}

function save_series($id) {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$name = $db->real_escape_string($json->name);
	run_query("update invoice_series set name = '$name' where id = $id");
	echo json_encode((object) array('id' => $id));
	$db->commit();
}

function insert_series() {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$name = $db->real_escape_string($json->name);
	run_query("insert into invoice_series (name, id_stair) values ('$name', $_SESSION[id_stair])");
	echo json_encode((object) array('id' => $db->insert_id));
	$db->commit();
}

function delete_series($id) {
	run_query("delete from invoice_series where id = $id", TRUE);
	invalidate_table();
	echo json_encode((object) array('id' => $id));
}

function get_messages() {
	if (array_key_exists('date_upkeep', $_SESSION) && !empty($_SESSION['date_upkeep'])) {
		echo query_to_json('select m.id, m.message, UNIX_TIMESTAMP(m.created_date) created_date, '
			. 'UNIX_TIMESTAMP(m.expire_date) expire_date from messages m '
			. 'join upkeeps u on m.id_upkeep = u.id '
			. "where u.id_stair = $_SESSION[id_stair] order by expire_date desc limit 25");
	}
}

function save_messages($id) {
	global $app;
	$json = json_decode($app->request->getBody());
	$message = $db->real_escape_string($json->message);
	run_query("update messages set message = '$message' where id = $id", true);
	echo json_encode((object) array('id' => $id));
}

function delete_messages($id) {
	run_query("delete from messages where id = $id", true);
	echo json_encode((object) array('id' => $id));
}

function insert_messages() {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$message = $db->real_escape_string($json->message);
	$date = intval($json->created_date);
	$exdate = intval($json->expire_date);
	run_query('insert into messages (id_user, id_upkeep, message, created_date, expire_date) values '
		. "({$_SESSION['user']->id}, $_SESSION[id_upkeep], '$message', FROM_UNIXTIME($date), FROM_UNIXTIME($exdate))");
	echo json_encode((object) array('id' => $db->insert_id));
	$result = run_query('select distinct p.email from persons p join apartments a on p.id = a.id_person '
		. "where a.id_stair = $_SESSION[id_stair] and p.notify = 1");
	if ($result) {
		$a = array();
		while ($row = $result->fetch_row()) {
			if ($row[0]) {
				$a[] = $row[0];
			}
		}
		send_email($a, 'Anunt', $json->message);
	}
	$db->commit();
}

function send_contact() {
	global $app;
	$json = json_decode($app->request->getBody());
	$a = array('contact.aplicatie@gmail.com');
	send_email($a, $json->subject, $json->message);
}

function get_payments() {
	$q = 'select pv.id, pv.id_apartment, pv.value, pv.id_invoice_series, pv.number, '
		. 'a.number apartment_number, UNIX_TIMESTAMP(pv.date) date '
		. "from payment_values pv join apartments a on pv.id_apartment = a.id where pv.id_upkeep = $_SESSION[id_upkeep] ";
	if ($_SESSION['id_apartment']) {
		$q .= "and id_apartment = $_SESSION[id_apartment] ";
	}
	$q .= 'order by date desc, apartment_number asc';
	echo query_to_json($q);
}

function get_payment($id) {
	$result = run_query("select value from payment_values where id = $id");
	if ($result) {
		$row = $result->fetch_object();
		if ($row) {
			return $row->value;
		}
	}
}

function save_payments($id) {
	global $app;
	$json = json_decode($app->request->getBody());
	$id_apartment = intval($json->id_apartment);
	$id_invoice_series = intval($json->id_invoice_series);
	$value = floatval($json->value);
	$number = intval($json->number);
	$date = intval($json->date);
	$new_value = $value - get_payment($id);
	run_query("update payment_values set id_apartment = $id_apartment, "
		. "value = $value, id_invoice_series = $id_invoice_series, "
		. "number = $number, date = FROM_UNIXTIME($date) where id = $id", true);
	invalidate_table();
	echo json_encode((object) array('id' => $id));
}

function insert_payments() {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$id_apartment = intval($json->id_apartment);
	$id_invoice_series = intval($json->id_invoice_series);
	$value = floatval($json->value);
	$number = intval($json->number);
	$date = intval($json->date);
	run_query('insert into payment_values (id_apartment, id_upkeep, '
		. 'value, id_invoice_series, number, date) values '
		. "($id_apartment, $_SESSION[id_upkeep], $value, $id_invoice_series, $number, FROM_UNIXTIME($date))");
	$id = $db->insert_id;
	echo json_encode((object) array('id' => $id));
	$db->commit();
	invalidate_table();
}

function delete_payments($id) {
	run_query("delete from payment_values where id = $id", true);
	invalidate_table();
	echo json_encode((object) array('id' => $id));
}

function get_users() {
	echo query_to_json("select u.id, u.username, p.id id_person, p.name contact, 'password' password, "
			. 'IFNULL(uam.id_apartment, 0) id_apartment from users u left join users_persons_map upm '
			. 'on u.id = upm.id_user left join persons p on upm.id_person = p.id '
			. 'left join users_apartments_map uam on uam.id_user = u.id left join apartments a '
			. 'on uam.id_apartment = a.id left join users_stairs_map usm on u.id = usm.id_user '
			. "where a.id_stair = $_SESSION[id_stair] or usm.id_stair = $_SESSION[id_stair]");
}

function insert_user_access($id, $id_apartment) {
	if ($id_apartment == 0) {
		run_query("insert into users_stairs_map (id_user, id_stair) values ($id, $_SESSION[id_stair])");
	} else {
		run_query("insert into users_apartments_map (id_user, id_apartment) values ($id, $id_apartment)");
	}
}

function save_user($id) {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$username = $db->real_escape_string($json->username);
	$id_person = intval($json->id_person);
	$id_apartment = intval($json->id_apartment);
	run_query("update users set username = '$username' where id = $id");
	run_query("delete from users_apartments_map where id_user = $id");
	run_query("delete from users_stairs_map where id_user = $id");
	run_query("delete from users_persons_map where id_user = $id and id_person in (select id_person from persons where id_stair = $_SESSION[id_stair])");
	run_query("insert into users_persons_map (id_user, id_person) values ($id, $id_person)");
	insert_user_access($id, $id_apartment);
	echo json_encode((object) array('id' => $id));
	$db->commit();
}

function insert_user() {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$username = $db->real_escape_string($json->username);
	$password = $db->real_escape_string($json->password);
	$id_person = intval($json->id_person);
	$id_apartment = intval($json->id_apartment);
	run_query("insert into users (username, password) values ('$username', MD5('$password'))");
	$id = $db->insert_id;
	run_query("insert into users_persons_map (id_user, id_person) values ($id, $id_person)");
	insert_user_access($id, $id_apartment);
	echo json_encode((object) array('id' => $id));
	$db->commit();
}

function delete_user($id) {
	run_query("delete from users where id = $id", TRUE);
	echo json_encode((object) array('id' => $id));
}

function get_user_apartments($id_apartment = NULL) {
	echo query_to_json(get_user_apartments_raw($id_apartment));
}

function get_user_apartments_raw($id_apartment = NULL) {
	$q = 'select a.id, a.number from apartments a join users_apartments_map uam '
		. "on a.id = uam.id_apartment where a.id_stair = $_SESSION[id_stair] and uam.id_user = {$_SESSION['user']->id}";
	if ($id_apartment) {
		$q .= " and a.id = $id_apartment";
	}
	return $q;
}

function get_apartments() {
	echo query_to_json('select a.id, a.number, a.id_person id_person, a.name '
		. 'from apartments a '
		. "where a.id_stair = $_SESSION[id_stair] "
		. 'order by a.number');
}

function save_apartment($id) {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$number = $db->real_escape_string($json->number);
	$id_person = intval($json->id_person);
	$name = $db->real_escape_string($json->name);
	run_query("update apartments set number = '$number', id_person = $id_person, name = '$name' where id = $id");
	echo json_encode((object) array('id' => $id));
	$db->commit();
	invalidate_table();
}

function insert_apartment() {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$number = $db->real_escape_string($json->number);
	$id_person = intval($json->id_person);
	$current = floatval($json->current);
	$pending = floatval($json->pending);
	$penalty = floatval($json->penalty);
	$name = $db->real_escape_string($json->name);
	run_query("insert into apartments (number, id_person, id_stair, current, pending, penalty, name) values ('$number', $id_person, $_SESSION[id_stair], $current, $pending, $penalty, '$name')");
	echo json_encode((object) array('id' => $db->insert_id));
	$db->commit();
	invalidate_table();
}

function delete_apartment($id) {
	run_query("delete from apartments where id = $id", TRUE);
	invalidate_table();
	echo json_encode((object) array('id' => $id));
}

function get_persons() {
	$q = 'select p.id, p.name, '
		. "COALESCE(p.telephone, '') telephone, COALESCE(p.email, '') email, "
		. 'pr.id id_person_role, a.id id_apartment, pj.id id_person_job, p.notify from persons p '
		. 'join person_roles pr on p.id_person_role = pr.id left join apartments a on '
		. 'p.id_apartment = a.id join person_jobs pj on p.id_person_job = pj.id '
		. "where p.id_stair = $_SESSION[id_stair]";
	if (!is_user_readwrite_no_die()) {
		$q .= " and (pj.id in (2, 3, 4) or p.id = {$_SESSION['user']->id_person})";
	}
	$q .= ' order by p.name';
	echo query_to_json($q);
}

function save_person($id) {
	if (!is_user_readwrite_no_die() && intval($id) != $_SESSION['user']->id_person) {
		crash('Credentiale invalide.');
	}
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$name = $db->real_escape_string($json->name);
	$telephone = $db->real_escape_string($json->telephone);
	$email = $db->real_escape_string($json->email);
	$notify = intval($json->notify);
	$id_person_role = intval($json->id_person_role);
	$id_person_job = intval($json->id_person_job);
	$id_apartment = $json->id_apartment == NULL ? 'NULL' : intval($json->id_apartment);
	run_query("update persons set name = '$name', telephone = '$telephone', "
		. "email = '$email', id_person_role = $id_person_role, id_person_job = $id_person_job, "
		. "id_apartment = $id_apartment, notify = $notify where id = $id");
	echo json_encode((object) array('id' => $id));
	$db->commit();
}

function delete_person($id) {
	run_query("delete from persons where id = $id", TRUE);
	echo json_encode((object) array('id' => $id));
}

function insert_person() {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$name = $db->real_escape_string($json->name);
	$telephone = $db->real_escape_string($json->telephone);
	$email = $db->real_escape_string($json->email);
	$notify = intval($json->notify);
	$id_person_role = intval($json->id_person_role);
	$id_apartment = $json->id_apartment == NULL ? 'NULL' : intval($json->id_apartment);
	run_query('insert into persons (name, telephone, email, id_person_role, id_apartment, id_stair, notify) '
		. "values ('$name', '$telephone', '$email', $id_person_role, $id_apartment, $_SESSION[id_stair], $notify)");
	echo json_encode((object) array('id' => $db->insert_id));
	$db->commit();
}

function get_person_roles() {
	echo query_to_json('select id, name from person_roles');
}

function get_person_jobs() {
	echo query_to_json('select id, name from person_jobs');
}

function get_coefficients() {
	echo query_to_json("select id, name, unit from coefficients");
}

function get_expenses() {
	$q = 'select distinct e.id, e.name, e.supplier, ev.value, e.unit, IFNULL(e.title, e.name) title, ';
	if ($_SESSION['id_apartment']) {
		$q .= 'cmv.id_mod_type, ';
	}
	$q .= 'c.id id_coefficient, ev.quantity '
		. 'from expenses e join coefficients c on e.id_coefficient = c.id '
		. "left join expense_values ev on e.id = ev.id_expense and ev.id_upkeep = $_SESSION[id_upkeep] ";
	if ($_SESSION['id_apartment']) {
		$q .= 'left join coefficient_mod_values cmv on e.id = cmv.id_expense and '
			. "cmv.id_apartment = $_SESSION[id_apartment] "
			. "and cmv.id_upkeep = $_SESSION[id_upkeep] ";
	}
	$q .= "where e.id_stair = $_SESSION[id_stair]";
	echo query_to_json($q);
}

function save_expense($id) {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$name = $db->real_escape_string($json->name);
	$supplier = $db->real_escape_string($json->supplier);
	$id_coefficient = intval($json->id_coefficient);
	$unit = $db->real_escape_string($json->unit);
	$title = $db->real_escape_string($json->title);
	if ($title == '') {
		$title = $name;
	}
	run_query("update expenses set name = '$name', supplier = '$supplier', "
		. "unit = '$unit', id_coefficient = $id_coefficient, title = '$title' where id = $id");
	if (property_exists($json, 'value')) {
		$value =  $json->value == NULL ? 'NULL' : floatval($json->value);
		$quantity =  $json->quantity == NULL ? 'NULL' : floatval($json->quantity);
		run_query('insert into expense_values (id_expense, id_upkeep, value, quantity) values '
			. "($id, $_SESSION[id_upkeep], $value, $quantity) on duplicate key update value = $value, quantity = $quantity");
		echo json_encode((object) array('id' => $id));
	}
	$db->commit();
	invalidate_table();
}

function delete_expense($id) {
	run_query("delete from expenses where id = $id", TRUE);
	invalidate_table();
	echo json_encode((object) array('id' => $id));
}

function insert_expense() {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$name = $db->real_escape_string($json->name);
	$supplier = $db->real_escape_string($json->supplier);
	$id_coefficient = intval($json->id_coefficient);
	$unit = $db->real_escape_string($json->unit);
	$title = $db->real_escape_string($json->title);
	if ($title == '') {
		$title = $name;
	}
	run_query('insert into expenses (name, supplier, unit, title, id_coefficient, id_stair) '
		. "values ('$name', '$supplier', '$unit', '$title', $id_coefficient, $_SESSION[id_stair])");
	echo json_encode((object) array('id' => $db->insert_id));
	$db->commit();
	invalidate_table();
}

function get_indexes_raw($json = FALSE, $id_apartment = NULL) {
	if ($json) {
		echo '[';
	} else {
		$r = array();
	}
	if (array_key_exists('date_upkeep', $_SESSION) && !empty($_SESSION['date_upkeep'])) {
		$balance_tree = $_SESSION['table'];
		$q = 'select a.id, a.id_apartment, IFNULL(a.index1, 0) index1, IFNULL(b.index1, 0) index1_old, '
			. 'IFNULL(a.index2, 0) index2, IFNULL(b.index2, 0) index2_old, IFNULL(a.index3, 0) index3, '
			. 'IFNULL(b.index3, 0) index3_old, a.id_expense, b.id_upkeep, e.name expense_name, f.number from '
			. "(select id, id_apartment, id_expense, index1, index2, index3 from indexes where id_upkeep = $_SESSION[id_upkeep]) a "
			. 'left join (select i.id_apartment, i.id_expense, max(u.start_date) start_date from indexes i '
			. 'join upkeeps u on i.id_upkeep = u.id '
			. "where u.id_stair = $_SESSION[id_stair] and u.start_date < FROM_UNIXTIME($_SESSION[date_upkeep]) "
			. 'group by i.id_apartment, i.id_expense) d '
			. 'on a.id_apartment = d.id_apartment and a.id_expense = d.id_expense '
			. 'left join (select i.id_upkeep, u.start_date, i.id_apartment, i.id, i.id_expense, i.index1, i.index2, '
			. 'i.index3 from indexes i left join upkeeps u on i.id_upkeep = u.id) b '
			. 'on d.start_date = b.start_date and d.id_apartment = b.id_apartment and d.id_expense = b.id_expense '
			. 'left join expenses e on a.id_expense = e.id '
			. 'left join apartments f on a.id_apartment = f.id ';
		if ($id_apartment) {
			$q .= "where a.id_apartment = $id_apartment ";
		}
		$q .= 'union '
			. 'select a.id, b.id_apartment, IFNULL(a.index1, 0) index1, IFNULL(b.index1, 0) index1_old, '
			. 'IFNULL(a.index2, 0) index2, IFNULL(b.index2, 0) index2_old, IFNULL(a.index3, 0) index3, '
			. 'IFNULL(b.index3, 0) index3_old, b.id_expense, b.id_upkeep, e.name expense_name, f.number from '
			. '(select i.id_apartment, i.id_expense, max(u.start_date) start_date from indexes i '
			. 'join upkeeps u on i.id_upkeep = u.id '
			. "where u.id_stair = $_SESSION[id_stair] and u.start_date < FROM_UNIXTIME($_SESSION[date_upkeep]) "
			. 'group by i.id_apartment, i.id_expense) d '
			. 'left join (select i.id_upkeep, u.start_date, i.id_apartment, i.id_expense, i.id, i.index1, i.index2, i.index3 from '
			. 'indexes i left join upkeeps u on i.id_upkeep = u.id) b '
			. 'on d.start_date = b.start_date and d.id_apartment = b.id_apartment and d.id_expense = b.id_expense '
			. 'left join (select id, id_apartment, id_expense, index1, index2, index3 from indexes where '
			. "id_upkeep = $_SESSION[id_upkeep]) a on d.id_apartment = a.id_apartment and d.id_expense = a.id_expense "
			. 'left join expenses e on b.id_expense = e.id '
			. 'left join apartments f on b.id_apartment = f.id ';
		if ($id_apartment) {
			$q .= "where d.id_apartment = $id_apartment ";
		}
		$result = run_query($q);
		if ($result) {
			$keys = array_keys($balance_tree);
			$c = count($keys);
			$first = TRUE;
			while ($line = $result->fetch_assoc()) {
				if ($json) {
					if ($first) {
						$first = FALSE;
					} else {
						echo ',';
					}
					echo '{"id":' . ($line['id'] == null ? 'null' : $line['id'])
					. ',"id_apartment":' . $line['id_apartment']
					. ',"index1":' . $line['index1']
					. ',"index2":' . $line['index2']
					. ',"index3":' . $line['index3']
					. ',"index1_old":' . $line['index1_old']
					. ',"index2_old":' . $line['index2_old']
					. ',"index3_old":' . $line['index3_old']
					. ',"id_expense":' . $line['id_expense'];
				}
				$estimated = 0;
				if ($line['id_upkeep']) {
					$ok = FALSE;
					foreach ($balance_tree as $btid => $btval) {
						if ($btid == $line['id_upkeep']) {
							$ok = TRUE;
						} else if ($btid == $_SESSION['id_upkeep']) {
							break;
						} else if ($ok && array_key_exists($line['number'], $btval) &&
							array_key_exists($line['expense_name'], $btval[$line['number']]['quantity'])) {
							$estimated += $btval[$line['number']]['quantity'][$line['expense_name']];
						}
					}
				}
				if ($json) {
					echo ',"estimated":' . $estimated . '}';
				} else {
					$line['estimated'] = $estimated;
					$r[] = $line;
				}
			}
		}
	}
	if ($json) {
		echo ']';
	} else {
		return $r;
	}
}

function get_indexes() {
	get_indexes_raw(TRUE, $_SESSION['id_apartment']);
}

function save_indexes($id) {
	global $app;
	$json = json_decode($app->request->getBody());
	$id_apartment = intval($json->id_apartment);
	$id_expense = intval($json->id_expense);
	if (array_search($id_apartment, $_SESSION['user']->apartments) !== FALSE || is_user_readwrite_no_die()) {
		$index1 = floatval($json->index1);
		$index2 = floatval($json->index2);
		$index3 = floatval($json->index3);
		run_query("update indexes set index1 = $index1, index2 = $index2, index3 = $index3 "
			. "where id = $id and id_upkeep = $_SESSION[id_upkeep]", TRUE);
		echo json_encode((object) array('id' => $id));
	}
}

function insert_indexes() {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$id_apartment = intval($json->id_apartment);
	$id_expense = intval($json->id_expense);
	if (array_search($id_apartment, $_SESSION['user']->apartments) !== FALSE || is_user_readwrite_no_die()) {
		$index1 = floatval($json->index1);
		$index2 = floatval($json->index2);
		$index3 = floatval($json->index3);
		run_query('insert into indexes (id_apartment, id_expense, id_upkeep, index1, index2, index3) values '
			. "($id_apartment, $id_expense, $_SESSION[id_upkeep], $index1, $index2, $index3)");
		echo json_encode((object) array('id' => $db->insert_id));
		$db->commit();
	}
}

function get_apartments_and_expenses($upkeep, $id_apartment = FALSE) {
	$q = 'select a.id id, a.number, c.name, e.name expense_name, ev.quantity, ev.value, e.id id_expense, c.id id_coefficient '
		. 'from apartments a cross join expenses e join coefficients c on c.id = e.id_coefficient '
		. "left join expense_values ev on e.id = ev.id_expense and ev.id_upkeep = $upkeep "
		. "where a.id_stair = $_SESSION[id_stair] and e.id_stair = $_SESSION[id_stair]";
	if ($id_apartment) {
		$q .= " and a.id = $id_apartment";
	}
	$q .= ' order by a.number, e.name';
	return run_query($q);
}

function get_coefficient_values() {
	echo '[';
	$result = run_query('select a.number, a.id id_apartment, c.name, cv.value, '
		. 'c.id id_coefficient, cv.id id_coefficient_value '
		. 'from apartments a left join expenses e '
		. 'on a.id_stair = e.id_stair '
		. 'left join coefficients c on e.id_coefficient = c.id left join coefficient_values cv '
		. "on cv.id_apartment = a.id and cv.id_upkeep = $_SESSION[id_upkeep] and cv.id_coefficient = c.id "
		. "where a.id_stair = $_SESSION[id_stair]");
	if ($result) {
		while ($line = $result->fetch_assoc()) {
			if ($line['name']) {
				$table[$line['number']][$line['name']] = array(floatval($line['value']),
					intval($line['id_coefficient']),
					intval($line['id_coefficient_value']));
			}
			$id[$line['number']] = intval($line['id_apartment']);
		}
	}
	if ($table) {
		$c = array_keys($table);
		$l = count($c) - 1;
		$k = 0;
		foreach($c as $i) {
			echo '{"id":' . json_encode($id[$i]);
			foreach(array_keys($table[$i]) as $j) {
				$v = "{$j}_VAL";
				echo ',' . json_encode($v) . ':' . json_encode($table[$i][$j][0]);
				$v = "{$j}_IDCF";
				echo ',' . json_encode($v) . ':' . json_encode($table[$i][$j][1]);
				$v = "{$j}_ID";
				echo ',' . json_encode($v) . ':' . json_encode($table[$i][$j][2]);
			}
			echo '}';
			if ($k++ < $l) {
				echo ',';
			}
		}
	}
	echo ']';
}

function get_mod_types() {
	echo query_to_json('select id, name from mod_types');
}

function get_coefficient_mod_values() {
	echo '[';
	$result = run_query('select a.number, a.id id_apartment, c.name, e.name expense_name, cv.value, '
			. 'c.id id_coefficient, cv.id id_coefficient_mod_value, IFNULL(cv.id_mod_type, 1) id_mod_type, '
			. 'e.id id_expense from apartments a left join expenses e '
			. 'on a.id_stair = e.id_stair left join coefficient_mod_values cv '
			. "on cv.id_apartment = a.id and cv.id_upkeep = $_SESSION[id_upkeep] and cv.id_expense = e.id "
			. 'left join coefficients c on e.id_coefficient = c.id '
			. "where a.id_stair = $_SESSION[id_stair]");
	if ($result) {
		$id = array();
		$table = array();
		while ($line = $result->fetch_assoc()) {
			if ($line['expense_name']) {
				$table[$line['number']][$line['expense_name']] = array(
					$line['value'] ? floatval($line['value']) : NULL,
					intval($line['id_mod_type']),
					intval($line['id_expense']),
					intval($line['id_coefficient_mod_value']));
			}
			$id[$line['number']] = $line['id_apartment'];
		}
		$c = array_keys($table);
		$l = count($c) - 1;
		$k = 0;
		foreach($c as $i) {
			echo '{"id":' . json_encode($id[$i]);
			foreach(array_keys($table[$i]) as $j) {
				$kv = "{$j}_VAL";
				echo ',' . json_encode($kv) . ':' . json_encode($table[$i][$j][0] != NULL ? $table[$i][$j][0] : 0);
				$kv = "{$j}_TYPE";
				echo ',' . json_encode($kv) . ':' . json_encode($table[$i][$j][1]);
				$kv = "{$j}_IDEXP";
				echo ',' . json_encode($kv) . ':' . json_encode($table[$i][$j][2]);
				$kv = "{$j}_ID";
				echo ',' . json_encode($kv) . ':' . json_encode($table[$i][$j][3]);
			}
			echo '}';
			if ($k++ < $l) {
				echo ',';
			}
		}
	}
	echo ']';
}

function save_coefficient_mod_values_raw($id_expense, $id_apartment, $id_mod_type, $v, $id_coefficient_mod_value = NULL) {
	global $db;
	if ($id_coefficient_mod_value) {
		run_query('update coefficient_mod_values cv '
			. "set cv.value = $v, cv.id_mod_type = $id_mod_type where "
			. "cv.id_expense = $id_expense and cv.id_apartment = $id_apartment and "
			. "cv.id_upkeep = $_SESSION[id_upkeep]");
		if ($db->affected_rows) {
			run_query('update coefficient_mod_values cv join upkeeps u on cv.id_upkeep = u.id '
				. "set cv.value = $v, cv.id_mod_type = $id_mod_type where "
				. "cv.id_expense = $id_expense and cv.id_apartment = $id_apartment and "
				. "u.start_date > FROM_UNIXTIME($_SESSION[date_upkeep]) and u.id_stair = $_SESSION[id_stair]");
			if ($id_mod_type != 3) {
				run_query('delete i.* from indexes i join coefficient_mod_values cv on '
					. 'i.id_apartment = cv.id_apartment and i.id_expense = cv.id_expense and '
					. 'i.id_upkeep = cv.id_upkeep join upkeeps u on i.id_upkeep = u.id '
					. "where cv.id_mod_type <> 3 and u.id_stair = $_SESSION[id_stair]");
			}
		}
		return $id_coefficient_mod_value;
	} else {
		run_query('insert into coefficient_mod_values (id_apartment, id_expense, id_upkeep, value, id_mod_type) '
			. "select $id_apartment, $id_expense, u.id, $v, $id_mod_type from upkeeps u where "
			. "u.id = $_SESSION[id_upkeep]");
		if ($db->affected_rows) {
			run_query('insert into coefficient_mod_values (id_apartment, id_expense, id_upkeep, value, id_mod_type) '
				. "select $id_apartment, $id_expense, u.id, $v, $id_mod_type from upkeeps u where "
				. "u.start_date > FROM_UNIXTIME($_SESSION[date_upkeep]) and u.id_stair = $_SESSION[id_stair]");
		}
		return $db->insert_id;
	}
}

function save_coefficient_values_raw($id_apartment, $id_coefficient, $v, $id_coefficient_value = NULL) {
	global $db;
	if ($id_coefficient_value) {
		run_query('update coefficient_values cv set '
			. "cv.value = $v where cv.id_apartment = $id_apartment and "
			. "cv.id_coefficient = $id_coefficient and "
			. "cv.id_upkeep = $_SESSION[id_upkeep]");
		if ($db->affected_rows) {
			run_query('update coefficient_values cv join upkeeps u on cv.id_upkeep = u.id set '
				. "cv.value = $v where cv.id_apartment = $id_apartment and "
				. "cv.id_coefficient = $id_coefficient and "
				. "u.start_date > FROM_UNIXTIME($_SESSION[date_upkeep]) and u.id_stair = $_SESSION[id_stair]");
		}
		$lid = $id_coefficient_value;
	} else {
		run_query('insert into coefficient_values (id_apartment, id_coefficient, id_upkeep, value) '
			. "select $id_apartment, $id_coefficient, u.id, $v from upkeeps u where "
			. "u.id =$_SESSION[id_upkeep]");
		if ($db->affected_rows) {
			run_query('insert into coefficient_values (id_apartment, id_coefficient, id_upkeep, value) '
				. "select $id_apartment, $id_coefficient, u.id, $v from upkeeps u where "
				. "u.start_date > FROM_UNIXTIME($_SESSION[date_upkeep]) and u.id_stair = $_SESSION[id_stair]");
		}
		$lid = $db->insert_id;
	}
	return $lid;
}

function save_coefficient_mod_values($id) {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$coefficients = get_object_vars($json);
	$id_apartment = $id;
	$roots = array();
	if (array_search($id_apartment, $_SESSION['user']->apartments) !== FALSE || is_user_readwrite_no_die()) {
		foreach ($coefficients as $k => $v) {
			if (substr($k, -4) === '_VAL') {
				if ($v == '') {
					$v = 'NULL';
				}
				$root = substr($k, 0, -4);
				$roots[] = $root;
				$id_coefficient_mod_value = intval($coefficients["{$root}_ID"]);
				$id_expense = intval($coefficients["{$root}_IDEXP"]);
				$id_mod_type = intval($coefficients["{$root}_TYPE"]);
				$lid = save_coefficient_mod_values_raw($id_expense, $id_apartment, $id_mod_type, $v, $id_coefficient_mod_value);
				if ($lid) {
					$coefficients["{$root}_ID"] = $lid;
				}
			}
		}
		$db->commit();
		invalidate_table();
		echo json_encode((object) $coefficients);
	}
}

function import_coefficient_mod_values() {
	global $db;
	if (($handle = fopen($_FILES['file']['tmp_name'], 'r')) !== FALSE) {
		if (($data = fgetcsv($handle)) !== FALSE) {
			$l = count($data) - 1;
			if ($l % 3 == 0) {
				$l = 3;
			} else if ($l % 4 == 0) {
				$l = 4;
			} else {
				fclose($handle);
				return;
			}
			$id_expense = array(0 => 0);
			foreach ($data as $k => $v) {
				if ($k % $l == 1) {
					$result = run_query("select id from expenses where name = '$v' and id_stair = $_SESSION[id_stair]");
					if ($result) {
						$row = $result->fetch_object();
						if ($row) {
							$id_expense[] = 0;
							$id_expense[] = 0;
							if ($l == 4) {
								$id_expense[] = 0;
							}
							$id_expense[] = intval($row->id);
						} else {
							fclose($handle);
							return;
						}
					}
				}
			}
			$r = get_indexes_raw();
			while (($data = fgetcsv($handle)) !== FALSE) {
				$id_apartment = NULL;
				$index1 = 0;
				$index2 = 0;
				$index3 = 0;
				foreach ($data as $k => $v) {
					if (count($data) >= 4) {
						if ($k == 0) {
							$result = run_query("select id from apartments where number = $v and id_stair = $_SESSION[id_stair]");
							if ($result) {
								$row = $result->fetch_object();
								if ($row) {
									$id_apartment = intval($row->id);
								}
							}
						} else if ($id_apartment != NULL && ($k % $l == 0)) {
							$id_coefficient_mod_value = NULL;
							$result = run_query('select cv.id from coefficient_mod_values cv '
								. "where cv.id_apartment = $id_apartment and "
								. "cv.id_expense = {$id_expense[$k]} and cv.id_upkeep = $_SESSION[id_upkeep]");
							if ($result) {
								$row = $result->fetch_object();
								if ($row) {
									$id_coefficient_mod_value = intval($row->id);
								}
							}
							if ($l == 3) {
								$index1 = floatval($data[$k - 2]);
								$index2 = floatval($data[$k - 1]);
								$index3 = floatval($v);
							} else {
								$index1 = floatval($data[$k - 3]);
								$index2 = floatval($data[$k - 2]);
								$index3 = floatval($data[$k - 1]);
							}
							$find = array('index1' => 0, 'index2' => 0, 'index3' => 0, 'index1_old' => 0, 'index2_old' => 0, 'index3_old' => 0);
							foreach ($r as $y) {
								if ($y['id_apartment'] == $id_apartment && $y['id_expense'] == $id_expense[$k]) {
									$find = $y;
									break;
								}
							}
							if ($l == 3) {
								$t = $index1 - $y['index1_old'] + $index2 - $y['index2_old'] + $index3 - $y['index3_old'] - $y['estimated'];
							} else {
								$t = floatval($v);
							}
							if ($find['index1'] || $find['index2'] || $find['index3']) {
								run_query("update indexes set index1 = $index1, index2 = $index2, index3 = $index3 "
									. "where id_apartment = $id_apartment and id_expense = $id_expense[$k] and id_upkeep = $_SESSION[id_upkeep]");
							} else {
								run_query('insert into indexes (id_apartment, id_expense, id_upkeep, index1, index2, index3) values '
									. "($id_apartment, $id_expense[$k], $_SESSION[id_upkeep], $index1, $index2, $index3)");
							}
							save_coefficient_mod_values_raw($id_expense[$k], $id_apartment, 3, $t, $id_coefficient_mod_value);
						}
					}
				}
			}
			$db->commit();
			invalidate_table();
		}
		fclose($handle);
	}
}

function save_coefficient_values($id) {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$coefficients = get_object_vars($json);
	$id_apartment = $id;
	if (array_search($id_apartment, $_SESSION['user']->apartments) !== FALSE || is_user_readwrite_no_die()) {
		foreach ($coefficients as $k => $v) {
			if (substr($k, -4) === '_VAL') {
				$root = substr($k, 0, -4);
				$id_coefficient_value = array_key_exists("{$root}_ID", $coefficients) ? $coefficients["{$root}_ID"] : NULL;
				$id_coefficient = $coefficients["{$root}_IDCF"];
				$lid = save_coefficient_values_raw($id_apartment, $id_coefficient, $v, $id_coefficient_value);
				if ($lid) {
					$coefficients["{$root}_ID"] = $lid;
				}
			}
		}
		$db->commit();
		invalidate_table();
		echo json_encode((object) $coefficients);
	}
}

function import_apartments() {
	global $db;
	if (($handle = fopen($_FILES['file']['tmp_name'], 'r')) !== FALSE) {
		if (($k = fgetcsv($handle)) !== FALSE) {
			if (count($k) == 5) {
				$ids = array();
				$result = run_query("select id, number from apartments where id_stair = $_SESSION[id_stair]");
				if ($result) {
					while ($row = $result->fetch_object()) {
						$ids[$row->number] = $row->id;
					}
				}
				while (($data = fgetcsv($handle)) !== FALSE && count($data) >= 4) {
					if (count($data) == 5) {
						$d = array_combine($k, $data);
						if (array_key_exists($d[''], $ids)) {
							run_query("update apartments set current = $d[curent], pending = $d[restanta], penalty = $d[penalizare], name = '$d[tip]' where id = {$ids[$d['']]}");
						} else {
							run_query("insert into apartments (number, current, pending, penalty, id_stair, id_person, name) values ('{$d['']}', $d[curent], $d[restanta], $d[penalizare], $_SESSION[id_stair], {$_SESSION['user']->id_person}, '$d[tip]')");
						}
					}
				}
				$db->commit();
				invalidate_table();
			}
		}
		fclose($handle);
	}
}

function import_coefficient_values() {
	global $db;
	if (($handle = fopen($_FILES['file']['tmp_name'], 'r')) !== FALSE) {
		if (($h = fgetcsv($handle)) !== FALSE) {
			while (($data = fgetcsv($handle)) !== FALSE) {
				foreach ($data as $k => $v) {
					if ($k > 0) {
						$result = run_query('select cv.id id_coefficient_value, a.id id_apartment, '
							. 'c.id id_coefficient from '
							. 'apartments a cross join coefficients c '
							. 'left join coefficient_values cv on cv.id_apartment = a.id and '
							. "cv.id_coefficient = c.id and cv.id_upkeep = $_SESSION[id_upkeep] "
							. "where a.id_stair = $_SESSION[id_stair] "
							. "and a.number = $data[0] and c.name = '$h[$k]'");
						if ($result) {
							$row = $result->fetch_object();
							if ($row) {
								save_coefficient_values_raw($row->id_apartment, $row->id_coefficient, floatval($v), $row->id_coefficient_value);
							}
						}
					}
				}
			}
			$db->commit();
			invalidate_table();
		}
		fclose($handle);
	}
}

function save_state() {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$id_stair = intval($json->id_stair);
	$id_apartment = intval($json->id_apartment);
	$date_upkeep = floatval($json->date_upkeep);
	$id_upkeep = NULL;
	$result = run_query('select id, name, UNIX_TIMESTAMP(start_date) start_date '
		. "from upkeeps where start_date = FROM_UNIXTIME($date_upkeep) and id_stair = $id_stair");
	if ($result) {
		$row = $result->fetch_object();
		if ($row) {
			$id_upkeep = intval($row->id);
			$_SESSION['date_upkeep'] = floatval($row->start_date);
			$_SESSION['name_upkeep'] = $row->name;
		} else {
			$_SESSION['date_upkeep'] = $date_upkeep;
			set_name_for_upkeep();
		}
	}
	if ($_SESSION['id_apartment'] && $_SESSION['id_apartment'] != $id_apartment) {
		$_SESSION['id_apartment'] = $id_apartment;
	} else if ($_SESSION['id_upkeep'] != $id_upkeep || $id_upkeep == NULL) {
		if ($_SESSION['id_stair'] != $id_stair) {
			$_SESSION['id_stair'] = $id_stair;
			$_SESSION['id_apartment'] = get_default_apartment();
			$_SESSION['id_upkeep'] = NULL;
			$_SESSION['oldest_date_upkeep'] = NULL;
			$_SESSION['date_upkeep_active'] = NULL;
			set_default_upkeep();
		} else {
			$_SESSION['id_upkeep'] = $id_upkeep;
		}
	}
	insert_upkeep();
	if (empty($_SESSION['date_upkeep_active']) || empty($_SESSION['oldest_date_upkeep'])) {
		set_default_upkeep();
	}
	if (!array_key_exists($_SESSION['id_upkeep'], $_SESSION['table'])) {
		invalidate_table();
	}
	return get_state();
}

function get_state() {
	if (array_key_exists('name_upkeep', $_SESSION) && array_key_exists('date_upkeep', $_SESSION)) {
		$u = '{"name":' .json_encode($_SESSION['name_upkeep']) . ',"start_date":' . json_encode($_SESSION['date_upkeep']) . '}';
	} else {
		$u = json_encode(NULL);
	}
	if (!empty($_SESSION['id_upkeep'])) {
		$u = query_to_json('select id, name, UNIX_TIMESTAMP(activation_date) activation_date, '
			. 'UNIX_TIMESTAMP(deactivation_date) deactivation_date, UNIX_TIMESTAMP(start_date) start_date, '
			. "id_stair, active from upkeeps where id = $_SESSION[id_upkeep]", TRUE);
	}
	echo '{"id_stair":' . json_encode($_SESSION['id_stair'])
		. ',"id_upkeep":' .json_encode($_SESSION['id_upkeep'])
		. ',"id_apartment":' . json_encode($_SESSION['id_apartment'])
		. ',"name_upkeep":' . json_encode(array_key_exists('name_upkeep', $_SESSION) ? $_SESSION['name_upkeep'] : NULL)
		. ',"date_upkeep":' . json_encode(array_key_exists('date_upkeep', $_SESSION) ? $_SESSION['date_upkeep'] : NULL)
		. ',"oldest_date_upkeep":' . json_encode(array_key_exists('oldest_date_upkeep', $_SESSION) ? $_SESSION['oldest_date_upkeep'] : NULL)
		. ',"date_upkeep_active":' . json_encode(array_key_exists('date_upkeep_active', $_SESSION) ? $_SESSION['date_upkeep_active'] : NULL)
		. ',"upkeep":' . $u
		. ',"association":' . get_association() . '}';
}

function get_association() {
	if ($_SESSION['id_stair']) {
		return query_to_json("select a.id, a.name, a.address from stairs s join associations a on s.id_association = a.id where s.id = $_SESSION[id_stair]", TRUE);
	} else {
		return json_encode(NULL);
	}
}

function get_default_stair() {
	return $_SESSION['user']->stairs ? $_SESSION['user']->stairs[0] : NULL;
}

function get_default_apartment() {
	$result  = run_query('select a.id from users_apartments_map uam join apartments a on uam.id_apartment = a.id '
		. "where a.id_stair = $_SESSION[id_stair] and uam.id_user = {$_SESSION['user']->id} limit 1");
	if ($result) {
		$row = $result->fetch_object();
		return $row ? intval($row->id) : NULL;
	}
}

function set_default_upkeep() {
	$result  = run_query("select u.id, UNIX_TIMESTAMP(u.start_date) start_date, "
		. "u.name, u.active from upkeeps u where u.id_stair = $_SESSION[id_stair] "
		. "order by u.active desc, u.start_date desc limit 1");
	if ($result) {
		$row = $result->fetch_object();
		if ($row) {
			if (empty($_SESSION['id_upkeep'])) {
				$_SESSION['id_upkeep'] = intval($row->id);
				$_SESSION['date_upkeep'] = intval($row->start_date);
				$_SESSION['name_upkeep'] = $row->name;
			}
			if ($row->active) {
				$_SESSION['date_upkeep_active'] = intval($row->start_date);
			}
		} else {
			insert_upkeep();
		}
	}
	$result = run_query('select min(UNIX_TIMESTAMP(start_date)) start_date '
		. "from upkeeps where id_stair = $_SESSION[id_stair] group by start_date");
	if ($result) {
		$row = $result->fetch_object();
		if ($row) {
			$_SESSION['oldest_date_upkeep'] = floatval($row->start_date);
		}
	}
}

function set_name_for_upkeep() {
	$months = array('', 'ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie');
	$_SESSION['name_upkeep'] = $months[date('n', $_SESSION['date_upkeep'])] . ' ' . date('Y', $_SESSION['date_upkeep']);
}

function insert_upkeep() {
	global $db, $app;
	if (empty($_SESSION['id_upkeep'])) {
		if (empty($_SESSION['date_upkeep'])) {
			$stime = strtotime('-1 month');
			$_SESSION['date_upkeep'] = mktime(0, 0, 0, date('n', $stime), 1, date('Y', $stime));
		}
		$date = $_SESSION['date_upkeep'];
		if (empty($_SESSION['name_upkeep'])) {
			set_name_for_upkeep();
		}
		$name = $_SESSION['name_upkeep'];
		$result = run_query('select u.id, u.interest_rate, u.calculator from upkeeps u '
				. "where u.id_stair = $_SESSION[id_stair] and u.start_date < FROM_UNIXTIME($date) "
				. 'order by u.start_date desc limit 1');
		if ($result) {
			$row = $result->fetch_object();
			if ($row) {
				$ir = $row->interest_rate ? $row->interest_rate : 'NULL';
				$ic = $row->calculator ? "'$row->calculator'" : 'NULL';
				run_query('insert into upkeeps (name, id_stair, interest_rate, calculator, start_date) '
					. "values ('$name', $_SESSION[id_stair], $ir, $ic, FROM_UNIXTIME($date))");
				$id = $db->insert_id;
				run_query('insert into coefficient_values (id_upkeep, id_apartment, id_coefficient, value) '
					. "select $id, id_apartment, id_coefficient, value from coefficient_values where id_upkeep = {$row->id}");
				run_query('insert into coefficient_mod_values (id_upkeep, id_expense, id_apartment, value, id_mod_type) '
					. "select $id, id_expense, id_apartment, value, id_mod_type from coefficient_mod_values ucmv "
					. "where ucmv.id_upkeep = {$row->id} and ucmv.id_mod_type = 2");
				run_query('insert into coefficient_mod_values (id_upkeep, id_expense, id_apartment, value, id_mod_type) '
					. "select $id, id_expense, id_apartment, NULL, id_mod_type from coefficient_mod_values ucmv "
					. "where ucmv.id_upkeep = {$row->id} and ucmv.id_mod_type in (3, 4)");
			} else {
				run_query("insert into upkeeps (name, id_stair, start_date) values ('$name', $_SESSION[id_stair], FROM_UNIXTIME($date))");
				$id = $db->insert_id;
			}
			$_SESSION['id_upkeep'] = $id;
			$db->commit();
			if ($date < $_SESSION['oldest_date_upkeep']) {
				$_SESSION['oldest_date_upkeep'] = $date;
			}
		}
	}
}

function activate_upkeep() {
	global $db;
	run_query("update upkeeps set active = 0, deactivation_date = sysdate() where active = 1 and id_stair = $_SESSION[id_stair]");
	run_query("update upkeeps set active = 1, activation_date = sysdate(), deactivation_date = null where id = $_SESSION[id_upkeep]");
	$db->commit();
	$_SESSION['date_upkeep_active'] = $_SESSION['date_upkeep'];
}

function reset_password($id) {
	run_query("update users set password = MD5('intretinere') where id = $id", TRUE);
}

function change_password() {
	global $db, $app;
	$json = json_decode($app->request->getBody());
	$password = $db->real_escape_string($json->new_password);
	$old_password = $db->real_escape_string($json->old_password);
	run_query("update users set password = MD5('$password') where id = {$_SESSION['user']->id} and password = MD5('$old_password')");
	if ($db->affected_rows == 0) {
		crash('Parola incorecta');
	} else {
		$db->commit();
	}
}

function get_configuration() {
	echo query_to_json('select IFNULL(ROUND(u.interest_rate * 100, 2), 0.2) rate from upkeeps u '
		. "where u.id = $_SESSION[id_upkeep]", TRUE);
}

function save_configuration() {
	global $app;
	$json = json_decode($app->request->getBody());
	$rate = floatval($json->rate) / 100;
	run_query("update upkeeps set interest_rate = $rate where id = $_SESSION[id_upkeep]", TRUE);
}

function get_table() {
	get_table_data(TRUE);
}

function get_table_data($json = FALSE) {
	$cf = array();
	$result = run_query('select a.number, c.name, cv.value from '
			. 'coefficient_values cv '
			. 'join apartments a on cv.id_apartment = a.id '
			. 'join coefficients c on cv.id_coefficient = c.id '
			. "where cv.id_upkeep = $_SESSION[id_upkeep] and c.default_show = 1");
	if ($result) {
		while ($row = $result->fetch_object()) {
			$cf[$row->number][$row->name] = $row->value;
		}
		$result->free();
	}
	$ex = array();
	$result = run_query("select e.name, IFNULL(e.title, e.name) title from expenses e where e.id_stair = $_SESSION[id_stair]");
	if ($result) {
		while ($row = $result->fetch_object()) {
			$ex[$row->name] = $row->title;
		}
		$result->free();
	}
	$grid = array();
	if ($json) {
		echo '[';
	}
	$first = TRUE;
	$balance = $_SESSION['table'][$_SESSION['id_upkeep']];
	foreach (array_keys($balance) as $i) {
		if (!empty($i)) {
			$row = array('APARTAMENT' => $i);
			$total = 0;
			if ($json) {
				if ($first) {
					$first = FALSE;
				} else {
					echo ',';
				}
				echo '{';
			}
			if (array_key_exists($i, $cf)) {
				foreach ($cf[$i] as $j => $v) {
					$row["$j"] = $v;
				}
			}
			$a = $balance[$i]['expense'];
			if ($a) {
				foreach ($a as $k => $v) {
					if (array_key_exists($ex[$k], $row)) {
						$row[$ex[$k]] += $v;
					} else {
						$row[$ex[$k]] = $v;
					}
					$total += $v;
				}
			}
			if ($json) {
				foreach ($row as $k => $v) {
					echo "\"$k\":$v,";
				}
			}
			$row['Subtotal'] = $total;
			$row['Restanta'] = array_key_exists('pending', $balance[$i]) && $balance[$i]['pending'] > 0 ? round($balance[$i]['pending'], 2) : 0;
			$row['Avans'] = array_key_exists('pending', $balance[$i]) && $balance[$i]['pending'] < 0 ? round(-$balance[$i]['pending'], 2) : 0;
			$row['Penalizare'] = array_key_exists('penalty', $balance[$i]) ? round($balance[$i]['penalty'], 2) : 0;
			$row['Rotunjire'] = $balance[$i]['error'];
			$row['Plati'] = round($balance[$i]['payment'], 2);
			$row['Total'] = round($total + $row['Restanta'] - $row['Avans'] + $row['Penalizare'] - $row['Plati'], 2);
			if ($json) {
				echo "\"Subtotal\":$total,\"Rotunjire\":{$row['Rotunjire']},\"Avans\":{$row['Avans']},\"Restanta\":{$row['Restanta']},\"Penalizare\":$row[Penalizare],\"Plati\":{$row['Plati']},\"Total\":$row[Total]}";
			}
			$grid[] = $row;
		}
	}
	if ($json) {
		echo ']';
	}
	return $grid;
}

function export_table() {
	grid_to_csv(get_table_data(), 'intretinere');
}

function invalidate_table() {
	if (array_key_exists('table', $_SESSION)) {
		unset($_SESSION['table']);
	}
	$result = run_query('select id, UNIX_TIMESTAMP(start_date) start_date, '
			. 'IFNULL(interest_rate, 0.002) rate, '
			. "IFNULL(calculator, 'default_calculator') classname from upkeeps where "
			. "id_stair = $_SESSION[id_stair] and start_date <= FROM_UNIXTIME($_SESSION[date_upkeep]) "
			. 'order by start_date');
	if ($result) {
		$table = array();
		$last_upkeep = 0;
		$last_rate = 0.002;
		$last_date = NULL;
		$prev_date = NULL;
		$first = TRUE;
		while ($row = $result->fetch_object()) {
			if ($last_upkeep != 0) {
				$prev_date = $last_date;
				$first = FALSE;
			} else {
				$prev_date = strtotime('-1 month', $row->start_date);
			}
			$last_date = $row->start_date;
			$c = new $row->classname;
			$c->calculate_balance_tree($table, $row->id, $last_upkeep, $prev_date, $last_date, $last_rate, $first);
			$last_upkeep = $row->id;
			$last_rate = $row->rate;
		}
		$_SESSION['table'] = $table;
	}
}

function get_chart_expenses() {
	global $app;
	$charttype = $app->request->params('type');
	$chartvalue = $app->request->params('value');
	$chartperiod = $app->request->params('period');
	$chartperiod = $chartperiod ? intval($chartperiod) - 1 : 5;
	if ($charttype == 'me') {
		if ($_SESSION['id_apartment']) {
			$o = query_to_object(get_user_apartments_raw($_SESSION['id_apartment']), TRUE);
			if ($o) {
				$number = $o['number'];
			}
		} else {
			$number = $app->request->params('apartment');
		}
	}
	$vf = $chartvalue == 'quantity' ? 'quantity value' : 'value'; 
	$q = "select u.id, u.name, e.name expense, ev.{$vf} from upkeeps u "
		. 'left join expense_values ev on u.id = ev.id_upkeep '
		. "left join expenses e on ev.id_expense = e.id where u.id_stair = $_SESSION[id_stair] "
		. "and u.start_date <= FROM_UNIXTIME($_SESSION[date_upkeep]) "
		. "and PERIOD_DIFF(DATE_FORMAT(FROM_UNIXTIME($_SESSION[date_upkeep]),'%Y%m'),DATE_FORMAT(u.start_date,'%Y%m')) <= $chartperiod "
		. 'order by e.name, u.start_date';
	$result = run_query($q);
	if ($result) {
		$balance_tree = $_SESSION['table'];
		$labels = array();
		$data = array();
		$a = array();
		$js = '{"labels":[';
		$first = TRUE;
		while ($row = $result->fetch_object()) {
			if (!array_key_exists($row->expense, $data)) {
				$data[$row->expense] = array();
			}
			if (!in_array($row->name, $labels)) {
				$labels[] = $row->name;
				if (!$first) {
					$js .= ',';
				} else {
					$first = FALSE;
				}
				$js .= json_encode($row->name);
				if (!isset($number)) {
					$arrear = 0;
					if (array_key_exists($row->id, $balance_tree)) {
						foreach($balance_tree[$row->id] as $v) {
							$arrear += $v['pending'];
						}
					}
					$a[] = $arrear;
				}
			}
			if (isset($number)) {
				if (array_key_exists($row->id, $balance_tree)) {
					if ($chartvalue == 'quantity') {
						if (array_key_exists($row->expense, $balance_tree[$row->id][$number]['quantity'])) {
							$data[$row->expense][$row->name] =  $balance_tree[$row->id][$number]['quantity'][$row->expense];
						}
					} else if (array_key_exists($row->expense, $balance_tree[$row->id][$number]['expense'])) {
						$data[$row->expense][$row->name] =  $balance_tree[$row->id][$number]['expense'][$row->expense];
					}
				}
			} else {
				$data[$row->expense][$row->name] = floatval($row->value);
			}
		}
		$js .= '],"datasets":[';
		$colors = array('0,191,255', '244,164,96', '127,255,212', '255,140,0', '139,71,93', '240,128,128',
				'205,51,51', '255,20,147', '153,50,204', '35,107,142', '0,139,0', '131,139,131');
		$first = TRUE;
		if ($a) {
			$color = $colors[0];
			$js .= '{"title":"RESTANTE"'
					. ',"data":' . json_encode($a)
					. ',"fillColor":"rgba(' . $color . ', 0.5)"'
					. ',"strokeColor":"rgba(' . $color . ', 1)"'
					. ',"pointColor":"rgba(' . $color . ', 1)"'
					. ',"pointStrokeColor":"#fff"}';
			$first = FALSE;
		}
		$i = 1;
		foreach ($data as $k => $v) {
			if ($k) {
				$jsrec = '[';
				$firstrec = TRUE;
				foreach($labels as $u) {
					if (!$firstrec) {
						$jsrec .= ',';
					} else {
						$firstrec = FALSE;
					}
					$jsrec .= json_encode(array_key_exists($u, $v) ? $v[$u] : 0);
				}
				$jsrec .= ']';
				$color = $colors[$i++ % 12];
				if (!$first) {
					$js .= ',';
				} else {
					$first = FALSE;
				}
				$js .= '{"title":' . json_encode($k)
					. ',"fillColor":"rgba(' . $color . ', 0.5)"'
					. ',"strokeColor":"rgba(' . $color . ', 1)"'
					. ',"pointColor":"rgba(' . $color . ', 1)"'
					. ',"pointStrokeColor":"#fff"'
					. ',"data":' . $jsrec . '}';
			}
		}
		echo $js . ']}';
	}
}
?>

