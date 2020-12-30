<?php

class default_calculator {

	function create_main_query_select() {
		return 'a.number, e.name, ev.value, '
			. 'IFNULL(ev.quantity, 1) quantity, cv.value coefficient_value, cmv.value mod_value, '
			. 'IFNULL(cmv.id_mod_type, 1) id_mod_type, '
			. 'a.current, a.pending, a.penalty, sum(pv.value) payments';
	}

	function create_main_query_from() {
		return 'upkeeps u '
			. 'left join apartments a on u.id_stair = a.id_stair '
			. 'left join expenses e on u.id_stair = e.id_stair '
			. 'left join expense_values ev on e.id = ev.id_expense and ev.id_upkeep = u.id '
			. 'left join coefficient_values cv on a.id = cv.id_apartment '
			. 'and e.id_coefficient = cv.id_coefficient and cv.id_upkeep = u.id '
			. 'left join coefficient_mod_values cmv on '
			. 'a.id = cmv.id_apartment and e.id = cmv.id_expense and cmv.id_upkeep = u.id '
			. 'left join payment_values pv on pv.id_apartment = a.id and pv.id_upkeep = u.id';
	}

	function create_main_query_where($id) {
		return "u.id = $id";
	}

	function create_main_query_group_by() {
		return 'a.number, e.name, ev.value, ev.quantity, cv.value, cmv.value, '
			. 'cmv.id_mod_type, a.current, a.pending, a.penalty';
	}

	function create_main_query_order_by() {
		return 'e.name, a.number';
	}

	function create_main_query($id) {
		return 'select ' . $this->create_main_query_select()
			. ' from ' . $this->create_main_query_from()
			. ' where ' . $this->create_main_query_where($id)
			. ' group by ' . $this->create_main_query_group_by()
			. ' order by ' . $this->create_main_query_order_by();
	}

	function calculate_balance_tree(&$table, $id, $last_upkeep, $prev_date, $last_date, $last_rate, $first) {
		$result = run_query($this->create_main_query($id));
		if ($result) {
			$last_expense = NULL;
			$quantity = 0;
			$price = 0;
			$left_price = 0;
			$total = 0;
			while ($row = $result->fetch_object()) {
				if ($row->name != $last_expense) {
					if ($last_expense) {
						$this->calculate_basic($table, $id, $last_expense, $left_price, $total);
					}
					$last_expense = $row->name;
					$quantity = floatval($row->quantity);
					$price = floatval($row->value);
					$left_price = $price;
					$total = 0;
				}
				$table[$id][$row->number]['payment'] = $row->payments;
				if ($first) {
					$table[$last_upkeep][$row->number]['payment'] = 0;
					$table[$last_upkeep][$row->number]['current'] = $row->current;
					$table[$last_upkeep][$row->number]['pending'] = $row->pending;
					$table[$last_upkeep][$row->number]['penalty'] = $row->penalty;
				}
				$this->calculate_mods($table, $id, $price, $left_price, $quantity, $total, $row);
			}
			if ($last_expense) {
				$this->calculate_basic($table, $id, $last_expense, $left_price, $total);
			}
			$this->calculate_penalties($table, $id, $last_upkeep, $last_rate, ($last_date - $prev_date) / 86400);
			foreach ($table[$id] as $k => $apt) {
				$table[$id][$k]['current'] = 0;
				$table[$id][$k]['error'] = 0;
				if (array_key_exists('expense', $apt)) {
					foreach ($apt['expense'] as $n => $v) {
						$table[$id][$k]['current'] += round($v, 2);
						$table[$id][$k]['expense'][$n] = round($v, 2);
						$table[$id][$k]['error'] += round($v, 2) - $v;
					}
				}
				$table[$id][$k]['error'] = round($table[$id][$k]['error'], 2);
			}
		}
	}

	function calculate_penalties(&$table, $id, $last_upkeep, $last_rate, $days) {
		foreach ($table[$last_upkeep] as $k => $apt) {
			$temp = $apt['payment'];
			if ($temp > $apt['penalty']) {
				$temp -= $apt['penalty'];
				$apt['penalty'] = 0;
				$apt['pending'] -= $temp;
			} else {
				$apt['penalty'] -= $temp;
			}
			if ($apt['pending'] > 0) {
				$table[$id][$k]['penalty'] = round($apt['penalty'] + $apt['pending'] * $last_rate * $days, 2);
			} else {
				$table[$id][$k]['penalty'] = 0;
			}
			$table[$id][$k]['pending'] = $apt['pending'] + $apt['current'];
		}
	}

	function calculate_mods(&$table, $id, $price, &$left_price, $quantity, &$total, $row) {
		if ($row->id_mod_type == 1 || ($row->id_mod_type == 3 && $row->mod_value == NULL)) {
			$this->calculate_mods_shared($table, $id, $price, $left_price, $quantity, $total, $row);
		} else if ($row->id_mod_type == 2) {
			$this->calculate_mods_exempt($table, $id, $price, $left_price, $quantity, $total, $row);
		} else if ($row->id_mod_type == 3) {
			$this->calculate_mods_index($table, $id, $price, $left_price, $quantity, $total, $row);
		} else if ($row->id_mod_type == 4) {
			$this->calculate_mods_extern($table, $id, $price, $left_price, $quantity, $total, $row);
		}
	}

	function calculate_mods_shared(&$table, $id, $price, &$left_price, $quantity, &$total, $row) {
		$table[$id][$row->number]['quantity'][$row->name] = floatval($row->coefficient_value);
		$total += $table[$id][$row->number]['quantity'][$row->name];
	}

	function calculate_mods_exempt(&$table, $id, $price, &$left_price, $quantity, &$total, $row) {
		$table[$id][$row->number]['quantity'][$row->name] = floatval($row->coefficient_value) * floatval($row->mod_value);
		$total += $table[$id][$row->number]['quantity'][$row->name];
	}

	function calculate_mods_index(&$table, $id, $price, &$left_price, $quantity, &$total, $row) {
		$table[$id][$row->number]['expense'][$row->name] = floatval($row->mod_value) * $price / $quantity;
		$left_price -= $table[$id][$row->number]['expense'][$row->name];
	}

	function calculate_mods_extern(&$table, $id, $price, &$left_price, $quantity, &$total, $row) {
		$table[$id][$row->number]['expense'][$row->name] = floatval($row->mod_value);
		$left_price -= $table[$id][$row->number]['expense'][$row->name];
	}

	function calculate_basic(&$table, $id, $last_expense, $left_price, $total) {
		foreach ($table[$id] as $k => $apt) {
			if ((!array_key_exists('expense', $apt)) || (!array_key_exists($last_expense, $apt['expense']))) {
				$table[$id][$k]['expense'][$last_expense] = $total ? $apt['quantity'][$last_expense] * $left_price / $total : 0;
			}
		}
	}
}

class add_percent_to_index_calculator_1 extends default_calculator {

	function calculate_mods_index(&$table, $id, $price, &$left_price, $quantity, &$total, $row) {
		$table[$id][$row->number]['expense'][$row->name] = $row->mod_value * $price / $quantity;
		if (strrpos($row->name, 'Apa calda') == 0) {
			$table[$id][$row->number]['expense'][$row->name] += $table[$id][$row->number]['expense'][$row->name] * 0.1;
		}
		$left_price -= $table[$id][$row->number]['expense'][$row->name];
	}
}

class add_percent_to_index_calculator_2 extends default_calculator {

	function create_main_query_select() {
		return parent::create_main_query_select()
			. ', cv2.value coefficient_value2, e.id id_expense';
	}

	function create_main_query_from() {
		return parent::create_main_query_from()
			. ' left join coefficient_values cv2 on a.id = cv2.id_apartment '
			. 'and cv2.id_coefficient = 2 and cv2.id_upkeep = u.id';
	}

	function create_main_query_group_by() {
		return parent::create_main_query_group_by()
			. ', cv2.value, e.id';
	}

	function calculate_mods_index(&$table, $id, $price, &$left_price, $quantity, &$total, $row) {
		$table[$id][$row->number]['expense'][$row->name] = $row->mod_value * $price / $quantity;
		if ($row->id_expense == 2) {
			$table[$id][$row->number]['expense'][$row->name] += $table[$id][$row->number]['expense'][$row->name] * $row->coefficient_value2 / 100;
		}
		$left_price -= $table[$id][$row->number]['expense'][$row->name];
	}
}
?>

