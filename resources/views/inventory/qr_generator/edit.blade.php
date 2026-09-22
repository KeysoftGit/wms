@extends('layouts.admin')

@section('titles')
    <title>Keysoft NLA WMS - Edit QR</title>
@endsection

@section('content')
    @include('inventory.qr_generator.form', [
        'title' => 'Edit QR',
        'action' => route('inventory.qr_generator.update'),
        'qr' => $qr,
        'payload' => $qr->json_value['data'] ?? [],
    ])
@endsection
