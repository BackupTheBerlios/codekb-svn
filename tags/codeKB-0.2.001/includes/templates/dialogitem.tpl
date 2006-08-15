<if $ckb[top]>

	<div class="forms2 dialog_top dialog_back">
		$ckb[top]
	</div>
</if>

<div class="forms2 dialog_back">
	
	$ckb[head]
	<if $ckb[content2]>
		<table style="width: 100%;">
			<tr>
				<td style=" vertical-align: top;">
	</if>
		$ckb[content1]
	<if $ckb[content2]>
				</td>
				<td style=" vertical-align: top;">
		$ckb[content2]
				</td>
		<if $ckb[content3]>
				<td style=" vertical-align: top;">
		$ckb[content3]
				</td>
		</if>
			</tr>
		</table>
	</if>
	$ckb[tail]

</div>
<br />