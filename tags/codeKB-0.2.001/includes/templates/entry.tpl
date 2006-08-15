<br />

<div class="entry">
	<div class="entry_header">
		$ckb[icon]
		$ckb[name]
	</div>

	<div class="entry_subheader">
		$ckb[subheader]
	</div>
	
	<div class="entry_content">
		<br />
		<if $ckb[description]>
			<em>
				$ckb[description]
			</em>
			<br /><br /><br />
		</if>
			$ckb[documentation]
		<br /><br />
	</div>
	
	<if $ckb[attachments]>
		<div class="forms">
			<fieldset>
				<legend>
					$ckb[attachments]
				</legend>
		
				<table style="margin: auto">
					$ckb[files]
				</table>
			</fieldset>
			<br />
			<br />
		</div>
	</if>
</div>
<br /><br />
		