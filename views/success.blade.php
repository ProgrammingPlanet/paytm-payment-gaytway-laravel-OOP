
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Shortner</title>
	<link rel="stylesheet" href="">
	<link rel="stylesheet" href="{{URL::to('/')}}/assets/libs/bootstrap4/css/bootstrap.min.css">
	<script src="{{URL::to('/')}}/assets/libs/jquery/jquery.min.js"></script>
	<link href="https://fonts.googleapis.com/css?family=Courgette|Literata|Lora|Pacifico" rel="stylesheet">
	<style type="text/css" media="screen">
		body{font-family: 'Lora', cursive; font-size: medium; font-weight: bold;}
	</style>
</head>
<body>
	<div class="container my-5"><br>
		<h3 class="text-center" style="font-family: 'Pacifico';"> Thank You! </h3>
		<div class="container mt-5 mx-auto">
			<div class="col-lg-5 col-12 mx-auto d-block">
				@foreach($params as $key => $value)
					@if(array_search($key, ['','TXNID','ORDERID','TXNAMOUNT','RESPMSG','GATEWAYNAME','TXNDATE']))
						{{ $key.' :: '.$value }} <br>
					@endif
				@endforeach
			</div>
			
		</div>
		
		
		
		
		
		
	</div>
</body>
</html>