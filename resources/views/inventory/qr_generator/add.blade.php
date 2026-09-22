@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Add QR</title>
@endsection

@section('content')
    @include('inventory.qr_generator.form', [
        'title' => 'Add QR',
        'action' => route('inventory.qr_generator.store'),
        'qr' => null,
        'payload' => [],
    ])
@endsection
