@php
    $bnE6 = [
        ['Ethics',      'Integrity &amp; Islamic values'],
        ['Empathy',     'Compassion &amp; understanding'],
        ['Exploration', 'Innovation &amp; curiosity'],
        ['Endeavour',   'Purposeful effort &amp; resilience'],
        ['Excellence',  'High standards &amp; mastery'],
        ['Empowerment', 'Personalisation &amp; wellbeing'],
    ];
@endphp

<div class="bn-e6-orbit" role="list" aria-label="The E to the power of six core values">
    <div class="bn-e6-ring" aria-hidden="true"></div>

    <div class="bn-e6-core">
        <span class="bn-e6-core__mark">E<sup>6</sup></span>
        <span class="bn-e6-core__sub">to the power of six</span>
    </div>

    @foreach ($bnE6 as $i => $v)
        <div class="bn-e6-node" role="listitem" style="--i: {{ $i }};">
            <span class="bn-e6-node__e">E<sup>{{ $i + 1 }}</sup></span>
            <span class="bn-e6-node__name">{{ $v[0] }}</span>
            <span class="bn-e6-node__desc">{!! $v[1] !!}</span>
        </div>
    @endforeach
</div>
