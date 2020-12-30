<?php
session_cache_limiter(false);
session_start();
if ((array_key_exists('test', $_POST) && $_POST['test'] == '1') || (array_key_exists('test', $_SESSION) && $_SESSION['test'] == 1)) {
	$_SESSION['test'] = 1;
	$db = new mysqli('localhost', 'root', 'root', 'adminonline_test');
} else {
	$_SESSION['test'] = 0;
	$db = new mysqli('localhost', 'root', 'root', 'adminonline');
}
$db->autocommit(false);
$mailutilizator = 'office@localhost';
$mailparola = 'root';
require('php/lib/class.phpmailer.php');
require('php/tenants.php');
require("php/calculators.php");
if (!isset($_SESSION['user'])) {
	if (isset($_POST['username'])) {
		$user = get_user($_POST['username'], $_POST['password']);
		if ($user) {
			$_SESSION['user'] = $user;
			get_user_details();
			$_SESSION['id_stair'] = get_default_stair();
			$_SESSION['id_apartment'] = get_default_apartment();
			set_default_upkeep();
			invalidate_table();
		} else {
			header('Location: index.php?error=1' . ($_POST['test'] == '1' ? '&test=1' : ''));
			die("Credentiale invalide.");
		}
	} else {
		header('Location: index.php');
		die("Credentiale invalide.");
	}
}
if ($_SERVER['REQUEST_URI'] != '/main.php') {
	require 'php/lib/Slim/Slim.php';
	\Slim\Slim::registerAutoloader();
	$app = new \Slim\Slim(array('mode' => 'production'));
	$app->get('/api/users', 'is_user_read', 'get_users');
	$app->get('/api/persons', 'is_user_read', 'get_persons');
	$app->get('/api/stairs', 'is_user_read', 'get_stairs');
	$app->get('/api/apartments', 'is_user_read', 'get_apartments');
	$app->get('/api/expenses', 'is_user_read', 'get_expenses');
	$app->get('/api/coefficients', 'is_user_read', 'get_coefficients');
	$app->get('/api/coefficient_values', 'is_user_read', 'get_coefficient_values');
	$app->get('/api/coefficient_mod_values', 'is_user_read', 'get_coefficient_mod_values');
	$app->get('/api/table', 'is_user_read', 'get_table');
	$app->get('/api/table/export', 'is_user_readwrite', 'export_table');
	$app->get('/api/configuration', 'is_user_read', 'get_configuration');
	$app->get('/api/person_roles', 'is_user_read', 'get_person_roles');
	$app->get('/api/person_jobs', 'is_user_read', 'get_person_jobs');
	$app->get('/api/mod_types', 'is_user_read', 'get_mod_types');
	$app->get('/api/series', 'is_user_read', 'get_series');
	$app->get('/api/payments', 'is_user_read', 'get_payments');
	$app->get('/api/state', 'is_user_read', 'get_state');
	$app->get('/api/messages', 'is_user_read', 'get_messages');
	$app->get('/api/chart/expenses', 'is_user_read', 'get_chart_expenses');
	$app->get('/api/indexes', 'is_user_read', 'get_indexes');
	$app->put('/api/state', 'is_user_read', 'save_state');
	$app->put('/api/users/:id', 'is_user_readwrite', 'save_user');
	$app->put('/api/persons/:id', 'is_user_read', 'save_person');
	$app->put('/api/apartments/:id', 'is_user_readwrite', 'save_apartment');
	$app->put('/api/expenses/:id', 'is_user_readwrite', 'save_expense');
	$app->put('/api/coefficient_values/:id', 'is_user_read', 'save_coefficient_values');
	$app->put('/api/coefficient_mod_values/:id', 'is_user_read', 'save_coefficient_mod_values');
	$app->put('/api/series/:id', 'is_user_readwrite', 'save_series');
	$app->put('/api/payments/:id', 'is_user_readwrite', 'save_payments');
	$app->put('/api/messages/:id', 'is_user_readwrite', 'save_messages');
	$app->put('/api/indexes/:id', 'is_user_read', 'save_indexes');
	$app->put('/api/stairs/:id', 'is_user_readwrite', 'save_stair');
	$app->delete('/api/users/:id', 'is_user_readwrite', 'delete_user');
	$app->delete('/api/persons/:id', 'is_user_readwrite', 'delete_person');
	$app->delete('/api/apartments/:id', 'is_user_readwrite', 'delete_apartment');
	$app->delete('/api/expenses/:id', 'is_user_readwrite', 'delete_expense');
	$app->delete('/api/series/:id', 'is_user_readwrite', 'delete_series');
	$app->delete('/api/payments/:id', 'is_user_readwrite', 'delete_payments');
	$app->delete('/api/messages/:id', 'is_user_readwrite', 'delete_messages');
	$app->post('/api/users', 'is_user_readwrite', 'insert_user');
	$app->post('/api/persons', 'is_user_readwrite', 'insert_person');
	$app->post('/api/apartments', 'is_user_readwrite', 'insert_apartment');
	$app->post('/api/import_apartments', 'is_user_readwrite', 'import_apartments');
	$app->post('/api/expenses', 'is_user_readwrite', 'insert_expense');
	$app->post('/api/upkeeps/activate', 'is_user_readwrite', 'activate_upkeep');
	$app->post('/api/series', 'is_user_readwrite', 'insert_series');
	$app->post('/api/payments', 'is_user_readwrite', 'insert_payments');
	$app->post('/api/configuration', 'is_user_readwrite', 'save_configuration');
	$app->post('/api/password/reset/:id', 'is_user_readwrite', 'reset_password');
	$app->post('/api/password/change', 'is_user_read', 'change_password');
	$app->post('/api/coefficient_values', 'is_user_readwrite', 'import_coefficient_values');
	$app->post('/api/coefficient_mod_values', 'is_user_readwrite', 'import_coefficient_mod_values');
	$app->post('/api/messages', 'is_user_readwrite', 'insert_messages');
	$app->post('/api/contact', 'is_user_read', 'send_contact');
	$app->post('/api/indexes', 'is_user_read', 'insert_indexes');
	$app->run();
} else {
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Online</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<script type="text/template" id="tpl-main-upkeep">
		<div class="form-group">
			<div class='input-group date' id='upkeepDatePicker'>
				<input type='text' class="form-control" id="date_upkeep" readonly data-format="MMMM YYYY"/>
				<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
				</span>
			</div>
		</div>
</script>
<script type="text/template" id="tpl-main-apartment">
<% if (apartments && state.id_apartment) { %>
<div class="form-group"><select class="form-control" id="mainApartment">
<% _.each(apartments, function(a) { %>
<option value="<%=a.id%>" <% if (a.id == state.id_apartment) print('selected'); %> >
Apartament <%=a.number%></option>
<% }); %>
</select>
</div>
<% } %>
</script>
<script type="text/template" id="tpl-buttons">
<table><tr><td>
<% if (APPSTATE.isUserAdmin()) { %>
<button id="add" type="button" class="btn btn-default hidden-print">Adauga</button>
<button id="delete" type="button" class="btn btn-default hidden-print" disabled data-toggle="modal" data-target="#deleteConfirmationModal">Sterge</button>
<button id="activateUpkeep" type="button" class="btn btn-default hidden hidden-print">Activeaza</button>
<button id="export" type="button" class="btn btn-default hidden hidden-print">Exporta</button>
<button id="resetPassword" type="button" class="btn btn-default hidden hidden-print" disabled data-toggle="modal" data-target="#resetConfirmationModal">Reseteaza parola</button>
<% } %>
<button id="changePassword" type="button" class="btn btn-default hidden hidden-print">Schimba parola</button>
<button id="save" type="button" class="btn btn-default hidden hidden-print">Salveaza</button>
<% if (APPSTATE.isUserRoot()) { %>
<button id="importCoefficients" type="button" class="btn btn-default hidden hidden-print">Importa</button>
<button id="importApartments" type="button" class="btn btn-default hidden hidden-print">Importa</button>
<button id="importModCoefficients" type="button" class="btn btn-default hidden hidden-print">Importa Indecsi</button>
<% } %>
</td><td id="viewfilter"></td></tr></table>
</script>
<script type="text/template" id="tpl-main">
<nav class="navbar navbar-default" role="navigation">
<div class="container-fluid">
<a class="navbar-brand" href="#">ADMIN ONLINE</a>
<p class="navbar-text" id="association"></p>
<div class="navbar-right">
<form method="post" action="index.php" id="exitform" class="form-inline">
<button type="button" class="btn btn-default navbar-btn" id="contact">Contacteaza-ne</button>
<button type="submit" class="btn btn-default navbar-btn">Iesire</button>&nbsp;
</form></div>
</div></nav>
<table class="table"><tr><td class="hidden-print" style="width:16%">
<span id="summary_content"></span>
<% if (stairs && state.id_stair) { %>
<div class="form-group"><select class="form-control" id="stairs">
<% _.each(stairs, function(s) { %>
<option value="<%=s.id_stair%>" <% if (s.id_stair == state.id_stair) print('selected'); %> >
<%=s.name_stair%></option>
<% }); %>
</select></div>
<% } %>
<span id="upkeeps_content"></span>
<span id="apartments_content"></span>
<ul class="nav nav-tabs nav-stacked hidden-print">
<li><a href="#">Tabel intretinere</a></li>
<li><a href="#view/tables">Sumar cheltuieli</a></li>
</ul>
Incasari
<ul class="nav nav-tabs nav-stacked hidden-print">
	<li><a href="#edit/payments">Intretinere</a></li>
<% if (APPSTATE.isUserAdmin()) { %>
	<li><a href="#edit/apartments">Apartamente</a></li>
	<li><a href="#edit/series">Serii chitante</a></li>
<% } %>
</ul>
Gestiune
<ul class="nav nav-tabs nav-stacked hidden-print">
	<li><a href="#edit/expenses">Cheltuieli</a></li>
	<li><a href="#edit/quotas">Cote parte</a></li>
	<li><a href="#edit/mod_quotas">Repartizare</a></li>
<% if (!APPSTATE.isUserAdmin()) { %>
	<li><a href="#edit/indexes">Indecsi</a></li>
<% } %>
</ul>
<% if (APPSTATE.isUserAdmin()) { %>
Evidenta
<ul class="nav nav-tabs nav-stacked hidden-print">
	<li><a href="#edit/persons">Persoane</a></li>
	<li><a href="#edit/users">Utilizatori</a></li>
</ul>
<% } %>
Utile
<ul class="nav nav-tabs nav-stacked hidden-print">
	<li><a href="#chart/expenses">Analiza</a></li>
	<li><a href="#edit/messages">Anunturi</a></li>
	<li><a href="#edit/configuration">Configurare</a></li>
</ul>
</td><td>
<span id="message_content"></span>
<div id="content"></div>
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Confirmare</h4>
	</div>
	<div class="modal-body">
		<p>Sunteti sigur ca vreti sa stergeti ?</p>
	</div>
	<div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal" id="confirmDelete">Da</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Nu</button>
	</div>
	 </div>
	</div>
</td></tr></table>
<div class="modal fade" id="resetConfirmationModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Confirmare</h4>
	</div>
	<div class="modal-body">
		<p>Sunteti sigur ca vreti sa resetati parola ?</p>
	</div>
	<div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal" id="confirmReset">Da</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Nu</button>
	</div>
	 </div>
	</div>
</div>
<div class="modal fade" id="addPersonModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade" id="addApartmentModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade" id="addCoefficientModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade" id="addExpenseModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade" id="addUpkeepModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade" id="addSeriesModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade" id="addPaymentModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade" id="importCoefficientsModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade" id="importApartmentsModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade" id="importModCoefficientsModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade" id="addMessagesModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
<div class="modal fade" id="contactModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
</div></div>
<div style="bottom:0" class="hidden-print">Admin Online 3.1 © 2015 S.C. Prozium S.R.L.</div>
</script>
<script type="text/template" id="tpl-last-message">
<%if (last_message) { %>
<div class="alert alert-info alert-dismissible hidden-print" role="alert">
  <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Inchide</span></button>
  <%=last_message%>
</div> <% } %>
</script>
<script type="text/template" id="tpl-main-summary">
	<p>
	<% if (APPSTATE.isUserAdmin()) { print("De incasat"); } else { print("De platit"); }%>
	<%=APPSTATE.formatWithPrecision(summary)%> lei</p>
</script>
<script type="text/template" id="tpl-add-person">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Adauga persoana</h4>
      </div>
      <div class="modal-body" id="addPersonContainer">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Anuleaza</button>
        <button type="button" class="btn btn-primary" id="addPersonButton">Adauga</button>
      </div>
    </div>
  </div>
</script>
<script type="text/template" id="tpl-add-apartment">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Adauga apartament</h4>
      </div>
      <div class="modal-body">
		<form role="form">
			<div class="form-group" id="apartmentNumberGroup">
				<label for="apartmentNumber" class="control-label">Numar</label>
				<input id="apartmentNumber" type="text" class="form-control"/>
				<p class='help-block'></p>
			</div>
			<div class="form-group" id="apartmentNameGroup">
				<label for="apartmentName" class="control-label">Tip</label>
				<select id="apartmentName" class="form-control">
					<option value="Garsoniera">Garsoniera</option>
					<option value="O camera">O camera</option>
					<option value="2 camere">2 camere</option>
					<option value="3 camere">3 camere</option>
					<option value="4 camere">4 camere</option>
					<option value="Duplex">Duplex</option>
					<option value="Penthaus">Penthaus</option>
				</select>
				<p class='help-block'></p>
			</div>
			<div class="form-group">
				<label for="apartmentPerson" class="control-label">Contact</label>
				<select id="apartmentPerson" class="form-control">
				<% _.each(persons, function(p) { %>
				<option value="<%=p.id%>"><%=p.name%></option>
				<% }); %>
				</select>
			</div>
			<div class="form-group" id="apartmentCurrentGroup">
				<label for="apartmentCurrent" class="control-label">Restanta pe ultima luna</label>
				<input id="apartmentCurrent" type="text" class="form-control" value="0"/>
				<p class='help-block'></p>
			</div>
			<div class="form-group" id="apartmentPendingGroup">
				<label for="apartmentPending" class="control-label">Restanta anterioara</label>
				<input id="apartmentPending" type="text" class="form-control" value="0"/>
				<p class='help-block'></p>
			</div>
			<div class="form-group" id="apartmentPenaltyGroup">
				<label for="apartmentPenalty" class="control-label">Penalizare</label>
				<input id="apartmentPenalty" type="text" class="form-control" value="0"/>
				<p class='help-block'></p>
			</div>
		</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Anuleaza</button>
        <button type="button" class="btn btn-primary" id="addApartmentButton">Adauga</button>
      </div>
    </div>
  </div>
</script>
<script type="text/template" id="tpl-add-user">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Adauga utilizator</h4>
      </div>
      <div class="modal-body">
		<form role="form">
			<div class="form-group" id="userNameGroup">
				<label for="userName" class="control-label">Nume</label>
				<input id="userName" type="text" class="form-control"/>
				<p class='help-block'></p>
			</div>
			<div class="form-group" id="userPasswordGroup1">
				<label for="userPassword" class="control-label">Parola</label>
				<input id="userPassword" type="password" class="form-control"/>
				<p class='help-block'></p>
			</div>
			<div class="form-group" id="userPasswordGroup2">
				<label for="userPassword" class="control-label">Re-introdu parola</label>
				<input id="userPassword2" type="password" class="form-control"/>
				<p class='help-block'></p>
			</div>
			<div class="form-group">
				<label for="userPerson" class="control-label">Contact</label>
				<select id="userPerson" class="form-control">
				<% _.each(persons, function(p) { %>
				<option value="<%=p.id%>"><%=p.name%></option>
				<% }); %>
				</select>
			</div>
			<div class="form-group">
				<label for="userApartment" class="control-label">Acces</label>
				<select id="userApartment" class="form-control">
				<option value="0">Administrator</option>
				<% _.each(apartments, function(a) { %>
				<option value="<%=a.id%>"><%=a.number%></option>
				<% }); %>
				</select>
			</div>
		</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Anuleaza</button>
        <button type="button" class="btn btn-primary" id="addUserButton">Adauga</button>
      </div>
    </div>
  </div>
</script>
<script type="text/template" id="tpl-add-coefficient">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Adauga coeficient</h4>
      </div>
      <div class="modal-body">
		<form role="form">
			<div class="form-group" id="coefficientNameGroup">
				<label for="coefficientName" class="control-label">Nume</label>
				<input id="coefficientName" type="text" class="form-control"/>
				<p class='help-block'></p>
			</div>
		</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Anuleaza</button>
        <button type="button" class="btn btn-primary" id="addCoefficientButton">Adauga</button>
      </div>
    </div>
  </div>
</script>
<script type="text/template" id="tpl-add-expense">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Adauga cheltuiala</h4>
      </div>
      <div class="modal-body">
		<form role="form">
			<div class="form-group" id="expenseNameGroup">
				<label for="expenseName" class="control-label">Nume</label>
				<input id="expenseName" type="text" class="form-control"/>
				<p class='help-block'></p>
			</div>
			<div class="form-group" id="expenseTitleGroup">
				<label for="expenseTitle" class="control-label">Titlu</label>
				<input id="expenseTitle" type="text" class="form-control"/>
				<p class='help-block'></p>
			</div>
			<div class="form-group">
				<label for="expenseSupplier" class="control-label">Furnizor</label>
				<input id="expenseSupplier" type="text" class="form-control"/>
				<p class='help-block'></p>
			</div>
			<div class="form-group">
				<label for="expenseCoefficient" class="control-label">Coeficient</label>
				<select id="expenseCoefficient" class="form-control">
				<% _.each(coefficients, function(c) { %>
				<option value="<%=c.id%>"><%=c.name%></option>
				<% }); %>
				</select>
			</div>
			<div class="form-group">
				<label for="expenseUnit" class="control-label">Unitate</label>
				<select id="expenseUnit" class="form-control">
					<option></option>
					<option value="mp">Metri patrati</option>
					<option value="mc">Metri cubi</option>
				</select>
			</div>
		</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Anuleaza</button>
        <button type="button" class="btn btn-primary" id="addExpenseButton">Adauga</button>
      </div>
    </div>
  </div>
</script>
<script type="text/template" id="tpl-change-password">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Schimba parola</h4>
      </div>
      <div class="modal-body">
		<form role="form">
			<div class="form-group" id="oldPasswordGroup">
				<label for="oldPassword" class="control-label">Parola curenta</label>
				<input id="oldPassword" type="password" class="form-control"/>
				<p class='help-block'></p>
			</div>
			<div class="form-group" id="newPasswordGroup">
				<label for="newPassword" class="control-label">Parola noua</label>
				<input id="newPassword" type="password" class="form-control"/>
				<p class='help-block'></p>
			</div>
			<div class="form-group" id="newPasswordGroup2">
				<label for="newPassword2" class="control-label">Re-introdu parola</label>
				<input id="newPassword2" type="password" class="form-control"/>
				<p class='help-block'></p>
			</div>
		</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Anuleaza</button>
        <button type="button" class="btn btn-primary" id="changePasswordButton">Salveaza</button>
      </div>
    </div>
  </div>
</script>
<script type="text/template" id="tpl-add-series">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Adauga serie chitante</h4>
      </div>
      <div class="modal-body">
		<form role="form">
			<div class="form-group" id="seriesNameGroup">
				<label for="seriesName" class="control-label">Nume</label>
				<input id="seriesName" type="text" class="form-control"/>
				<p class='help-block'></p>
			</div>
		</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Anuleaza</button>
        <button type="button" class="btn btn-primary" id="addSeriesButton">Adauga</button>
      </div>
    </div>
  </div>
</script>
<script type="text/template" id="tpl-add-messages">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Adauga anunt</h4>
      </div>
      <div class="modal-body">
		<form role="form">
			<div class="form-group" id="messagesDateGroup">
				<label for="messagesDate" class="control-label">Data expirare mesaj</label>
				<div class='input-group date' id='messagesDatePicker'>
                    <input type='text' class="form-control" id="messagesDate" value="<%=defaultDate%>" data-format="DD/MM/YYYY"/>
                    <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div><p class='help-block'></p></div>
			<div class="form-group" id="messagesMsgGroup">
				<label for="messagesMsg" class="control-label">Mesaj</label>
				<textarea id="messagesMsg" class="form-control" rows="3"></textarea>
				<p class='help-block'></p>
			</div>
		</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Anuleaza</button>
        <button type="button" class="btn btn-primary" id="addMessagesButton">Adauga</button>
      </div>
    </div>
  </div>
</script>
<script type="text/template" id="tpl-add-payment">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Adauga chitanta</h4>
      </div>
      <div class="modal-body">
		<form role="form">
			<div class="form-group">
				<label for="paymentApartment" class="control-label">Apartment</label>
				<select id="paymentApartment" class="form-control">
				<% _.each(apartments, function(a) { %>
				<option value="<%=a.id%>"><%=a.number%></option>
				<% }); %>
				</select>
			</div>
			<div class="form-group" id="paymentValueGroup">
				<label for="paymentValue" class="control-label">Valoare</label>
				<input id="paymentValue" type="text" class="form-control" value="<%=total%>"/>
				<p class='help-block'></p>
			</div>
			<div class="form-group" id="paymentDateGroup">
				<label for="paymentDate" class="control-label">Data</label>
				<div class='input-group date' id='paymentDatePicker'>
                    <input type='text' class="form-control" id="paymentDate" value="<%=defaultDate%>" data-format="DD/MM/YYYY"/>
                    <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
			</div>
			<div class="form-group">
				<label for="paymentSeries" class="control-label">Serie</label>
				<select id="paymentSeries" class="form-control">
				<% _.each(series, function(s) { %>
				<option value="<%=s.id%>"><%=s.name%></option>
				<% }); %>
				</select>
			</div>
			<div class="form-group" id="paymentNumberGroup">
				<label for="paymentNumber" class="control-label">Numar</label>
				<input id="paymentNumber" type="text" class="form-control" value="<%=defaultNumber%>"/>
				<p class='help-block'></p>
			</div>
		</form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Anuleaza</button>
        <button type="button" class="btn btn-primary" id="addPaymentButton">Adauga</button>
      </div>
    </div>
  </div>
</script>
<script type="text/template" id="tpl-update-indexes">
<% var g = 0;
  _.each(expenses, function(e) {
	if (e.id_mod_type == 3) {
	g++;
	var index = _.find(indexes, function(i) {return i.id_expense == e.id;});
	if (!index) { index = {index1: 0, index2: 0, index3: 0, index1_old: 0, index2_old: 0, index3_old: 0, estimated: 0}; }%>
<table class="backgrid"><thead>
<tr><th class="renderable"><%=e.name%></th><th class="renderable">Index vechi</th><th class="renderable">Index nou</th><th class="renderable">Consum</th></tr></thead><tbody>
<tr><th class="renderable">Baia mare</th><td class="number-cell renderable"><%=index.index1_old.toFixed(APPSTATE.precision)%></td>
<td class="number-cell renderable">
<div class="form-group" id="<%=e.id%>Index1Group">
<input style="text-align:right" id="<%=e.id%>Index1" type="text" value="<%=index.index1 ? index.index1.toFixed(APPSTATE.precision) : ''%>" class="form-control" <%if (!APPSTATE.isUpkeepOpen()) {print('readonly');}%>/>
<p class='help-block'></p>
</div>
</td><td class="number-cell renderable" id="<%=e.id%>Total1"><%=((index.index1 ? index.index1 : index.index1_old) - index.index1_old).toFixed(APPSTATE.precision)%></td></tr>
<tr><th class="renderable">Baia mica</th><td class="number-cell renderable"><%=index.index2_old.toFixed(APPSTATE.precision)%></td>
<td class="renderable">
<div class="form-group" id="<%=e.id%>Index2Group">
<input style="text-align:right" id="<%=e.id%>Index2" type="text" value="<%=index.index2 ? index.index2.toFixed(APPSTATE.precision) : ''%>" class="form-control" <%if (!APPSTATE.isUpkeepOpen()) {print('readonly');}%>/>
<p class='help-block'></p>
</div>
</td><td class="number-cell renderable" id="<%=e.id%>Total2"><%=((index.index2 ? index.index2 : index.index2_old) - index.index2_old).toFixed(APPSTATE.precision)%>
</td></tr>
<tr><th class="renderable">Bucatarie</th><td class="number-cell renderable"><%=index.index3_old.toFixed(APPSTATE.precision)%></td><td class="renderable">
<div class="form-group" id="<%=e.id%>Index3Group">
<input style="text-align:right" id="<%=e.id%>Index3" type="text" value="<%=index.index3 ? index.index3.toFixed(APPSTATE.precision) : ''%>" class="form-control" <%if (!APPSTATE.isUpkeepOpen()) {print('readonly');}%>/>
<p class='help-block'></p>
</div>
</td><td class="number-cell renderable" id="<%=e.id%>Total3"><%=((index.index3 ? index.index3 : index.index3_old) - index.index3_old).toFixed(APPSTATE.precision)%></td></tr>
<tr><th class="renderable">Consum estimat anterior</th><td class="number-cell renderable"></td><td class="renderable">
</td><td class="number-cell renderable"><%=(index.estimated ? index.estimated : 0).toFixed(APPSTATE.precision)%></td></tr>
</tbody><tfoot><tr><th class="renderable">Total</th><td class="renderable"></td><td class="renderable"></td>
<td class="number-cell renderable" id="<%=e.id%>Total">
<%=((index.index1 ? index.index1 : index.index1_old) + (index.index2 ? index.index2 : index.index2_old) + (index.index3 ? index.index3 : index.index3_old) - (index.index1_old ? index.index1_old : 0) - (index.index2_old ? index.index2_old : 0) - (index.index3_old ? index.index3_old : 0) - (index.estimated ? index.estimated : 0)).toFixed(APPSTATE.precision)%>
</td></tr></tfoot></table><br/>
<% }});
	if (g == 0) { %><p>Nu aveti contoare.</p><br/>
<% } %>
</script>
<script type="text/template" id="tpl-view-total-indexes">
</script>
<script type="text/template" id="tpl-import-coefficients">
  <div class="modal-dialog">
    <div class="modal-content">
	<form role="form" id="importCoefficientsForm" action="/api/coefficient_values" enctype="multipart/form-data" method="post">
	  <input type="hidden" name="MAX_FILE_SIZE" value="100000"/>
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Importa cote parte</h4>
      </div>
      <div class="modal-body">
			<div class="form-group" id="importCoefficientsGroup">
				<label for="importCoefficientsFile" class="control-label">Fisier CSV</label>
				<input id="importCoefficientsFile" type="file" class="form-control" name="file"/>
				<p class='help-block'></p>
			</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Anuleaza</button>
        <button type="submit" class="btn btn-primary">Importa</button>
      </div>
	  </form>
    </div>
  </div>
</script>
<script type="text/template" id="tpl-import-apartments">
  <div class="modal-dialog">
    <div class="modal-content">
	<form role="form" id="importApartmentsForm" action="/api/import_apartments" enctype="multipart/form-data" method="post">
	  <input type="hidden" name="MAX_FILE_SIZE" value="100000"/>
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Importa apartamente</h4>
      </div>
      <div class="modal-body">
			<div class="form-group" id="importApartmentsGroup">
				<label for="importApartmentsFile" class="control-label">Fisier CSV</label>
				<input id="importApartmentsFile" type="file" class="form-control" name="file"/>
				<p class='help-block'></p>
			</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Anuleaza</button>
        <button type="submit" class="btn btn-primary">Importa</button>
      </div>
	  </form>
    </div>
  </div>
</script>
<script type="text/template" id="tpl-import-mod-coefficients">
  <div class="modal-dialog">
    <div class="modal-content">
	<form role="form" id="importModCoefficientsForm" action="/api/coefficient_mod_values" enctype="multipart/form-data" method="post">
	  <input type="hidden" name="MAX_FILE_SIZE" value="100000"/>
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Importa indecsi</h4>
      </div>
      <div class="modal-body">
			<div class="form-group" id="importModCoefficientsGroup">
				<label for="importModCoefficientsFile" class="control-label">Fisier CSV</label>
				<input id="importModCoefficientsFile" type="file" class="form-control" name="file"/>
				<p class='help-block'></p>
			</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Anuleaza</button>
        <button type="submit" class="btn btn-primary">Importa</button>
      </div>
	  </form>
    </div>
  </div>
</script>
<script type="text/template" id="tpl-contact">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Formular de contact</h4>
      </div>
      <div class="modal-body">
<form role="form">
<div class="form-group" id="contactSubjectGroup">
<label for="contactSubject">Subiect</label>
<input id="contactSubject" type="text" class="form-control"/>
<p class='help-block'></p>
</div>
<div class="form-group" id="contactMessageGroup">
<label for="contactMessage" class="control-label">Mesaj</label>
<textarea id="contactMessage" class="form-control" rows="3"></textarea>
<p class='help-block'></p>
</div><div class="modal-footer">
<button id="send" type="button" class="btn btn-default hidden-print">Trimite</button>
</div></form></div></div>
</script>
<script type="text/template" id="tpl-define-configuration">
<form role="form">
<div class="form-group hidden-print">
<label for="configRate">Dobanda penalizare pe zi (%)</label>
<input id="configRate" type="text" class="form-control" value="<%=configuration.rate%>"/>
</div>
</form>
</script>
<script type="text/template" id="tpl-define-expenses">
<div id="configuration"></div>
<div id="expenses"></div>
</script>
<script type="text/template" id="tpl-define-apartments">
<div id="apartments"></div>
</script>
<script type="text/template" id="tpl-define-users">
<div id="users"></div>
</script>
<script type="text/template" id="tpl-define-persons">
<div id="persons"></div>
</script>
<script type="text/template" id="tpl-edit-coefficients">
<div id="edit_coefficients"></div>
</script>
<script type="text/template" id="tpl-edit-coefficients-footer">
<tr><td class="string-cell renderable">TOTAL</td>
<% _.each(total, function(k) {
	print('<td  class="number-cell renderable">' + k.toFixed(APPSTATE.precision) + '</td>');
}); %>
</tr>
</script>
<script type="text/template" id="tpl-edit-coefficient-mods">
<div id="edit_coefficient_mods"></div>
</script>
<script type="text/template" id="tpl-view-coefficient-mods-cell">
<%=theType%>
<%=theValue ? theValue.toFixed(APPSTATE.precision) : ''%>
</script>
<script type="text/template" id="tpl-edit-coefficient-mods-cell">
<div class="modal-dialog">
<div class="modal-content">
<form role="form">
<div class="modal-header"><button type="reset" class="close" >&times;</button>
<h3><%- column.get("label") %></h3></div>
<div class="modal-body">
<div class="form-group">
<label for="coefficientModCellType" class="control-label">Tip</label>
<select id="coefficientModCellType" class="form-control" <%if (!APPSTATE.isUpkeepOpen()) {print('disabled');}%>>
<% _.each(theOptions, function(c) { %>
<option <% if (c[1] == theType) print('selected'); %> value="<%=c[1]%>"><%=c[0]%></option>
<% }); %>
</select>
</div>
<div class="form-group <% if (theType != 1) print('hidden');%>" id="coefficientModCellTab1">
</div>
<div class="form-group <% if (theType != 2) print('hidden');%>" id="coefficientModCellTab2">
<label for="coefficientModCellValue2" class="control-label">Cota</label>
<input id="coefficientModCellValue2" type="text" value="<%=(theValue ? theValue : 0).toFixed(APPSTATE.precision)%>" class="form-control" <%if (!APPSTATE.isUpkeepOpen()) {print('readonly');}%>/>
</div>
<div class="form-group <% if (theType != 3) print('hidden');%>" id="coefficientModCellTab3">
<table class="backgrid"><thead>
<tr><th class="renderable"></th><th class="renderable">Index vechi</th><th class="renderable">Index nou</th><th class="renderable">Consum</th></tr></thead><tbody>
<tr><th class="renderable">Baia mare</th><td class="number-cell renderable"><%=index.index1_old.toFixed(APPSTATE.precision)%></td>
<td class="number-cell renderable">
<div class="form-group" id="coefficientModCellIndex1Group">
<input style="text-align:right; border: 1px solid #CCC; height: 34px" id="coefficientModCellIndex1" type="text" value="<%=index.index1 ? index.index1.toFixed(APPSTATE.precision) : ''%>" class="form-control" <%if (!APPSTATE.isUpkeepOpen()) {print('readonly');}%>/>
<p class='help-block'></p>
</div>
</td><td class="number-cell renderable" id="coefficientModCellTotal1"><%=((index.index1 ? index.index1 : index.index1_old) - index.index1_old).toFixed(APPSTATE.precision)%></td></tr>
<tr><th class="renderable">Baia mica</th><td class="number-cell renderable"><%=index.index2_old.toFixed(APPSTATE.precision)%></td>
<td class="renderable">
<div class="form-group" id="coefficientModCellIndex2Group">
<input style="text-align:right; border: 1px solid #CCC; height: 34px" id="coefficientModCellIndex2" type="text" value="<%=index.index2 ? index.index2.toFixed(APPSTATE.precision) : ''%>" class="form-control" <%if (!APPSTATE.isUpkeepOpen()) {print('readonly');}%>/>
<p class='help-block'></p>
</div>
</td><td class="number-cell renderable" id="coefficientModCellTotal2"><%=((index.index2 ? index.index2 : index.index2_old) - index.index2_old).toFixed(APPSTATE.precision)%>
</td></tr>
<tr><th class="renderable">Bucatarie</th><td class="number-cell renderable"><%=index.index3_old.toFixed(APPSTATE.precision)%></td><td class="renderable">
<div class="form-group" id="coefficientModCellIndex3Group">
<input style="text-align:right; border: 1px solid #CCC; height: 34px" id="coefficientModCellIndex3" type="text" value="<%=index.index3 ? index.index3.toFixed(APPSTATE.precision) : ''%>" class="form-control" <%if (!APPSTATE.isUpkeepOpen()) {print('readonly');}%>/>
<p class='help-block'></p>
</div>
</td><td class="number-cell renderable" id="coefficientModCellTotal3"><%=((index.index3 ? index.index3 : index.index3_old) - index.index3_old).toFixed(APPSTATE.precision)%></td></tr>
<tr><th class="renderable">Consum estimat anterior</th><td class="number-cell renderable"></td><td class="renderable">
</td><td class="number-cell renderable"><%=(index.estimated ? index.estimated : 0).toFixed(APPSTATE.precision)%></td></tr>
</tbody><tfoot><tr><th class="renderable">Total</th><td class="renderable"></td><td class="renderable"></td>
<td class="number-cell renderable" id="coefficientModCellTotal">
<%=((index.index1 ? index.index1 : index.index1_old) + (index.index2 ? index.index2 : index.index2_old) + (index.index3 ? index.index3 : index.index3_old) - (index.index1_old ? index.index1_old : 0) - (index.index2_old ? index.index2_old : 0) - (index.index3_old ? index.index3_old : 0) - (index.estimated ? index.estimated : 0)).toFixed(APPSTATE.precision)%>
</td></tr></tfoot></table><br/>
</div>
<div class="form-group <% if (theType != 4) print('hidden');%>" id="coefficientModCellTab4">
<label for="coefficientModCellValue4" class="control-label">Valoare</label>
<input id="coefficientModCellValue4" type="text" value="<%=(theValue ? theValue : 0).toFixed(APPSTATE.precision)%>" class="form-control"/>
</div>
</div><div class="modal-footer">
<button type="reset" class="btn btn-default">Anuleaza</button>
<button type="submit" class="btn btn-primary" id="saveMod" <%if (!APPSTATE.isUpkeepOpen()) {print('disabled');}%>>Salveaza</button>
</div>
</form>
</div></div>
</script>
<script type="text/template" id="tpl-define-series">
<div id="edit_series"></div>
</script>
<script type="text/template" id="tpl-define-messages">
<div id="edit_messages"></div>
</script>
<script type="text/template" id="tpl-define-payments">
<div id="edit_payments"></div>
</script>
<script type="text/template" id="tpl-define-apartments-footer">
<tr><% if (canSelectRow) { %><td class="string-cell renderable"></td><% } %><td class="string-cell renderable">TOTAL</td>
<td class="string-cell renderable"></td>
<td class="number-cell renderable"><%=total[0].toFixed(APPSTATE.precision)%></td>
<td class="number-cell renderable"><%=total[1].toFixed(APPSTATE.precision)%></td>
<td class="number-cell renderable"><%=total[2].toFixed(APPSTATE.precision)%></td>
<td class="number-cell renderable"><%=total[3].toFixed(APPSTATE.precision)%></td>
<td class="string-cell renderable"></td></tr>
</script>
<script type="text/template" id="tpl-define-payments-footer">
<tr><% if (canSelectRow) { %><td class="string-cell renderable"></td><% } %><td class="string-cell renderable">TOTAL</td>
<td class="string-cell renderable"></td><td class="string-cell renderable"></td>
<td class="number-cell renderable"><%=total.toFixed(APPSTATE.precision)%></td><td class="string-cell renderable"></td></tr>
</script>
<script type="text/template" id="tpl-define-expenses-footer">
<tr><% if (canSelectRow) { %><td class="string-cell renderable"></td><% } %><td class="string-cell renderable">TOTAL</td>
<td class="string-cell renderable"></td><td class="string-cell renderable"></td><td class="string-cell renderable"></td><td class="string-cell renderable"></td>
<td class="number-cell renderable"><%=total[0].toFixed(APPSTATE.precision)%></td><td class="number-cell renderable"><%=total[1].toFixed(APPSTATE.precision)%></td></tr>
</script>
<script type="text/template" id="tpl-view-total-table">
<tr>
<td class="string-cell renderable">TOTAL</td>
<% _.each(total, function(k) {
	print('<td  class="number-cell renderable">' + k.toFixed(APPSTATE.precision) + '</td>');
}); %>
</tr>
</script>
<script type="text/template" id="tpl-view-table">
<strong><div class="visible-print text-center">
Tabel de intretinere pentru luna <%=APPSTATE.get('name_upkeep')%><br/>
<%=APPSTATE.get('association').name%>
</div></strong><br/>
<div id="table"></div>
<div class="visible-print"><br/>
<h4><span class="text-right label label-default">Tabel generat de www.adminonline.ro</span></h4>
<strong><span>Data afisarii: <%=(moment().format('D MMMM YYYY'))%></span></strong>
<br/><br/><div class="row">
<div class="col-md-3">Presedinte</div>
<div class="col-md-3">Cenzor</div><div class="col-md-3">Administrator</div>
</div>
</div>
</script>
<script type="text/template" id="tpl-view-tables">
<div class="row">
<% _.each(tables, function(t) {
	if (t) { %>
	<div class="col-md-3"><table class="backgrid"><tbody>
	<tr><th class="renderable" colspan="2">Cheltuieli <%=t.name%></td></tr>
	<%
	var s = 0;
	_.each(t.expenses, function(e) {
		if (!e.quantity) {
			s += e.value;
	%>
	<tr><td class="renderable"><%=e.name%></td><td class="renderable"><%=APPSTATE.formatWithPrecision(e.value)%></td></tr>
	<% }});
	if (t.expenses.length > 1) { %>
	<tr><th class="renderable">Total</th><td class="renderable"><%=APPSTATE.formatWithPrecision(s)%></td></tr>
	<% } %>
	<tr><td class="renderable"><%=t.unit%></td><td class="renderable"><%=APPSTATE.formatWithPrecision(t.total)%></td></tr>
	<tr><td class="renderable">Lei / <%=t.unit%></td><td class="renderable"><%=APPSTATE.formatWithPrecision(t.total == 0 ? 0 : (s / t.total))%></td></tr>
	<%
	if ('apartments' in t) {
	var sortable = [];
	for (var p in t.apartments) {
		sortable.push([p, t.apartments[p]]);
	}
	sortable.sort(function(a, b) { return a[1] - b[1]; });
	_.each(sortable, function(a) { %>
	<tr><td class="renderable"><%=a[0]%> <%=a[1]%> <%=t.unit%></td><td class="renderable"><%=APPSTATE.formatWithPrecision(t.total == 0 ? 0 : (a[1] * s / t.total))%></td></td></tr>
	<% }); } %>
	</tbody></table></div>
<% }});
_.each(tables, function(t) {
	if (t) { %>
<div class="col-md-3"><table class="backgrid"><tbody>
	<% _.each(t.expenses, function(e) {
		if (e.quantity) {
	%>
	<tr><th class="renderable" colspan="2">Cheltuieli <%=e.name%></td></tr>
	<tr><td class="renderable">Valoare factura</td><td class="renderable"><%=APPSTATE.formatWithPrecision(e.value)%></td></tr>
	<tr><td class="renderable"><%=e.unit%></td><td class="renderable"><%=APPSTATE.formatWithPrecision(e.quantity)%></td></tr>
	<tr><td class="renderable"><%=e.unit%> / <%=t.unit%></td><td class="renderable"><%=APPSTATE.formatWithPrecision(e.quantity / t.total)%></td></tr>
	<tr><td class="renderable">Lei / <%=e.unit%></td><td class="renderable"><%=APPSTATE.formatWithPrecision(e.value / e.quantity)%></td></tr>
	<% }}); %>
</tbody></table></div>
<% }}); %>
</div>
<div class="visible-print"><br/>
<h4><span class="text-right label label-default">Tabele generate de www.adminonline.ro</span></h4>
<strong>Data afisarii: <%=(moment().format('D MMMM YYYY'))%></strong>
<br/><br/><div class="row">
<div class="col-md-3">Presedinte</div>
<div class="col-md-3">Cenzor</div><div class="col-md-3">Administrator</div></div>
</div>
</script>
<script type="text/template" id="tpl-edit-configuration">
	<form role="form">
		<div class="form-group" id="personNameGroup">
			<label for="personName" class="control-label">Nume</label>
			<input id="personName" type="text" class="form-control" value="<%=person.name%>" <%if (!APPSTATE.isUserAdmin()) {print('readonly');}%>/>
			<p class='help-block'></p>
		</div>
		<div class="row">
		<div class="form-group col-sm-4" id="personTelephoneGroup">
			<label for="personTelephone" class="control-label">Telefon</label>
			<input id="personTelephone" type="text" class="form-control" value="<%=person.telephone%>"/>
			<p class='help-block'></p>
		</div>
		<div class="form-group col-sm-4" id="personEmailGroup">
			<label for="personEmail" class="control-label">E-Mail</label>
			<input id="personEmail" type="email" class="form-control" value="<%=person.email%>"/>
			<p class='help-block'></p>
		</div>
		<div class="form-group col-sm-2">
			<label for="personNotify" class="control-label">Primeste notificari</label>
			<input id="personNotify" type="checkbox" class="form-control" <%if (person.notify) print ('checked');%>/>
		</div>
		</div>
		<div class="form-group">
			<label for="personRole" class="control-label">Rol</label>
			<select id="personRole" class="form-control" <%if (!APPSTATE.isUserAdmin()) {print('disabled');}%>>
			<% _.each(personRoles, function(a) { %>
			<option value="<%=a.id%>" <%if (person.id_person_role == a.id) print('selected');%>><%=a.name%></option>
			<% }); %>
			</select>
		</div>
		<div class="form-group">
			<label for="personJob" class="control-label">Pozitie</label>
			<select id="personJob" class="form-control" <%if (!APPSTATE.isUserAdmin()) {print('disabled');}%>>
			<% _.each(personJobs, function(a) { %>
			<option value="<%=a.id%>" <%if (person.id_person_job == a.id) print('selected');%>><%=a.name%></option>
			<% }); %>
			</select>
		</div>
		<div class="form-group">
			<label for="personApartment" class="control-label">Apartment</label>
			<select id="personApartment" class="form-control" <%if (!APPSTATE.isUserAdmin()) {print('disabled');}%>>
			<option/>
			<% _.each(apartments, function(a) { %>
			<option value="<%=a.id%>" <%if (person.id_apartment == a.id) print('selected');%>><%=a.number%></option>
			<% }); %>
			</select>
		</div>
		<% if (APPSTATE.isUserRoot()) { %>
		<div class="checkbox">
			<label>
			<input id="stairErrors" type="checkbox" <%if (stair.errors_column) print('checked');%>/>Afiseaza coloana Rotunjire
			</label>
		</div>
		<div class="checkbox">
			<label>
			<input id="stairPayments" type="checkbox" <%if (stair.payments_column) print('checked');%>/>Afiseaza coloana Plati
			</label>
		</div>
		<% } %>
	</form>
</script>
<script type="text/template" id="tpl-legend">
<form id="legendForm">
<% _.each(chart.datasets, function(d) { %>
<span class="title" style="border-color: <%=d.strokeColor%>">
<input type="checkbox" name="<%=d.title%>" <%if ($.inArray(d.title, checked) != -1) print('checked');%>/><%=d.title%></span>
<% }); %>
</form>
</script>
<script type="text/template" id="tpl-chart-expenses">
<div class="row">
<div class="col-lg-9">
<canvas id="chartExpenses" width="800" height="400"></canvas>
</div>
<div class="col-lg-1">
<form role="form" id="typeForm">
	<div class="form-group">
		<div class="radio">
			<label>
			<input type="radio" name="charttype" checked/>
			Bloc
			</label>
		</div>
		<div class="radio">
			<label>
			<input type="radio" name="charttype" value="me"/>
			Apartament
			</label>
		</div>
		<% if (APPSTATE.isUserAdmin()) { %>
		<label class="control-label">Apartament
		<select disabled="disabled" id="chartApartment" class="form-control">
		<option/>
		<% _.each(apartments, function(a) { %>
			<option value="<%=a.number%>"><%=a.number%></option>
		<% }); %>
		</select></label>
		<% } %>
	</div><br/>
	<div class="form-group">
		<div class="radio">
			<label>
			<input type="radio" name="chartvalue" value="value" checked/>
			Valoare
			</label>
		</div>
		<div class="radio">
			<label>
			<input type="radio" name="chartvalue" value="quantity"/>
			Cantitate
			</label>
		</div>
	</div>
	<div id="chartslider" class="form-group">
	</div>
</form></div><div class="col-lg-1">
<div id="legendExpenses" class="legend"></div></div></div>
</script>
<script type="text/template" id="tpl-chart-period">
	<label class="control-label">Perioada (luni)
	<% if (period > 1) { %>
	<select id="chartoptions" class="form-control">
		<% for (var i = 2; i <= period; i += 2) { %>
		<option value="<%=i%>" <% if (i == Math.min(6, period)) print("selected"); %>><%=i%></option>
		<% } %>
	</select>
	</label>
	<% } %>
</script>
<link rel="stylesheet" href="css/bootstrap.min.css"/>
<link rel="stylesheet" href="css/bootstrap-datetimepicker.min.css"/>
<link rel="stylesheet" href="css/backgrid.min.css"/>
<link rel="stylesheet" href="css/backgrid-filter.min.css" />
<link rel="stylesheet" href="css/backgrid-select-all.min.css" />
<link rel="stylesheet" href="css/tenants.css"/>
<!--[if lt IE 9]>
<script src="js/lib/html5shiv.js"></script>
<script src="js/lib/respond.min.js"></script>
<script src="js/lib/excanvas.compiled.js"></script>
<![endif]-->
<script src="js/lib/jquery-1.11.2.min.js"></script>
<script src="js/lib/jquery-migrate-1.2.1.min.js"></script>
<script src="js/lib/jquery.form.min.js"></script>
<script src="js/lib/underscore-min.js"></script>
<script src="js/lib/json2.js"></script>
<script src="js/lib/backbone-min.js"></script>
<script src="js/lib/backgrid.min.js"></script>
<script src="js/lib/backgrid-filter.min.js"></script>
<script src="js/lib/backgrid-select-all.min.js"></script>
<script src="js/lib/spin.min.js"></script>
<script src="js/lib/Chart.min.js"></script>
<script src="js/lib/moment-with-langs.min.js"></script>
<script src="js/lib/bootstrap.min.js"></script>
<script src="js/lib/bootstrap-datetimepicker.js"></script>
<script src="js/tenants.js"></script>
</head>
<body>
<div class="container" id="main"></div>
<script>
var spinner = new Spinner({
  lines: 13,
  length: 20,
  width: 10,
  radius: 30,
  corners: 1,
  rotate: 0,
  direction: 1,
  color: '#000',
  speed: 1,
  trail: 60,
  shadow: false,
  hwaccel: false,
  className: 'spinner',
  zIndex: 2e9
});
$(document).ajaxStart(function(){ spinner.spin(document.getElementById('main')); });
$(document).ajaxStop(function(){ spinner.stop(); });
var APPSTATE = new AppState(<?=get_state()?>);
APPSTATE.filterDelay = 150;
APPSTATE.precision = 2;
APPSTATE.language = 'ro';
moment.lang(APPSTATE.language);
var options = {user: new User(<?=json_encode($_SESSION['user'])?>),
	stairs: new StairCollection(<?=json_encode(get_stairs())?>),
	userApartments: new ApartmentCollection(<?=get_user_apartments()?>),
	messages: new MessageCollection(<?=get_messages()?>),
	apartments: new ApartmentCollection(<?=get_apartments()?>),
	coefficients: new CoefficientCollection(<?=get_coefficients()?>),
	expenses: new ExpenseCollection(<?=get_expenses()?>),
	users: new UserCollection(<?=get_users()?>),
	persons: new PersonCollection(<?=get_persons()?>),
	personRoles: new PersonRoleCollection(<?=get_person_roles()?>),
	personJobs: new PersonJobCollection(<?=get_person_jobs()?>),
	modTypes: new ModTypeCollection(<?=get_mod_types()?>),
	coefficientValues: new CoefficientValueCollection(<?=get_coefficient_values()?>),
	coefficientModValues: new CoefficientModValueCollection(<?=get_coefficient_mod_values()?>),
	table: new Table(<?=get_table()?>),
	configuration: new Configuration(<?=get_configuration()?>),
	series: new InvoiceSeriesCollection(<?=get_series()?>),
	payments: new PaymentCollection(<?=get_payments()?>),
	indexes: new IndexCollection(<?=get_indexes()?>)
};
APPSTATE.user = options.user;
var APP = new AppRouter(options);
Backbone.history.start();
</script>
</body>
</html>
<?php } ?>

