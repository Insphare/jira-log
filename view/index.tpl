<h1>Jira Zeitlogger</h1>
<div class="errorBox box hide"></div>
<div class="successBox box hide"></div>
<div class="user {$userHide}">{$user}</div>

<a href="/log" id="log">TimeSheet</a>

<div>
	<div class="left wrapperLeft">
		<table>
			<tr>
				<th colspan="2">
					Konfiguration
				</th>
			</tr>
			<tr>
				<td>Host</td>
				<td>{$host} <input type="button" value="Verbindung testen" id="testConnection" /></td>
			</tr>
			<tr id="rvs" class="hide">
				<td>RVS-Weiche</td>
				<td>
					<input type="text" name="timetrack" id="timetrack" value="{$session.timetrack}" />
					<input type="button" value="Ermitteln" id="getTimeTrack" />
				</td>
			</tr>
		</table>

		<table>
			<tr>
				<th colspan="2">
					Autorisierung
				</th>
			</tr>
			<tr>
				<td>Benutzer</td>
				<td><input type="text" name="username" id="username" value="{$session.username}" /></td>
			</tr>
			<tr>
				<td>Passwort</td>
				<td>
					<input type="password" name="password" id="password" value="" />
					<input type="button" name="callProfile" value="Profil aufrufen" id="callProfile" />
				</td>
			</tr>
		</table>

		<table>
			<tr>
				<th colspan="2">
					Activity-Sheet
				</th>
			</tr>
			<tr>
				<td>
					<textarea name="sheet" id="sheet">{$session.sheet}</textarea>
					<br/>
					<input type="button" value="Vorschau" id="preview" />
				</td>
			</tr>
		</table>
	</div>
	<div class="left wrapperRight">
		<div class="preview hide" id="ajaxContent"></div>
	</div>
</div>
<div class="fix"></div>




