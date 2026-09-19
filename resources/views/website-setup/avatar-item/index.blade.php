@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="table-content table-basic mt-20">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="btn-group">
                        @foreach (App\Models\LearningEngine\AvatarItem::CATEGORIES as $catKey => $catLabel)
                            <a href="{{ route('avatar-item.index', ['category' => $catKey]) }}"
                                class="btn {{ $data['category'] === $catKey ? 'ot-btn-primary' : 'btn-outline-secondary' }}">{{ $catLabel }}</a>
                        @endforeach
                    </div>
                    @if (hasPermission('avatar_item_create'))
                        <a href="{{ route('avatar-item.create', ['category' => $data['category']]) }}" class="btn btn-lg ot-btn-primary">
                            <span><i class="fa-solid fa-plus"></i> </span>
                            <span>{{ ___('common.add') }}</span>
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    <p class="text-secondary mb-3">
                        @if ($data['category'] === 'avatar')
                            The base character itself — every student always has exactly one of these equipped. Set a price in Coins to make one purchasable, or 0 to give it to every student for free.
                        @elseif ($data['category'] === 'outfit')
                            A clothing layer worn over the base character (shirt, hoodie, dress, etc.). Optional — a student can go without one.
                        @elseif ($data['category'] === 'hat')
                            A headwear layer worn on top of everything else (cap, headphones, goggles, etc.). Optional.
                        @else
                            Small extras a student can wear several of at once (glasses, backpack, a held prop, ...). Optional.
                        @endif
                    </p>
                    <p class="text-secondary mb-3">
                        <i class="fa-solid fa-circle-info"></i>
                        For layers to line up on the student's avatar, upload every image (across all four categories) at the exact same canvas size and with the character in the exact same position — e.g. 500&times;650px, transparent background. Mismatched artwork will still work, it just won't line up visually.
                    </p>

                    @if (hasPermission('avatar_item_delete') || hasPermission('avatar_item_update'))
                        @include('backend.partials.bulk-actions-bar')
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    @if (hasPermission('avatar_item_delete') || hasPermission('avatar_item_update'))
                                        <th style="width:36px"><input type="checkbox" id="bulkSelectAll"></th>
                                    @endif
                                    <th style="width:70px">{{ ___('common.image') }}</th>
                                    <th>{{ ___('common.title') }}</th>
                                    <th>{{ ___('settings.price_in_coins') }}</th>
                                    <th>{{ ___('common.Serial') }}</th>
                                    <th>{{ ___('common.status') }}</th>
                                    @if (hasPermission('avatar_item_update') || hasPermission('avatar_item_delete'))
                                        <th class="action">{{ ___('common.action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['items'] as $row)
                                    <tr id="row_{{ $row->id }}">
                                        @if (hasPermission('avatar_item_delete') || hasPermission('avatar_item_update'))
                                            <td><input type="checkbox" class="bulk-row-checkbox" value="{{ $row->id }}"></td>
                                        @endif
                                        <td>
                                            @if ($row->image)
                                                <img src="{{ globalAsset($row->image) }}" alt="{{ $row->name }}" style="height:44px;width:44px;border-radius:50%;object-fit:cover;">
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $row->name }}</td>
                                        <td>{{ $row->price_coins > 0 ? $row->price_coins . ' 🪙' : ___('common.free') }}</td>
                                        <td>{{ $row->sort_order }}</td>
                                        <td>
                                            @if ($row->status == App\Enums\Status::ACTIVE)
                                                <span class="badge-basic-success-text">{{ ___('common.active') }}</span>
                                            @else
                                                <span class="badge-basic-danger-text">{{ ___('common.inactive') }}</span>
                                            @endif
                                        </td>
                                        @if (hasPermission('avatar_item_update') || hasPermission('avatar_item_delete'))
                                            <td class="action">
                                                <div class="dropdown dropdown-action">
                                                    <button type="button" class="btn-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa-solid fa-ellipsis"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @if (hasPermission('avatar_item_update'))
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('avatar-item.edit', $row->id) }}">
                                                                    <span class="icon mr-8"><i class="fa-solid fa-pen-to-square"></i></span>{{ ___('common.edit') }}
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if (hasPermission('avatar_item_delete'))
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0);"
                                                                    onclick="delete_row('avatar-item/delete', {{ $row->id }})">
                                                                    <span class="icon mr-8"><i class="fa-solid fa-trash-can"></i></span>
                                                                    <span>{{ ___('common.delete') }}</span>
                                                                </a>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color">
                                            <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                            <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="ot-pagination pagination-content d-flex justify-content-end align-content-center py-3">
                        <nav>
                            <ul class="pagination justify-content-between">
                                {!! $data['items']->links() !!}
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    @include('backend.partials.delete-ajax')
@endpush
@include('backend.partials.bulk-actions-ajax', ['bulkRoute' => 'avatar-item'])
