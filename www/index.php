<?php
	session_start();
	session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Online</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<link rel="stylesheet" href="css/bootstrap.min.css"/>
<!--[if lt IE 9]>
<script src="js/lib/html5shiv.js"></script>
<script src="js/lib/respond.min.js"></script>
<script src="js/lib/excanvas.compiled.js"></script>
<![endif]-->
<script src="js/lib/jquery-1.11.2.min.js"></script>
<script src="js/lib/jquery-migrate-1.2.1.min.js"></script>
<script src="js/lib/bootstrap.min.js"></script>
<script>
function validation() {
	var hasError = false;
	if (login.username.value == '') {
		$('#usernameGroup').addClass('has-error');
		hasError = true;
	}
	if (login.password.value == '') {
		$('#passwordGroup').addClass('has-error');
		hasError = true;
	}
	return hasError ? false : true;
}
</script>
</head>
<body>
<div class="container">
<div class="page-header">
<h1>Admin Online</h1>
</div>
<h4>Aplicatie pentru calculul tabelului de intretinere pentru asociatiile de proprietari. Aplicatia este oferita gratuit.<br/>
Folositi contul demonstrativ demo / demo. Pentru un cont real va rog contactati administratorul si veti primi acces in cel mai scurt timp.
</h4>
<?php if (array_key_exists('error', $_GET)) { ?>
<div class="alert alert-danger alert-dismissible hidden-print" role="alert">
  <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Inchide</span></button>
  Utilizatorul sau parola sunt gresite. Incercati din nou.
</div>
<?php } ?>
<div class="row"><div class="col-md-1"></div><div class="col-md-3">
<div class="panel panel-default">
<div class="panel-heading">Aplicatie</div>
<div class="panel-body">
<form name="login" method='post' action='main.php' role="form" onsubmit="return validation();">
<input type="hidden" name="test" value="<?php if (isset($_GET['test'])) { echo 1; } else { echo '0'; } ?>"/>
<div class="form-group" id="usernameGroup">
<label for="username">Utilizator</label>
<input class="form-control" name='username'/>
</div>
<div class="form-group" id="passwordGroup">
<label for="password">Parola</label>
<input class="form-control" type='password' name='password'/>
</div>
<div align="center">
<button type="submit" class="btn btn-primary">Conecteaza</button>
</div>
</form>
</div></div>
</div>
<div class="col-md-6">
<img class="img-thumbnail" width="200" src="img/thumb1.png"/>
<img class="img-thumbnail" width="200" src="img/thumb2.png"/>
<img class="img-thumbnail" width="200" src="img/thumb3.png"/>
<img class="img-thumbnail" width="200" src="img/thumb4.png"/>
</div>
</div>
<div style="bottom:0">Admin Online 3.1 © 2015 Cristian Mocanu</div></div>
</body>
</html>
