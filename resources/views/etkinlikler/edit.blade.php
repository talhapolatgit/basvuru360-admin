@extends('layouts.admin')

@section('title', 'Etkinlik Düzenle')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Etkinlik Yönetimi</p>
        <h1 class="page-title">{{ $etkinlik->ad }}</h1>
        <p class="page-subtitle">#{{ $etkinlik->etkinlik_no }} — etkinlik bilgilerini güncelleyin.</p>
    </div>
    <x-back-button :href="route('etkinlikler.show', $etkinlik)" />
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

<form method="POST" action="{{ route('etkinlikler.update', $etkinlik) }}" class="etkinlik-form">
    @csrf
    @method('PUT')
    @include('etkinlikler._form', ['etkinlik' => $etkinlik])
    <div class="form-footer">
        <x-back-button :href="route('etkinlikler.show', $etkinlik)" icon="close">İptal</x-back-button>
        <x-cta-button type="submit" icon="save">Değişiklikleri Kaydet</x-cta-button>
    </div>
</form>
@endsection

@push('scripts')
@include('etkinlikler._form_scripts')
@endpush
