<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<section class="masthead d-flex text-white">
	<nav><a href="<?=base_url()?>" class="btn btn-secondary"><< Back</a></nav>
	<div class="container text-center jumbotron" id="form-container">
		<h1>Credit Check Total Report Parser</h1>
		<div class="col-md-6 offset-md-3">
		<form method="post" enctype="multipart/form-data" id="cctForm" action="ajax_post">
			<fieldset>
				<input type="file" name="userfile" class="btn btn-primary btn-xl form-control" required="required" id="user-file">
			</fieldset>
			<fieldset>
				<input type="password" name="passcode" placeholder=" Enter Passcode" required="required" maxlength="4" class="form-control">
			</fieldset>
			<fieldset>
				<button class="btn btn-success btn-xl js-scroll-trigger" id="submit-btn">Submit</button>
			</fieldset>
			<div id="targetLayer"></div>
		</form>
		<div class="progress" id="progress">
		 	<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="progress-bar"></div>
		</div>
		<span class="progress-txt">File Upload <i id="progress-count">0</i>% Completed</span>
		</div>
		<div class="parse-updates">
			<div class="progress">
		 		<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" id="progress-bar-total"></div>
		 		<span id="tot-progress"><i id="tot-progress-count">0</i>% Completed</span>
			</div>
			<div class="cur-parse-process">
				<div class="progress-status" id="status-message"></div>
				<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" id="process-progress-bar"></div>
			</div>
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
		var doc_info;
		var last_msg = '';
		var tot_progress = 0;
		$('#progress').hide(); 
		$('.progress-txt').hide();
		$('#tot-progress').hide();
    	$('#cctForm').submit(function(e) {	
    		e.preventDefault();
    		$('#progress').show();
    		$('.progress-txt').show();
    		$('#tot-progress').show();
    		$(this).ajaxSubmit({
    			target: '#targetLayer',
    			beforeSubmit: function(){
    				$('#progress-bar').width('0%');
    				$('#progress-bar-total').width('0%');
    				$('#user-file').attr('disabled','disabled');
    				$('#submit-btn').attr('disabled','disabled');
    			},
    			uploadProgress: function(event, position, total, percentComplete){
    				$('#progress-bar').width(percentComplete + '%');
    				$('#progress-count').html(percentComplete);
    				$('#progress-bar').attr('aria-valuenow', percentComplete);
    			},
    			success: function(){
    				var response = $('#targetLayer').html();
    				console.log(response);
    				try{
    					doc_info = JSON.parse(response);
    				} catch(e){
    					console.log(e);
    					$('#status-message').html("Failed to upload the document and unable to create document profile. Please try again!");
    					return false;
    				}
    				$('#status-message').html(doc_info.progress);
    				doc_id = doc_info.doc_id;
    				if (doc_id != undefined && !isNaN(doc_id)) {
    					trackParsing(doc_id);
    					initDocParsing(doc_id);
    				}
    			},
    		});
    		return false;
    	});

    	function trackParsing(doc_id)
    	{
    		var $url = "<?=base_url('cct_progress')?>";
			$data = {"doc_id":doc_id};
    		$.ajax({
				url: $url,
				type: "POST",
				data: $data,
				dataType: "json", //html,xml,script,json,jsonp,text
				success: function(data, textStatus) {
					console.log(data);
					if(last_msg != data.progress) {
						var $prev_msg = $('#status-message').html();
						$('#status-message').html("<span>"+data.progress+"</span><br />" + $prev_msg);	
						last_msg = data.progress;						
					}

					if (tot_progress < 95) {
		    			setTimeout(function() {
		    				trackParsing(doc_id);
							$('#progress-bar-total').width(tot_progress+'%');
							$('#tot-progress-count').html(tot_progress);
							tot_progress++;
						}, 5000);		
		    		} else if(data.status == "completed") {
		    			$('#progress-bar-total').width('100%');
						$('#tot-progress-count').html("100");
						$('#user-file').removeAttr('disabled');
    					$('#submit-btn').removeAttr('disabled');
		    			return;
		    		} else {
		    			setTimeout(function() {
		    				trackParsing(doc_id);
		    			}, 5000);
		    		}
				}
			});
    	}

    	function initDocParsing(doc_id)
    	{
    		if (isNaN(doc_id) || doc_id == undefined) {
    			return false;
    		}
    		var $url = "<?=base_url('cct_init')?>";
    		$data = {"doc_id":doc_id};
    		$('#status-message').html("<span>Document parsing started..</span><br/>");
    		$.ajax({
				url: $url,
				type: "POST",
				data: $data,
				dataType: "json", //html,xml,script,json,jsonp,text
				success: function(data, textStatus) {
					console.log(data);			
					try{
						var $prev_msg = $('#status-message').html();
						$('#status-message').html($prev_msg + "<span>"+data.progress+"<span><br/>");
						$('#progress-bar-total').width('100%');
						$('#tot-progress-count').html('100');
					}catch(e){
						return false;
					}
				}
			});
			//trackParsing(doc_id);
    	}
    });
</script>