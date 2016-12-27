<?php
require_once('inc/user.php');

if(isset($_GET['logout'])) {
	// remove all session variables
	session_unset(); 

	// destroy the session 
	session_destroy(); 
}

// check if user is authenticated
if(!$_SESSION['authenticated']) {

	// check if user sent the login form
	if(isset($_POST['username']) && isset($_POST['password'])) {
		$user_id = login($_POST['username'], $_POST['password']);

		if($user_id != -1) {
			session_regenerate_id(); // avoid session fixation exploit
			$_SESSION['authenticated'] = true;

			$user =  new User($user_id);
			$_SESSION['user'] = $user->serialize();

			$navigation = renderNavigation($user);

			$profile = "<a href='./index.php?logout'>Logout</a>";
		}
		else {
			$profile = null;
			$title = null;
			$navigation = null;
			$content = null;

			include_once('login.php');
		}
	}
	else {
		$profile = null;
		$title = null;
		$navigation = null;
		$content = null;

		include_once('login.php');
	}
}
else {
	$profile = "<a href='./index.php?logout'>Logout</a>";
}

if(!$content_class)
	$content_class = "basic";
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="description" content="[cityrock] Verwaltungsplattform">
	<meta name="keywords" content="Kletterhalle, Klettern, Kletterzentrum, Stuttgart, cityrock">
	<title>[cityrock] Verwaltungsplattform</title>
	<link href="<?php echo $root_directory; ?>/styles/style.min.css" rel="stylesheet" type="text/css">
	<link href="<?php echo $root_directory; ?>/styles/bootstrap.min.css" rel="stylesheet" type="text/css">
	<link href="<?php echo $root_directory; ?>/styles/bootstrap-datetimepicker.min.css" rel="stylesheet" type="text/css">
	<!--Bootstrap Choosen -->

	<link href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.6.2/chosen.min.css" rel="stylesheet" type="text/css">

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>

	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
	<script src="//cdn.ckeditor.com/4.5.11/basic/ckeditor.js"></script>




	<script></script>
</head>
<body>
	<!-- header -->
	<header class="header">
		<div class="container">

			<div class="header-info">
				<?php include ('content_header.php')?>
			</div>

		</div>
	</header>
	
	<div class="container">
		<?php if(!$hide_navigation && count($navigation)): ?>
		<!-- navigation -->
		<nav class="navigation" id="navigation">
			<?php echo $navigation; ?>
		</nav>
		<?php endif; ?>
	
		<!-- content -->
		<div class="<?php echo $content_class; ?>">
			<?php 
				echo "<h2>" . $title . "</h2>";
				echo "<p>{$content}</p>"; 
			?>
		</div>
	</div>

	<!-- footer -->
	<footer class="footer">
		<div class="reference">
			<!--
			Powered by <a href="http://www.clowdfish.com" target="_blank">clowdfish.com</a>
			<img src="<?php echo $root_directory; ?>/images/clowdfish.png" alt="clowdfish Logo" />
			-->
		</div>
	</footer>
	<nav class="context-menu" id="context-menu">
	  <ul class="context-menu-list">
	    <li class="context-menu-item">
	      <a href="#" class="context-menu-link cancel" user-id='{$_SESSION['user']['id']}' >
	        <i class="fa fa-times"></i> Aussetzen
	      </a>
	    </li>
	  </ul>
	</nav>

  <script type="text/javascript" src="<?php echo $root_directory; ?>/scripts/script.min.js"></script>
  <script type="text/javascript" src="<?php echo $root_directory; ?>/scripts/validator.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.15.1/moment.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.15.1/locale/de.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.14.30/js/bootstrap-datetimepicker.min.js"></script>

	<!-- Chosen Bootstrap -->

	<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.5.1/prism.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.6.2/chosen.jquery.js"></script>
	<script> jQuery(document).ready(function(){
			jQuery('.chosen-select').chosen({width: "100%"});
		});</script>

	<script>CKEDITOR.replace( 'comment' );</script>





</body>
</html>
