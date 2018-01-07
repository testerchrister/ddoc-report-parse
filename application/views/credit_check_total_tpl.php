<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<section class="masthead d-flex text-white">
	<nav><a href="<?=base_url()?>" class="btn btn-secondary"><< Back</a></nav>
	<div class="container text-center jumbotron">
		<h1>Credit Check Total Report Parser</h1>
		<div class="col-md-6 offset-md-3">
		<form method="post" enctype="multipart/form-data" id="cctForm">
			<fieldset>
				<input type="file" name="userfile" class="btn btn-primary btn-xl form-control" required="required">
			</fieldset>
			<fieldset>
				<input type="password" name="passcode" placeholder=" Enter Passcode" required="required" maxlength="4" class="form-control">
			</fieldset>
			<fieldset>
				<button class="btn btn-success btn-xl js-scroll-trigger">Submit</button>
			</fieldset>
			<div id="targetLayer"></div>
		</form>
		<div class="progress" id="progress">
		 	<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="progress-bar"></div>
		</div>
		<span class="progress-txt">File Upload <i id="progress-count">0</i>% Completed</span>
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
<script type="text/javascript" src="<?=base_url("assets/js/jquery.form.mini.js")?>"></script>
<script type="text/javascript">
	$(document).ready(function() {
		var doc_id;
		$('#progress').hide(); 
		$('.progress-txt').hide();
    	$('#cctForm').submit(function(e) {	
    		e.preventDefault();
    		$('#progress').show();
    		$('.progress-txt').show();
    		$(this).ajaxSubmit({
    			target: '#targetLayer',
    			beforeSubmit: function(){
    				$('#progress-bar').width('0%');
    			},
    			uploadProgress: function(event, position, total, percentComplete){
    				$('#progress-bar').width(percentComplete + '%');
    				$('#progress-count').html(percentComplete);
    				$('#progress-bar').attr('aria-valuenow', percentComplete);
    			},
    			success: function(){
    				var doc_id = $('#targetLayer').html();    				
    				if (doc_id != undefined && !isNaN(doc_id)) {
    					initDocParsing(doc_id);
    					console.log(doc_id);
    				}
    			},
    		});
    		return false;
    	});

    	function initDocParsing(doc_id)
    	{
    		if (isNaN(doc_id) || doc_id == undefined) {
    			return false;
    		}
    		var $url = "<?=base_url('cct_parser')?>";
    		$data = {"method":"personal_info", "doc_id":doc_id};
    		$.ajax({
				url: $url,
				type: "POST",
				data: $data,
				dataType: "json", //html,xml,script,json,jsonp,text
				success: function(data, textStatus) {
					console.log(data);
				}
			});
    		return false;
    		$data2 = {"method":"personal_info", "doc_id":doc_id};
    		$.ajax({
				url: $url,
				type: "POST",
				data: $data2,
				dataType: "json", //html,xml,script,json,jsonp,text
				success: function(data, textStatus) {
					console.log(data);
				}
			});

			$data3 = '{"method":"personal_info", "doc_id":"'+doc_id+'"}';
    		$.ajax({
				url: $url,
				type: "POST",
				data: $data3,
				dataType: "json", //html,xml,script,json,jsonp,text
				success: function(data, textStatus) {
					console.log(data);
				}
			});

			$data4 = '{"method":"personal_info", "doc_id":"'+doc_id+'"}';
    		$.ajax({
				url: $url,
				type: "POST",
				data: $data4,
				dataType: "json", //html,xml,script,json,jsonp,text
				success: function(data, textStatus) {
					console.log(data);
				}
			});

			$data5 = '{"method":"personal_info", "doc_id":"'+doc_id+'"}';
    		$.ajax({
				url: $url,
				type: "POST",
				data: $data5,
				dataType: "json", //html,xml,script,json,jsonp,text
				success: function(data, textStatus) {
					console.log(data);
				}
			});
    	}
    });
</script>