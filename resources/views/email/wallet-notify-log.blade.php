<h2>Wallet Status</h2>
<p><strong>User:</strong> {{ $log->user->email }}</p>
<p><strong>Type:</strong> {{ ucfirst($log->type) }}</p>
<p><strong>Action:</strong> {{ $log->action }}</p>
<p><strong>Message:</strong> {{ $log->message }}</p>
<pre><code>{{ json_encode($log->context, JSON_PRETTY_PRINT) }}</code></pre>