<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container">	
	<div class="masthead type-container">
		<h2>Select Your Report Type</h2>
		<div class="report-types">
			<div class="card text-white bg-success mb-3 report-type-card">
				<div class="card-header"><h3>Identity IQ<h3></div>
				<div class="card-body">
					<p>You report document must be in HTML format downloaded from identityiq.com</p>
					<a href="<?=base_url("iiq")?>" class="btn btn-info" id="btn-iiq">Proceed</a>
				</div>
			</div>
			<div class="card text-white bg-secondary mb-3 report-type-card">
				<div class="card-header"><h3>Credit Check Total</h3></div>
				<div class="card-body">
					<p>Your credit report document must be in PDF format downloaded from creditchecktotal.com</p>
					<a href="<?=base_url("cct")?>" class="btn btn-info" id="btn-cct">Proceed</a>
				</div>
			</div>
		</div>
	</div>
</div>