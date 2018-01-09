<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<section class="masthead d-flex text-white">
	<div class="container text-center">
		<h1>Identity IQ Report Parser</h1>
		<div class="col-md-6 offset-md-3">
		<form method="post" enctype="multipart/form-data" id="iiqForm" action="iiq_parser">
			<fieldset>
				<input type="file" name="userfile" class="btn btn-primary btn-xl form-control" required="required" id="user-file">
			</fieldset>
			<fieldset>
				<button class="btn btn-success btn-xl js-scroll-trigger" id="submit-btn">Submit</button>
			</fieldset>
			<div id="targetLayer"></div>

		</form>
		<div id="progress-upload">
		<div class="progress">
		 	<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100" id="progress-bar-upload"></div>
		</div>
		<span class="progress-txt">File uploading <i id="progress-count"></i></span>
		</div>
		<div class="parse-updates">
			<div class="progress">
		 		<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="progress-bar-total"></div>
			</div>
			<span id="tot-progress"><i id="tot-progress-count"></i></span>
			<div class="cur-parse-process">
				<div class="progress-status" id="status-message"></div>
			</div>
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
		var url = "<?=base_url('iiq_parser')?>";
		var tot_progress = 0;
    	$('#iiqForm').submit(function(e) {	
    		e.preventDefault();
    		if($('#userfile').val() == "") {
    			return;
    		}
    		$('#progress-upload').show();
    		$('.progress-txt').show();
    		$(this).ajaxSubmit({
    			target: '#targetLayer',
    			beforeSubmit: function(){
    				$('#progress-bar-upload').width('0%');
    				$('#user-file').attr('disabled','disabled');
    				$('#submit-btn').attr('disabled','disabled');
    			},
    			uploadProgress: function(event, position, total, percentComplete){
    				$('#progress-bar-upload').width(percentComplete + '%');
    				$('#progress-bar-upload').attr('aria-valuenow', percentComplete);
    				$('#progress-count').html(percentComplete + "%");
    			},
    			success: function(){
    				var response = $('#targetLayer').html();
    				var doc_info;
    				try{
    					doc_info = JSON.parse(response);
    				} catch(e) {
    					$('#status-message').html("Failed to upload the document and unable to create document profile. Please try again!");
    					return false;
    				}
    				doc_id = doc_info.doc_id;
    				if (doc_id!= undefined && !isNaN(doc_id)) {
    					$('#status-message').html(doc_info.progress);
    					trackParsing(doc_id);
    					initDocParsing(doc_id);
    				} else {
    					$('#status-message').html(doc_info.progress);
    				}
    			},
    			resetForm: true
    		});
    		return false;
    	});

    	function initDocParsing(doc_id)
    	{
    		if (isNaN(doc_id) || doc_id == undefined) {
    			return false;
    		}
    		$('.parse-updates').show();
    		var $url = "<?=base_url('iiq_init')?>";
    		$data = {"doc_id":doc_id};    		
    		$.ajax({
				url: $url,
				type: "POST",
				data: $data,
				dataType: "json", //html,xml,script,json,jsonp,text
				success: function(data, textStatus) {
					console.log(data);			
					try{
						$('#status-message').html("<span>"+data.progress+"<span>");
						$('#progress-bar-total').width('100%');
						$('#tot-progress-count').html('Document Parsing Progress 100%');
					}catch(e){
						return false;
					}
				}
			});
    	}

    	function trackParsing(doc_id)
    	{
    		var $url = "<?=base_url('iiq_progress')?>";
			$data = {"doc_id":doc_id};
    		$.ajax({
				url: $url,
				type: "POST",
				data: $data,
				dataType: "json", //html,xml,script,json,jsonp,text
				success: function(data, textStatus) {
					console.log(data);
					if(data.progress) {
						$('#status-message').html("<span>"+data.progress+"</span>");
					}

					 if(data.status == "completed") {
		    			$('#progress-bar-total').width('100%');
						$('#tot-progress-count').html("Document Parsing Progress 100%");
						$('#user-file').removeAttr('disabled');
    					$('#submit-btn').removeAttr('disabled');
		    			return;
		    		} else if (tot_progress < 95) {
		    			setTimeout(function() {
		    				trackParsing(doc_id);
							$('#progress-bar-total').width(tot_progress+'%');
							$('#tot-progress-count').html("Document Parsing Progress " + tot_progress + "%");
							tot_progress += 5;
						}, 1000);		
		    		} else {
		    			setTimeout(function() {
		    				trackParsing(doc_id);
		    			}, 1000);
		    		}
				}
			});
    	}
    });
</script>