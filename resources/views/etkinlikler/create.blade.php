@extends('layouts.admin')

@section('title', 'Yeni Etkinlik')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Etkinlik Yönetimi</p>
        <h1 class="page-title">Yeni Etkinlik</h1>
        <p class="page-subtitle">Temel bilgileri, tarihleri ve başvuru koşullarını tanımlayın.</p>
    </div>
    <x-back-button :href="route('etkinlikler.index')" />
</div>

@if ($errors->any())
    <div class="alert alert-error mb-4">
        <strong>Formda hatalar var.</strong>
        <ul class="mt-2 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('etkinlikler.store') }}" class="etkinlik-form">
    @csrf
    @include('etkinlikler._form', ['etkinlik' => null])
    <div class="form-footer">
        <x-back-button :href="route('etkinlikler.index')" icon="close">İptal</x-back-button>
        <x-cta-button type="submit" icon="save">Etkinliği Kaydet</x-cta-button>
    </div>
</form>
@endsection

@push('scripts')
@include('etkinlikler._form_scripts')
@endpush
