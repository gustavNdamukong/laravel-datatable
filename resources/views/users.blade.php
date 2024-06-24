@extends('layouts.main.app')

@section('content')

    <!-- ==========================
    BREADCRUMB - START
    =========================== -->
    <!-- Hero Header Start -->
    <div class="container-xxl py-5 bg-primary hero-header mb-5">
        <div class="container my-5 py-5 px-lg-5">
            <div class="row g-5 py-5">
                <div class="col-12 text-center">
                    <h1 class="text-white animated zoomIn">Users</h1>
                    <hr class="bg-white mx-auto mt-0" style="width: 90px;">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center">
                            <li class="breadcrumb-item"><a class="text-white" href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-white" href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Users</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- Hero Header End -->
    <!-- ==========================
        BREADCRUMB - END
    =========================== -->



    <!-- ==========================
        PAGE CONTENT - START
    =========================== -->
    <div class="container-xxl bg-white py-5">
        <div class="container px-lg-5">
            <div class="row g-5">
                <div class="col-sm-12 col-md-12 col-lg-12 wow fadeInUp" data-wow-delay="0.1s">
                    <h2 class="hidden">Users</h2>
                    <div class="row viewText">

                        {!! $usersTable !!}

                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection