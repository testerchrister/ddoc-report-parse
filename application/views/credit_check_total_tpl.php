<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<section class="masthead d-flex text-white">
	<div class="container text-center jumbotron">
		<h1>Credit Check Total Report Parser</h1>
		<div class="col-md-6 offset-md-3">
		<form method="post" enctype="multipart/form-data">
			<fieldset>
				<input type="file" name="userfile" class="btn btn-primary btn-xl form-control" required="required">
			</fieldset>
			<fieldset>
				<input type="password" name="passcode" placeholder=" Enter Passcode" required="required" maxlength="4" class="form-control">
			</fieldset>
			<fieldset>
				<button class="btn btn-success btn-xl js-scroll-trigger">Submit</button>
			</fieldset>
		</form>
		</div>
		<div>
			<?php
				if($error):
			?>	
			<div class="alert alert-dismissible alert-warning">
			  <button type="button" class="close" data-dismiss="alert">&times;</button>
			  <h4 class="alert-heading">Warning!</h4>
			  <p class="mb-0"><?=$error?></p>
			</div>
		<?php endif;?>
		<?php if ($parse_report) :	
			foreach($parse_report as $pr) :
				if ($pr['status'] == "success") :
		?>
			<div class="alert alert-dismissible alert-success">
			  <button type="button" class="close" data-dismiss="alert">&times;</button>
			  <h4 class="alert-heading"><?=$pr['status']?></h4>
			  <p class="mb-0"><?=$pr['message']?></p>
			</div>
			<?php
				else:
			?>
			<div class="alert alert-dismissible alert-warning">
			  <button type="button" class="close" data-dismiss="alert">&times;</button>
			  <h4 class="alert-heading"><?=$pr['status']?></h4>
			  <p class="mb-0"><?=$pr['message']?></p>
			</div>
		<?php endif; endforeach; endif;?>
		</div>
	</div>
</section>