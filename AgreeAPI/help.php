<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="Description" content="Agree | Help">

	<title>Daniel Oliver</title>
	<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
</head>
<body>
	<div class="container">
		<h1>Agree | Help</h1>
		<h2>Anatomy of a request</h2>
		<h3>Request type</h3>
		<p>The API does not distinguish between different HTTP verbs. All arguments sent through GET, POST, or PUT are collected and routed according to the <code>action</code> parameter without regard to the verb.</p>
		
		<h3>Action parameter</h3>
		<p>The <code>action</code> parameter spells out the user’s intent.</p>

		<h3>Authentication</h3>
		<p>Every request, with the exception of the help actions, requires authentication. This can be in the form of an <code>email</code> and <code>password</code> or the <code>token</code> that is returned with each successful email and password authentication. The token expires after an hour of inactivity.</p>

		<table class="table table-striped">
			<thead>
				<tr>
					<th>Action</th>
					<th>Expected Parameters<br><small>in addition to authentication</small></th>
					<th>Expected Output</th>
					<th>Error Output</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th></th>
					<td><code></code></td>
					<td><code></code></td>
					<td></td>
				</tr>
				<tr>
					<th></th>
					<td><code></code></td>
					<td><code></code></td>
					<td></td>
				</tr>
				<tr>
					<th></th>
					<td><code></code></td>
					<td><code></code></td>
					<td></td>
				</tr>
				<tr>
					<th></th>
					<td><code></code></td>
					<td><code></code></td>
					<td></td>
				</tr>
				<tr>
					<th></th>
					<td><code></code></td>
					<td><code></code></td>
					<td></td>
				</tr>
		</table>
	</div>
</body>
</html>