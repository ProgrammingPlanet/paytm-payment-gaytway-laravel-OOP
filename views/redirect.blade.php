<title>Merchant Check Out Page</title>
<center><br>
	<h1>Please do not refresh this page...</h1>
</center>
<form method="POST" action="{{$txnurl}}" name="f1">
	@foreach($paramList as $name => $value)
		<input type="hidden" name="{{$name}}" value="{{$value}}">
	@endforeach
	<input type="hidden" name="CHECKSUMHASH" value="{{ $checkSum }}">
</form>
<script type="text/javascript">
	document.f1.submit();
</script>