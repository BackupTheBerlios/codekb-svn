
<if $ckb[first]>
	<tr>
</if>
	<td style="text-align:right; vertical-align: top;">
	
		$ckb[icon]
		
	</td>
	<td style="vertical-align: top">
	
		$ckb[name]
		
		<if $ckb[count]>
			<span class="category_descr">
								(<span title="$ckb[catdescr]">$ckb[catcount]</span>/<span title="$ckb[entdescr]">$ckb[entrycount]</span>)
			</span>			
		</if>
		<br />
		<span class="category_descr">
			$ckb[description]
		</span>
		
	</td>
	
<if $ckb[last]>
	</tr>
</if>