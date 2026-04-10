@extends('layouts.back-end.app')

@section('title', translate('rooms'))

@section('content')
<div class="content container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h1 mb-0">
            <i class="tio-home"></i> {{ translate('rooms') }}
        </h2>
    </div>
    <div class="mb-3">
        <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary">
            <i class="tio-add"></i> {{ translate('add_new_room') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">

            <table class="table table-hover table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>ID</th>
                        <th>{{ translate('image') }}</th>
                        <th>{{ translate('hotel') }}</th>
                        <th>{{ translate('room_type') }}</th>
                        <th>{{ translate('price') }}</th>
                        <th>{{ translate('availability') }}</th>
                        <th>{{ translate('status') }}</th>
                        <th class="text-center">{{ translate('action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rooms as $key => $room)
                        <tr>
                            <td>{{ $rooms->firstItem() + $key }}</td>

                            <td>
                                @php
                                    $gallery = is_array($room->gallery) ? $room->gallery : json_decode($room->gallery ?? '[]', true);
                                    $previewImage = $room->featured_image ?: ($gallery[0] ?? null);

                                    if ($previewImage) {
                                        if (filter_var($previewImage, FILTER_VALIDATE_URL)) {
                                            $previewImageUrl = $previewImage;
                                        } elseif (strpos($previewImage, 'storage/app/hotel/hotel/') === 0) {
                                            $previewImageUrl = asset('public/' . ltrim($previewImage, '/'));
                                        } else {
                                            $previewImageUrl = asset('storage/' . ltrim($previewImage, '/'));
                                        }
                                    }
                                @endphp

                                @if($previewImage)
                                    <img src="{{ $previewImageUrl }}"
                                         alt="{{ $room->room_type }}"
                                         style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px;">
                                @else
                                    <span class="text-muted">{{ translate('no_image') }}</span>
                                @endif
                            </td>

                            <td>
                                <strong>{{ $room->hotel->name ?? '-' }}</strong>
                            </td>

                            <td>{{ $room->room_type }}</td>

                            <td>
                                {{ getWebConfig('currency_symbol') }}
                                {{ $room->single_sale_price ?? $room->single_price }}
                            </td>

                            <td>
                                <span class="badge badge-info">
                                    {{ $room->rooms_available }}
                                </span>
                            </td>

                            <td>
                                <span class="badge badge-{{ $room->status ? 'success' : 'danger' }}">
                                    {{ $room->status ? translate('active') : translate('inactive') }}
                                </span>
                            </td>

                            <td class="text-center">
                                <a href="{{ route('admin.rooms.edit', $room->id) }}"
                                   class="btn btn-sm btn-outline--primary">
                                    <i class="tio-edit"></i>
                                </a>

                                <form action="{{ route('admin.rooms.delete', $room->id) }}"
                                      method="POST"
                                      class="d-inline-block"
                                      onsubmit="return confirm('{{ translate('are_you_sure') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="tio-delete"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">
                                {{ translate('no_data_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $rooms->links() }}
            </div>

        </div>
    </div>
</div>
@endsection