<!DOCTYPE HTML>
<html class="no-js" lang="en-gb" xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">
	<head>
		<!-- ==========================
			Meta Tags
		=========================== -->
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<!-- CSRF Token -->
		<meta name="csrf-token" content="{{ csrf_token() }}">

			@if(isset($globalSeoMetadata))
				{!! $globalSeoMetadata !!}
			@endif 

			@if(isset($pageSeoMetadata))
				{!! $pageSeoMetadata !!}
			@else
				<title>@yield('title', config('app.name'))</title>
			@endif

		<!-- Google Web Fonts -->
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

		<!-- Icon Font Stylesheet -->
		<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
		<link href="{{ asset('node_modules/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet">

		<!-- Scripts -->
		@yield('top-scripts')

		<!-- Styles -->
		@yield('styles')
		@include('layouts.main.html_dependencies_top')
	</head>
	<body>

		<!-- ==========================
            SCROLL TOP - START
        =========================== -->
		<div id="scrolltop" class="hidden-xs"><i class="fa fa-angle-up"></i></div>
		<!-- ==========================
            SCROLL TOP - END
        =========================== -->

		<div id="page-wrapper" class="bg-white"> 

			  <!-- ==========================
				  HEADER - START
			  =========================== -->
			  @include('layouts.main.header')
			  <!-- ==========================
				  HEADER - END
			  =========================== -->


			  @if (is_array($errors) && count($errors) > 0 || $errors->any())
				<div class="alert alert-danger alert-error position-relative">
					@if (is_array($errors))
					<p>
						@foreach ($errors as $error)
							{{ $error }}<br />
						@endforeach
					</p>
					@else
					<p>
						@foreach ($errors->all() as $error)
							{{ $error }}<br />
						@endforeach
					</p>
					@endif
				</div>
			@endif

			@if(session('success'))
				<div class="alert alert-success">
					<p>{{ session('success') }}</p>
				</div>
			@endif

			@if(session('danger'))
				<div class="alert alert-danger">
					<p>{{ session('danger') }}</p>
				</div>
			@endif


			<!-- ==========================
			  PAGE CONTENT - START
		    =========================== -->
			@yield('content')
			<!-- ==========================
			  PAGE CONTENT - END
			=========================== -->


			<section>
				<div class="well">
					<div id="consent-popup" class="consent-hidden">
						<p>Be aware that we use cookies to improve your experience, and nothing more <a href="#"> Link to your Terms & Conditions or Data Policy here</a>.
							<a href="#" id="accept-cookie-use" class="btn btn-primary rounded-pill animated slidInRight">Okay</a>
						</p>
					</div>
					<!-- ==========================
						  FOOTER - START
					  =========================== -->
					@include('layouts.main.footer')
					<!-- ==========================
						FOOTER - END
					=========================== -->
				</div>
			</section>
		</div>

        <!-- Scripts -->
        @yield('bottom-scripts')

		@include('layouts.main.html_dependencies_bottom')

	</body>
</html>