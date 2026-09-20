{{--
    The Knowledge Tree as an actual tree — trunk, branches and canopy drawn
    in SVG that genuinely grow stage by stage, rather than a row of leaf
    emojis. Five stages, one per 10 days the dashboard has been opened:
    Seed → Sprout → Sapling → Young Tree → Full Bloom.

    Greens are mixed from --bn-advanced so the tree still follows the theme,
    but the trunk and soil stay natural browns — a tree tinted pink by the
    Berry theme would stop reading as a tree.

    Usage: @include('backend.partials.knowledge-tree', ['tree' => $lh['knowledge_tree']])
--}}
@php
    $stage = (int) ($tree['stage_index'] ?? 0);

    // Trunk top and width per stage. Stage 0 has no trunk at all — a seed
    // hasn't broken the surface yet.
    $trunkTop   = [124, 96, 78, 62, 54][$stage];
    $trunkWidth = [0, 4, 6, 8, 10][$stage];

    // Canopy clusters as [cx, cy, r, tone] — tone picks one of three greens
    // so the foliage reads as having depth instead of one flat blob.
    $canopy = [
        0 => [],
        1 => [[54, 90, 7, 'mid'], [67, 86, 8, 'light']],
        2 => [[60, 68, 15, 'mid'], [46, 77, 11, 'dark'], [74, 76, 11, 'light']],
        3 => [[60, 50, 19, 'mid'], [40, 62, 15, 'dark'], [80, 61, 15, 'light'], [50, 37, 12, 'light'], [71, 38, 12, 'mid']],
        4 => [[60, 44, 22, 'mid'], [36, 58, 17, 'dark'], [84, 57, 17, 'light'], [47, 29, 14, 'light'], [74, 30, 14, 'mid'], [60, 20, 12, 'light']],
    ][$stage];

    $tones = ['mid' => 'var(--bn-tree-leaf)', 'light' => 'var(--bn-tree-leaf-light)', 'dark' => 'var(--bn-tree-leaf-dark)'];
@endphp

<svg class="bn-tree-svg" viewBox="0 0 120 140" role="img" aria-label="{{ $tree['label'] ?? '' }}">
    {{-- soil --}}
    <ellipse cx="60" cy="126" rx="33" ry="7" fill="var(--bn-tree-soil)"></ellipse>
    <ellipse cx="60" cy="124" rx="33" ry="6" fill="var(--bn-tree-soil-top)"></ellipse>

    @if ($stage === 0)
        {{-- Seed: still underground, with the first shoot just breaking through --}}
        <ellipse cx="60" cy="119" rx="5.5" ry="7" fill="var(--bn-tree-trunk-dark)"></ellipse>
        <path d="M60 113 q1 -8 6 -12" stroke="var(--bn-tree-leaf)" stroke-width="2.5" fill="none" stroke-linecap="round"></path>
        <ellipse cx="68" cy="99" rx="5" ry="3" fill="var(--bn-tree-leaf-light)" transform="rotate(-28 68 99)"></ellipse>
    @else
        {{-- Trunk, tapered from base to crown --}}
        <path d="M{{ 60 - $trunkWidth / 2 }} 124
                 L{{ 60 - $trunkWidth / 3.2 }} {{ $trunkTop }}
                 L{{ 60 + $trunkWidth / 3.2 }} {{ $trunkTop }}
                 L{{ 60 + $trunkWidth / 2 }} 124 Z"
              fill="var(--bn-tree-trunk)"></path>
        <path d="M{{ 60 - $trunkWidth / 2 }} 124
                 L{{ 60 - $trunkWidth / 3.2 }} {{ $trunkTop }}
                 L{{ 60 - $trunkWidth / 12 }} {{ $trunkTop }}
                 L{{ 60 - $trunkWidth / 6 }} 124 Z"
              fill="var(--bn-tree-trunk-dark)"></path>

        @if ($stage >= 3)
            {{-- Branches only once there's a trunk thick enough to carry them --}}
            <path d="M60 {{ $trunkTop + 22 }} Q50 {{ $trunkTop + 16 }} 43 {{ $trunkTop + 6 }}"
                  stroke="var(--bn-tree-trunk)" stroke-width="3.5" fill="none" stroke-linecap="round"></path>
            <path d="M60 {{ $trunkTop + 30 }} Q71 {{ $trunkTop + 24 }} 78 {{ $trunkTop + 14 }}"
                  stroke="var(--bn-tree-trunk)" stroke-width="3.5" fill="none" stroke-linecap="round"></path>
        @endif

        @foreach ($canopy as $c)
            <circle cx="{{ $c[0] }}" cy="{{ $c[1] }}" r="{{ $c[2] }}" fill="{{ $tones[$c[3]] }}"></circle>
        @endforeach

        @if ($stage >= 4)
            {{-- Blossoms, only at full bloom --}}
            @foreach ([[48, 34], [70, 24], [84, 52], [38, 56], [62, 16], [55, 50], [76, 44]] as $b)
                <circle cx="{{ $b[0] }}" cy="{{ $b[1] }}" r="2.6" fill="var(--bn-tree-blossom)"></circle>
            @endforeach
        @endif
    @endif
</svg>
