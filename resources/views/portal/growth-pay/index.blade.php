@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-8">
                    <h4 class="bradecrumb-title mb-1">Team Portal</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Growth Pay</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-12 p-0">
            <form action="{{ route('portal-growth-pay.index') }}" method="get">
                <div class="card ot-card mb-24 d-flex flex-row align-items-center gap-3" style="padding:1rem 1.25rem">
                    <input type="month" name="month" class="ot-input" value="{{ $data['month'] }}">
                    <button class="btn ot-btn-primary" type="submit">{{ ___('common.Search') }}</button>
                </div>
            </form>
        </div>

        <p class="text-secondary">You enter the base and bonus yourself — there's no automatic calculation. Marking paid records it as a real expense; you still transfer the money to the employee yourself, same as before.</p>

        <div class="table-content table-basic">
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered class-table">
                        <thead class="thead">
                            <tr><th>Employee</th><th>Base</th><th>Bonus</th><th>Total</th><th>Note</th><th>Status</th><th class="action">{{ ___('common.action') }}</th></tr>
                        </thead>
                        <tbody class="tbody">
                            @foreach ($data['rows'] as $row)
                                @php($pay = $row['pay'])
                                <tr>
                                    <td>{{ trim($row['staff']->first_name . ' ' . $row['staff']->last_name) }}</td>
                                    <td colspan="4">
                                        @if ($pay && $pay->status === 'paid')
                                            {{ Setting('currency_symbol') }} {{ number_format($pay->base_amount, 2) }} +
                                            {{ Setting('currency_symbol') }} {{ number_format($pay->bonus_amount, 2) }} —
                                            {{ $pay->note }}
                                        @else
                                            <form action="{{ route('portal-growth-pay.save') }}" method="post" class="d-flex gap-2 flex-wrap align-items-center">
                                                @csrf
                                                <input type="hidden" name="staff_id" value="{{ $row['staff']->id }}">
                                                <input type="hidden" name="month" value="{{ $data['month'] }}">
                                                <input type="number" step="0.01" min="0" name="base_amount" class="ot-input" style="max-width:110px" placeholder="Base" value="{{ $pay->base_amount ?? '' }}">
                                                <input type="number" step="0.01" min="0" name="bonus_amount" class="ot-input" style="max-width:110px" placeholder="Bonus" value="{{ $pay->bonus_amount ?? '' }}">
                                                <input type="text" name="note" class="ot-input" style="max-width:200px" placeholder="Note (optional)" value="{{ $pay->note ?? '' }}">
                                                <button type="submit" class="btn btn-sm ot-btn-primary">Save</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="table-content table-basic mt-24">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">This month's totals</h4></div>
                <div class="card-body">
                    <table class="table table-bordered class-table">
                        <thead class="thead"><tr><th>Employee</th><th>Total</th><th>Status</th><th class="action">{{ ___('common.action') }}</th></tr></thead>
                        <tbody class="tbody">
                            @foreach ($data['rows'] as $row)
                                @continue (!$row['pay'])
                                @php($pay = $row['pay'])
                                <tr>
                                    <td>{{ trim($row['staff']->first_name . ' ' . $row['staff']->last_name) }}</td>
                                    <td>{{ Setting('currency_symbol') }} {{ number_format($pay->total, 2) }}</td>
                                    <td>
                                        @if ($pay->status === 'paid')
                                            <span class="badge-basic-success-text">Paid</span>
                                        @else
                                            <span class="badge-basic-warning-text">Unpaid</span>
                                        @endif
                                    </td>
                                    <td class="action">
                                        @if ($pay->status !== 'paid')
                                            <form action="{{ route('portal-growth-pay.mark-paid', $pay->id) }}" method="post" onsubmit="return confirm('Mark this paid? This records a real expense and can\'t be undone.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm ot-btn-success">Mark Paid</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
