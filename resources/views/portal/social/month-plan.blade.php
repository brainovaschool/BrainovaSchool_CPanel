@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">Social Board</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Month Plan</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="table-content table-basic mb-24">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">{{ now()->format('F Y') }} — targets vs actual</h4></div>
                <div class="card-body">
                    <p class="text-secondary">Counts any reel/carousel accepted or further along the pipeline, planned for this month.</p>
                    <table class="table table-bordered class-table">
                        <thead class="thead"><tr><th>Category</th><th>Reels</th><th>Carousels</th></tr></thead>
                        <tbody class="tbody">
                            @forelse ($data['plan'] as $row)
                                <tr>
                                    <td>{{ $row['category'] }}</td>
                                    <td>{{ $row['reel_actual'] }} / {{ $row['reel_target'] }}</td>
                                    <td>{{ $row['carousel_actual'] }} / {{ $row['carousel_target'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="100%" class="text-center gray-color">No category targets set yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if ($data['canManage'])
            <div class="card ot-card">
                <div class="card-header"><h4 class="mb-0">Edit targets</h4></div>
                <div class="card-body">
                    <form action="{{ route('portal-social-month-plan.targets') }}" method="post">
                        @csrf
                        <table class="table table-bordered class-table mb-3" id="targetsTable">
                            <thead class="thead"><tr><th>Category</th><th>Reel target / month</th><th>Carousel target / month</th></tr></thead>
                            <tbody>
                                @forelse ($data['plan'] as $row)
                                    <tr>
                                        <td><input type="text" name="category[]" class="ot-input" value="{{ $row['category'] }}"></td>
                                        <td><input type="number" min="0" name="reel_target[]" class="ot-input" value="{{ $row['reel_target'] }}"></td>
                                        <td><input type="number" min="0" name="carousel_target[]" class="ot-input" value="{{ $row['carousel_target'] }}"></td>
                                    </tr>
                                @empty
                                @endforelse
                                <tr>
                                    <td><input type="text" name="category[]" class="ot-input" placeholder="New category"></td>
                                    <td><input type="number" min="0" name="reel_target[]" class="ot-input" value="0"></td>
                                    <td><input type="number" min="0" name="carousel_target[]" class="ot-input" value="0"></td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="submit" class="btn ot-btn-primary">Save Targets</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
