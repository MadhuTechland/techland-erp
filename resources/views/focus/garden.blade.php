@extends('layouts.admin')

@section('page-title')
    {{ __('My Forest') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('focus.index') }}">{{ __('Focus Forest') }}</a></li>
    <li class="breadcrumb-item">{{ __('My Forest') }}</li>
@endsection

@push('css-page')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
.garden-container {
    font-family: 'Poppins', sans-serif;
}

.garden-header {
    background: linear-gradient(135deg, #065F46 0%, #064E3B 50%, #022C22 100%);
    border-radius: 24px;
    padding: 40px;
    color: #fff;
    margin-bottom: 30px;
    text-align: center;
}

.garden-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.garden-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
}

.garden-stats {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin-top: 30px;
}

.garden-stat {
    text-align: center;
}

.garden-stat-value {
    font-size: 2.5rem;
    font-weight: 700;
}

.garden-stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

/* Forest Grid */
.forest-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 15px;
    padding: 30px;
    background: linear-gradient(180deg, #D1FAE5 0%, #A7F3D0 50%, #6EE7B7 100%);
    border-radius: 24px;
    min-height: 400px;
}

.forest-tree {
    width: 80px;
    height: 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.forest-tree:hover {
    transform: scale(1.1) translateY(-5px);
}

.forest-tree-icon {
    font-size: 3rem;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
}

.forest-tree-date {
    font-size: 0.65rem;
    color: #065F46;
    margin-top: 5px;
    font-weight: 500;
}

/* Tree Detail Modal */
.tree-detail-modal .modal-content {
    border-radius: 20px;
    overflow: hidden;
}

.tree-detail-header {
    background: linear-gradient(135deg, #065F46, #064E3B);
    color: #fff;
    padding: 30px;
    text-align: center;
}

.tree-detail-icon {
    font-size: 5rem;
    margin-bottom: 15px;
}

.tree-detail-body {
    padding: 25px;
}

.tree-detail-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #E5E7EB;
}

.tree-detail-item:last-child {
    border-bottom: none;
}

.tree-detail-label {
    color: #6B7280;
}

.tree-detail-value {
    font-weight: 600;
    color: #1F2937;
}

/* Empty State */
.empty-forest {
    text-align: center;
    padding: 60px;
    color: #6B7280;
}

.empty-forest-icon {
    font-size: 4rem;
    margin-bottom: 20px;
}

/* Filter Bar */
.filter-bar {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.filter-label {
    font-weight: 500;
    color: #374151;
}

.filter-select {
    padding: 8px 16px;
    border: 2px solid #E5E7EB;
    border-radius: 10px;
    font-size: 0.9rem;
    cursor: pointer;
}

.filter-select:focus {
    outline: none;
    border-color: #10B981;
}
</style>
@endpush

@section('content')
<div class="garden-container">
    <!-- Header -->
    <div class="garden-header">
        <div class="garden-title">{{ __('My Forest') }}</div>
        <div class="garden-subtitle">{{ __('Every tree represents a moment of deep focus') }}</div>

        <div class="garden-stats">
            <div class="garden-stat">
                <div class="garden-stat-value">{{ $totalTrees }}</div>
                <div class="garden-stat-label">{{ __('Trees Planted') }}</div>
            </div>
            <div class="garden-stat">
                <div class="garden-stat-value">{{ $levelInfo['icon'] }}</div>
                <div class="garden-stat-label">{{ $levelInfo['name'] }}</div>
            </div>
            <div class="garden-stat">
                <div class="garden-stat-value">{{ number_format($levelInfo['points']) }}</div>
                <div class="garden-stat-label">{{ __('Total Points') }}</div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <span class="filter-label">{{ __('Filter:') }}</span>
        <select class="filter-select" id="treeTypeFilter">
            <option value="">{{ __('All Trees') }}</option>
            @foreach(\App\Models\FocusSession::$treeTypes as $key => $tree)
                <option value="{{ $key }}">{{ $tree['icon'] }} {{ $tree['name'] }}</option>
            @endforeach
        </select>
        <select class="filter-select" id="monthFilter">
            <option value="">{{ __('All Time') }}</option>
            <option value="this_month">{{ __('This Month') }}</option>
            <option value="last_month">{{ __('Last Month') }}</option>
            <option value="this_year">{{ __('This Year') }}</option>
        </select>
    </div>

    <!-- Forest Grid -->
    @if($trees->count() > 0)
        <div class="forest-grid" id="forestGrid">
            @foreach($trees as $tree)
                <div class="forest-tree"
                     data-tree-type="{{ $tree['tree_type'] }}"
                     data-date="{{ $tree['date'] }}"
                     onclick="showTreeDetail({{ json_encode($tree) }})">
                    <div class="forest-tree-icon">{{ $tree['tree_info']['icon'] }}</div>
                    <div class="forest-tree-date">{{ \Carbon\Carbon::parse($tree['date'])->format('M d') }}</div>
                </div>
            @endforeach
        </div>
    @else
        <div class="forest-grid">
            <div class="empty-forest" style="grid-column: 1 / -1;">
                <div class="empty-forest-icon">🌱</div>
                <h4>{{ __('Your forest is empty') }}</h4>
                <p>{{ __('Complete focus sessions to grow trees!') }}</p>
                <a href="{{ route('focus.index') }}" class="btn btn-success mt-3">
                    {{ __('Start Focusing') }}
                </a>
            </div>
        </div>
    @endif
</div>

<!-- Tree Detail Modal -->
<div class="modal fade tree-detail-modal" id="treeDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="tree-detail-header">
                <div class="tree-detail-icon" id="modalTreeIcon">🌳</div>
                <h4 id="modalTreeName">Oak Tree</h4>
            </div>
            <div class="tree-detail-body">
                <div class="tree-detail-item">
                    <span class="tree-detail-label">{{ __('Date') }}</span>
                    <span class="tree-detail-value" id="modalDate">-</span>
                </div>
                <div class="tree-detail-item">
                    <span class="tree-detail-label">{{ __('Duration') }}</span>
                    <span class="tree-detail-value" id="modalDuration">-</span>
                </div>
                <div class="tree-detail-item">
                    <span class="tree-detail-label">{{ __('Project') }}</span>
                    <span class="tree-detail-value" id="modalProject">-</span>
                </div>
                <div class="tree-detail-item">
                    <span class="tree-detail-label">{{ __('Task') }}</span>
                    <span class="tree-detail-value" id="modalTask">-</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script>
function showTreeDetail(tree) {
    document.getElementById('modalTreeIcon').textContent = tree.tree_info.icon;
    document.getElementById('modalTreeName').textContent = tree.tree_info.name;
    document.getElementById('modalDate').textContent = tree.date;
    document.getElementById('modalDuration').textContent = tree.duration + ' minutes';
    document.getElementById('modalProject').textContent = tree.project || 'Free Focus';
    document.getElementById('modalTask').textContent = tree.task || '-';

    new bootstrap.Modal(document.getElementById('treeDetailModal')).show();
}

// Filters
document.getElementById('treeTypeFilter').addEventListener('change', applyFilters);
document.getElementById('monthFilter').addEventListener('change', applyFilters);

function applyFilters() {
    const treeType = document.getElementById('treeTypeFilter').value;
    const month = document.getElementById('monthFilter').value;

    document.querySelectorAll('.forest-tree').forEach(tree => {
        let show = true;

        if (treeType && tree.dataset.treeType !== treeType) {
            show = false;
        }

        // Add month filtering logic here if needed

        tree.style.display = show ? 'flex' : 'none';
    });
}
</script>
@endpush
