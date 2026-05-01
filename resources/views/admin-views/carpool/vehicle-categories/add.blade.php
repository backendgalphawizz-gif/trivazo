@extends('layouts.back-end.app')

@section('title', translate('add_vehicle_category'))

@section('content')
<div class="content container-fluid">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0">{{ translate('add_vehicle_category') }}</h2>
        <div class="ml-auto">
            <a href="{{ route('admin.carpool.vehicle-categories.list') }}" class="btn btn-outline-primary">
                <i class="tio-arrow-back-ios"></i> {{ translate('back') }}
            </a>
        </div>
    </div>

    <div class="card max-w-720">
        <div class="card-body">
            <form action="{{ route('admin.carpool.vehicle-categories.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>{{ translate('name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="100">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group mb-0">
                    <label class="d-flex align-items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <span>{{ translate('active') }}</span>
                    </label>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn--primary">{{ translate('save_category') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
