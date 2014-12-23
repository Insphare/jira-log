<tr class="bookItem" data-issue="{$issue}">
	<td>
		{$issue}
	</td>
	<td>
		{$summary}
	</td>
	<td>
		<input id="{$issue}_start" class="startDate smallInput" type="text" value="{$startDay}" />&nbsp;
		<input id="{$issue}_start" class="startHour smallInput" type="number" value="{$startHour}" max="23" min="0" />:<input id="{$issue}_start" class="startMinute smallInput" type="number" value="{$startMin}" max="59" min="0" /> Uhr
	</td>
	<td>
		<input id="{$issue}_time" class="numberHour smallInput" type="number" value="{$timeHour}" max="12" min="0" />h&nbsp;
		<input id="{$issue}_time" class="numberMinute smallInput" type="number" value="{$timeMin}" step="15" max="45" min="0" />m
	</td>
	<td>
		<textarea class="smallInput comment" id="{$issue}_text">{$comment}</textarea>
	</td>
</tr>
