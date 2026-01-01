@extends('layouts.admin')

@section('page-title')
    {{ __('My Code Reviews') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('My Code Reviews') }}</li>
@endsection

@push('css-page')
<style>
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 20px;
        color: #fff;
        margin-bottom: 20px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }
    .stat-card.pending { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); }
    .stat-card h3 { font-size: 2rem; font-weight: 700; margin-bottom: 0; }
    .stat-card p { opacity: 0.9; margin: 0; font-size: 0.85rem; }
    .stat-card i { font-size: 2.5rem; opacity: 0.3; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); }

    .review-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        margin-bottom: 15px;
        border-left: 4px solid #667eea;
        transition: transform 0.2s;
    }
    .review-card:hover { transform: translateY(-2px); }
    .review-card.critical { border-left-color: #ff4b2b; }
    .review-card.warning { border-left-color: #ffd200; }
    .review-card.clean { border-left-color: #38ef7d; }

    .review-header { padding: 15px 20px; border-bottom: 1px solid #eee; }
    .review-body { padding: 15px 20px; }

    .severity-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .severity-badge.critical { background: #ff4b2b; color: #fff; }
    .severity-badge.warning { background: #ffd200; color: #333; }
    .severity-badge.clean { background: #38ef7d; color: #fff; }

    .no-mapping-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 40px;
        color: #fff;
        text-align: center;
    }
    .no-mapping-card i { font-size: 4rem; opacity: 0.5; }
</style>
@endpush

@section('content')
@if(isset($noMapping) && $noMapping)
    <!-- No GitHub Mapping -->
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="no-mapping-card">
                <i class="ti ti-brand-github"></i>
                <h4 class="mt-3">{{ __('GitHub Account Not Linked') }}</h4>
                <p class="opacity-75">{{ __('Your GitHub account is not linked to your ERP profile. Please contact your administrator to set up the mapping.') }}</p>
            </div>
        </div>
    </div>
@else
    <!-- Stats -->
    <div class="row">
        <div class="col-md-6">
            <div class="stat-card">
                <i class="ti ti-code"></i>
                <h3>{{ $stats['total'] }}</h3>
                <p>{{ __('Total Reviews') }}</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card pending">
                <i class="ti ti-clock"></i>
                <h3>{{ $stats['pending_actions'] }}</h3>
                <p>{{ __('Pending Actions') }}</p>
            </div>
        </div>
    </div>

    <!-- Reviews List -->
    <div class="row">
        <div class="col-12">
            @forelse($reviews as $review)
                @php
                    $cardClass = 'clean';
                    if ($review->critical_count > 0) $cardClass = 'critical';
                    elseif ($review->warning_count > 0) $cardClass = 'warning';
                @endphp
                <div class="review-card {{ $cardClass }}">
                    <div class="review-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $review->repo_name }}</strong>
                            <span class="text-muted mx-2">/</span>
                            <span class="text-muted">{{ $review->branch }}</span>
                            <span class="severity-badge {{ $cardClass }} ms-2">
                                @if($review->critical_count > 0)
                                    {{ $review->critical_count }} {{ __('Critical') }}
                                @elseif($review->warning_count > 0)
                                    {{ $review->warning_count }} {{ __('Warnings') }}
                                @else
                                    {{ __('Clean') }}
                                @endif
                            </span>
                        </div>
                        <div class="text-muted">
                            {{ $review->created_at->format('M d, Y H:i') }}
                        </div>
                    </div>
                    <div class="review-body">
                        <div class="row">
                            <div class="col-md-9">
                                <p class="mb-2">
                                    <code>{{ substr($review->commit_sha, 0, 7) }}</code>
                                    <span class="ms-2">{{ Str::limit($review->commit_message, 100) }}</span>
                                </p>
                                <small class="text-muted">
                                    <i class="ti ti-file"></i> {{ $review->files_changed }} files
                                    <span class="text-success">+{{ $review->lines_added }}</span>
                                    <span class="text-danger">-{{ $review->lines_deleted }}</span>
                                </small>
                            </div>
                            <div class="col-md-3 text-end">
                                <a href="{{ route('code-reviews.show', $review->id) }}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-eye"></i> {{ __('View & Take Action') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="ti ti-mood-happy" style="font-size: 4rem; color: #38ef7d;"></i>
                    <p class="text-muted mt-3">{{ __('No code reviews yet. Keep pushing great code!') }}</p>
                </div>
            @endforelse

            <div class="mt-4">
                {{ $reviews->links() }}
            </div>
        </div>
    </div>
@endif
@endsection
