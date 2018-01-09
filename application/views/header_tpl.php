<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html>
<head>
	<title>Identity IQ Report Parser</title>
	 <link href="<?=base_url("assets/bootstrap/css/bootstrap.min.css")?>" rel="stylesheet">
	    <!-- Custom Fonts -->
    <link href="<?=base_url("assets/font-awesome/css/font-awesome.min.css")?>" rel="stylesheet" type="text/css">
    <link rel="stylesheet" type="text/css" href="<?=base_url("assets/css/stylish-portfolio.min.css")?>">
    <link href="<?=base_url("assets/css/style.css")?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,700,300italic,400italic,700italic" rel="stylesheet" type="text/css">
    <script src="<?=base_url("assets/jquery/jquery.min.js")?>"></script>
</head>
<body id="page-top">
	<nav class="navbar navbar-expand-lg navbar-light bg-light">
  		<div class="logo-container">
			<img src="<?=base_url('assets/img/logo.png')?>">
		</div>
		<ul class="navbar-nav ml-auto">
		<?php if(isset($_SESSION['user_id'])) :	?>
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="true"> DOC Parser</a>
				<div class="dropdown-menu" aria-labelledby="download">
                <a class="dropdown-item" href="<?=base_url("iiq")?>">Identity IQ</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="<?=base_url("cct")?>">Credit Check Total</a>
              </div>
			</li>
			<li class="nav-item active">
        		<a href="<?=base_url('logout')?>" class="nav-link disabled btn-logout">Logout</a>
      		</li>			
		<?php endif;?>
  	</nav>