@extends('layouts.admin')
@section('page-title')
    {{__('Employee Screenshots')}}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item"><a href="{{route('time.tracker')}}">{{__('Tracker')}}</a></li>
    <li class="breadcrumb-item">{{__('Employee Screenshots')}}</li>
@endsection

@push('css-page')
    <link rel="stylesheet" href="{{url('css/swiper.min.css')}}">
    <style>
        .screenshot-filters {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .employee-section {
            background: #fff;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .employee-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
        }

        .employee-header:hover {
            background: linear-gradient(135deg, #5a6fd6 0%, #6a4190 100%);
        }

        .employee-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
            font-weight: 600;
            overflow: hidden;
        }

        .employee-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .employee-info {
            flex: 1;
        }

        .employee-info h5 {
            margin: 0;
            color: #fff;
            font-weight: 600;
        }

        .employee-info small {
            color: rgba(255,255,255,0.8);
        }

        .screenshot-count {
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 20px;
            color: #fff;
            font-weight: 600;
        }

        .toggle-icon {
            color: #fff;
            font-size: 20px;
            transition: transform 0.3s;
        }

        .employee-header.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }

        .screenshots-container {
            padding: 20px;
            background: #f8fafc;
        }

        .screenshots-container.collapsed {
            display: none;
        }

        .screenshots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .screenshot-card {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .screenshot-card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .screenshot-image {
            position: relative;
            width: 100%;
            height: 180px;
            overflow: hidden;
            background: #e2e8f0;
        }

        .screenshot-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .screenshot-image img:hover {
            transform: scale(1.05);
        }

        .screenshot-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .screenshot-card:hover .screenshot-overlay {
            opacity: 1;
        }

        .screenshot-overlay .btn {
            background: #fff;
            color: #1e293b;
        }

        .screenshot-meta {
            padding: 12px 14px;
        }

        .screenshot-time {
            font-size: 13px;
            color: #1e293b;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .screenshot-task {
            font-size: 11px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .screenshot-task i {
            color: #6366f1;
        }

        /* Modal Styles */
        .screenshot-modal .modal-content {
            background: #1e293b;
            border: none;
        }

        .screenshot-modal .modal-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 16px 20px;
        }

        .screenshot-modal .modal-title {
            color: #fff;
        }

        .screenshot-modal .btn-close {
            filter: invert(1);
        }

        .screenshot-modal .modal-body {
            padding: 0;
            text-align: center;
        }

        .screenshot-modal .modal-body img {
            max-width: 100%;
            max-height: 80vh;
        }

        .screenshot-modal .modal-footer {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 12px 20px;
        }

        .screenshot-modal .screenshot-details {
            color: #94a3b8;
            font-size: 13px;
        }

        /* Empty State */
        .empty-screenshots {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-screenshots i {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-screenshots h5 {
            color: #64748b;
            margin-bottom: 8px;
        }

        /* Timeline badge */
        .time-badge {
            display: inline-block;
            background: #e0e7ff;
            color: #4338ca;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        /* Date navigation */
        .date-nav {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .date-nav .btn {
            padding: 6px 12px;
        }
    </style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Filters -->
        <div class="screenshot-filters">
            <form method="GET" action="{{ route('employee.screenshots') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('Date') }}</label>
                    <div class="date-nav">
                        <a href="{{ route('employee.screenshots', ['date' => \Carbon\Carbon::parse($selectedDate)->subDay()->format('Y-m-d'), 'user_id' => $selectedUserId]) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="ti ti-chevron-left"></i>
                        </a>
                        <input type="date" name="date" class="form-control" value="{{ $selectedDate }}" onchange="this.form.submit()">
                        <a href="{{ route('employee.screenshots', ['date' => \Carbon\Carbon::parse($selectedDate)->addDay()->format('Y-m-d'), 'user_id' => $selectedUserId]) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="ti ti-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Employee') }}</label>
                    <select name="user_id" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('All Employees') }}</option>
                        @foreach($usersWithPhotos as $user)
                            <option value="{{ $user->id }}" {{ $selectedUserId == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Quick Dates') }}</label>
                    <select class="form-select" onchange="window.location.href='{{ route('employee.screenshots') }}?date=' + this.value + '&user_id={{ $selectedUserId }}'">
                        <option value="">{{ __('Select Date') }}</option>
                        @foreach($availableDates as $date)
                            <option value="{{ $date }}" {{ $selectedDate == $date ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($date)->format('D, M d, Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('employee.screenshots') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-refresh"></i> {{ __('Reset') }}
                    </a>
                    <a href="{{ route('time.tracker') }}" class="btn btn-outline-primary">
                        <i class="ti ti-clock"></i> {{ __('Time Tracker') }}
                    </a>
                </div>
            </form>
        </div>

        <!-- Date Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="ti ti-calendar text-primary"></i>
                {{ \Carbon\Carbon::parse($selectedDate)->format('l, F d, Y') }}
            </h4>
            <span class="badge bg-primary">
                {{ $photosByUser->flatten()->count() }} {{ __('screenshots') }}
            </span>
        </div>

        <!-- Employee Sections -->
        @forelse($photosByUser as $userId => $photos)
            @php
                $user = $photos->first()->user ?? null;
                $userName = $user ? $user->name : 'Unknown User';
                $userEmail = $user ? $user->email : '';
                $userAvatar = $user && $user->avatar ? asset(Storage::url('uploads/avatar/' . $user->avatar)) : null;
                $initials = strtoupper(substr($userName, 0, 2));
            @endphp
            <div class="employee-section">
                <div class="employee-header" onclick="toggleEmployee({{ $userId }})">
                    <div class="employee-avatar">
                        @if($userAvatar)
                            <img src="{{ $userAvatar }}" alt="{{ $userName }}">
                        @else
                            {{ $initials }}
                        @endif
                    </div>
                    <div class="employee-info">
                        <h5>{{ $userName }}</h5>
                        <small>{{ $userEmail }}</small>
                    </div>
                    <div class="screenshot-count">
                        <i class="ti ti-photo"></i> {{ $photos->count() }} {{ __('screenshots') }}
                    </div>
                    <i class="ti ti-chevron-down toggle-icon"></i>
                </div>
                <div class="screenshots-container" id="screenshots-{{ $userId }}">
                    <div class="screenshots-grid">
                        @foreach($photos as $photo)
                            <div class="screenshot-card">
                                <div class="screenshot-image">
                                    <img src="{{ asset(Storage::url($photo->img_path)) }}"
                                         alt="Screenshot"
                                         onclick="viewScreenshot('{{ asset(Storage::url($photo->img_path)) }}', '{{ $photo->time ? $photo->time->format('H:i:s') : '' }}', '{{ $userName }}', '{{ $photo->tracker ? $photo->tracker->name : '' }}')"
                                         onerror="this.src='{{ asset('assets/images/placeholder.png') }}'">
                                    <div class="screenshot-overlay">
                                        <button class="btn btn-sm" onclick="viewScreenshot('{{ asset(Storage::url($photo->img_path)) }}', '{{ $photo->time ? $photo->time->format('H:i:s') : '' }}', '{{ $userName }}', '{{ $photo->tracker ? $photo->tracker->name : '' }}')">
                                            <i class="ti ti-zoom-in"></i> {{ __('View') }}
                                        </button>
                                    </div>
                                </div>
                                <div class="screenshot-meta">
                                    <div class="screenshot-time">
                                        <i class="ti ti-clock"></i>
                                        {{ $photo->time ? $photo->time->format('h:i:s A') : 'N/A' }}
                                    </div>
                                    @if($photo->tracker)
                                        <div class="screenshot-task">
                                            <i class="ti ti-subtask"></i>
                                            {{ Str::limit($photo->tracker->name ?? 'No task', 35) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="employee-section">
                <div class="empty-screenshots">
                    <i class="ti ti-photo-off"></i>
                    <h5>{{ __('No Screenshots Found') }}</h5>
                    <p>{{ __('No screenshots were captured on this date.') }}</p>
                    @if($availableDates->count() > 0)
                        <p class="mt-3">
                            <strong>{{ __('Recent dates with screenshots:') }}</strong><br>
                            @foreach($availableDates->take(5) as $date)
                                <a href="{{ route('employee.screenshots', ['date' => $date]) }}" class="btn btn-sm btn-outline-primary mt-2">
                                    {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                                </a>
                            @endforeach
                        </p>
                    @endif
                </div>
            </div>
        @endforelse
    </div>
</div>

<!-- Screenshot View Modal -->
<div class="modal fade screenshot-modal" id="screenshotModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-photo"></i> <span id="modal-user-name"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <img src="" id="modal-screenshot-image" alt="Screenshot">
            </div>
            <div class="modal-footer">
                <div class="screenshot-details flex-grow-1 text-start">
                    <span id="modal-time"></span>
                    <span class="mx-2">|</span>
                    <span id="modal-task"></span>
                </div>
                <a href="" id="modal-download" class="btn btn-primary btn-sm" download>
                    <i class="ti ti-download"></i> {{ __('Download') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script>
    function toggleEmployee(userId) {
        var container = document.getElementById('screenshots-' + userId);
        var header = container.previousElementSibling;

        if (container.classList.contains('collapsed')) {
            container.classList.remove('collapsed');
            header.classList.remove('collapsed');
        } else {
            container.classList.add('collapsed');
            header.classList.add('collapsed');
        }
    }

    function viewScreenshot(imgUrl, time, userName, task) {
        $('#modal-screenshot-image').attr('src', imgUrl);
        $('#modal-user-name').text(userName);
        $('#modal-time').html('<i class="ti ti-clock"></i> ' + time);
        $('#modal-task').html('<i class="ti ti-subtask"></i> ' + (task || 'No task'));
        $('#modal-download').attr('href', imgUrl);
        $('#screenshotModal').modal('show');
    }

    // Keyboard navigation for modal
    $(document).keydown(function(e) {
        if ($('#screenshotModal').hasClass('show')) {
            if (e.keyCode === 27) { // Escape
                $('#screenshotModal').modal('hide');
            }
        }
    });
</script>
@endpush
