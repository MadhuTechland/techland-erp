@extends('layouts.admin')

@section('page-title')
    {{ __('Focus Forest') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Focus Forest') }}</li>
@endsection

@push('css-page')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --focus-primary: #10B981;
    --focus-secondary: #059669;
    --focus-accent: #34D399;
    --focus-dark: #064E3B;
    --focus-light: #D1FAE5;
    --focus-orange: #F59E0B;
    --focus-red: #EF4444;
    --focus-purple: #8B5CF6;
}

.focus-container {
    font-family: 'Poppins', sans-serif;
}

/* Main Timer Card */
.timer-card {
    background: linear-gradient(135deg, #065F46 0%, #064E3B 50%, #022C22 100%);
    border-radius: 24px;
    padding: 40px;
    color: #fff;
    position: relative;
    overflow: hidden;
    min-height: 500px;
}

.timer-card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(52, 211, 153, 0.1) 0%, transparent 50%);
    animation: pulse-bg 4s ease-in-out infinite;
    pointer-events: none; /* Allow clicks to pass through */
    z-index: 0;
}

@keyframes pulse-bg {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

/* Tree Container */
.tree-container {
    position: relative;
    width: 200px;
    height: 200px;
    margin: 0 auto 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.tree-circle {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.2);
}

.tree-progress {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 4px solid transparent;
    border-top-color: var(--focus-accent);
    transform: rotate(-90deg);
    transition: all 0.3s ease;
}

.tree-icon {
    font-size: 80px;
    z-index: 2;
    animation: tree-sway 3s ease-in-out infinite;
    filter: drop-shadow(0 10px 20px rgba(0,0,0,0.3));
}

@keyframes tree-sway {
    0%, 100% { transform: rotate(-2deg); }
    50% { transform: rotate(2deg); }
}

.tree-icon.growing {
    animation: tree-grow 1s ease-out forwards, tree-sway 3s ease-in-out infinite;
}

@keyframes tree-grow {
    0% { transform: scale(0.5); opacity: 0.5; }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); opacity: 1; }
}

.tree-icon.withered {
    filter: grayscale(1) drop-shadow(0 10px 20px rgba(0,0,0,0.3));
    animation: wither 0.5s ease-out forwards;
}

@keyframes wither {
    0% { transform: scale(1); }
    50% { transform: scale(0.9) rotate(10deg); }
    100% { transform: scale(0.8) rotate(-5deg); opacity: 0.5; }
}

/* Timer Display */
.timer-display {
    text-align: center;
    margin-bottom: 30px;
}

.timer-time {
    font-size: 4rem;
    font-weight: 700;
    letter-spacing: 4px;
    text-shadow: 0 4px 20px rgba(0,0,0,0.3);
    font-variant-numeric: tabular-nums;
}

.timer-label {
    font-size: 0.9rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 2px;
}

/* Timer Controls */
.timer-controls {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}

.timer-btn {
    padding: 14px 32px;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.timer-btn-primary {
    background: linear-gradient(135deg, var(--focus-accent), var(--focus-primary));
    color: #fff;
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
}

.timer-btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(16, 185, 129, 0.5);
}

.timer-btn-danger {
    background: linear-gradient(135deg, #F87171, var(--focus-red));
    color: #fff;
    box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
}

.timer-btn-danger:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(239, 68, 68, 0.4);
}

.timer-btn-secondary {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border: 2px solid rgba(255,255,255,0.3);
}

.timer-btn-secondary:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-3px);
}

.timer-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}

/* Session Setup */
.session-setup {
    position: relative;
    z-index: 2;
}

/* Timer Controls */
.timer-controls {
    position: relative;
    z-index: 2;
}

/* Timer Display */
.timer-display {
    position: relative;
    z-index: 2;
}

.setup-row {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    justify-content: center;
}

.setup-select {
    background: rgba(255,255,255,0.1);
    border: 2px solid rgba(255,255,255,0.2);
    border-radius: 12px;
    padding: 12px 20px;
    color: #fff;
    font-size: 0.95rem;
    min-width: 200px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.setup-select:focus {
    outline: none;
    border-color: var(--focus-accent);
    background: rgba(255,255,255,0.15);
}

.setup-select option {
    background: #064E3B;
    color: #fff;
}

/* Duration Chips */
.duration-chips {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.duration-chip {
    padding: 10px 24px;
    border-radius: 25px;
    background: rgba(255,255,255,0.1);
    border: 2px solid rgba(255,255,255,0.2);
    color: #fff;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.duration-chip:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-2px);
}

.duration-chip.active {
    background: var(--focus-accent);
    border-color: var(--focus-accent);
    box-shadow: 0 4px 15px rgba(52, 211, 153, 0.4);
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.stat-icon {
    font-size: 2rem;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1F2937;
}

.stat-label {
    font-size: 0.8rem;
    color: #6B7280;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Progress Bar */
.daily-progress {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.progress-title {
    font-weight: 600;
    color: #1F2937;
}

.progress-count {
    font-size: 0.9rem;
    color: #6B7280;
}

.progress-bar-container {
    height: 12px;
    background: #E5E7EB;
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--focus-primary), var(--focus-accent));
    border-radius: 10px;
    transition: width 0.5s ease;
}

.progress-sessions {
    display: flex;
    gap: 8px;
    margin-top: 15px;
}

.session-dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #E5E7EB;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.session-dot.completed {
    background: var(--focus-primary);
    color: #fff;
}

.session-dot.current {
    background: var(--focus-orange);
    color: #fff;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
    50% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
}

/* Level Progress */
.level-card {
    background: linear-gradient(135deg, var(--focus-purple) 0%, #7C3AED 100%);
    border-radius: 16px;
    padding: 20px;
    color: #fff;
    margin-bottom: 20px;
}

.level-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.level-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.level-icon {
    font-size: 2rem;
}

.level-name {
    font-weight: 600;
}

.level-number {
    font-size: 0.85rem;
    opacity: 0.8;
}

.level-points {
    text-align: right;
}

.points-value {
    font-size: 1.5rem;
    font-weight: 700;
}

.points-label {
    font-size: 0.75rem;
    opacity: 0.8;
}

.level-progress {
    height: 8px;
    background: rgba(255,255,255,0.2);
    border-radius: 5px;
    overflow: hidden;
}

.level-progress-fill {
    height: 100%;
    background: #fff;
    border-radius: 5px;
    transition: width 0.5s ease;
}

/* Weekly Chart */
.weekly-chart {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.chart-title {
    font-weight: 600;
    color: #1F2937;
    margin-bottom: 20px;
}

.chart-bars {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    height: 120px;
    gap: 10px;
}

.chart-bar-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
}

.chart-bar {
    width: 100%;
    max-width: 40px;
    background: linear-gradient(180deg, var(--focus-accent), var(--focus-primary));
    border-radius: 8px 8px 0 0;
    transition: height 0.5s ease;
    min-height: 4px;
}

.chart-bar.today {
    background: linear-gradient(180deg, var(--focus-orange), #D97706);
}

.chart-day {
    margin-top: 10px;
    font-size: 0.75rem;
    color: #6B7280;
    font-weight: 500;
}

.chart-value {
    font-size: 0.7rem;
    color: #9CA3AF;
    margin-top: 5px;
}

/* Trees Showcase */
.trees-showcase {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.trees-title {
    font-weight: 600;
    color: #1F2937;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.trees-grid {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.tree-option {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background: #F3F4F6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.tree-option:hover {
    transform: scale(1.1);
    background: var(--focus-light);
}

.tree-option.selected {
    background: var(--focus-light);
    box-shadow: 0 0 0 3px var(--focus-primary);
}

.tree-option.locked {
    opacity: 0.4;
    cursor: not-allowed;
}

.tree-option.locked::after {
    content: '🔒';
    position: absolute;
    font-size: 0.8rem;
    bottom: -5px;
    right: -5px;
}

/* Achievements */
.achievements-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.achievements-title {
    font-weight: 600;
    color: #1F2937;
    margin-bottom: 15px;
}

.achievements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
    gap: 10px;
}

.achievement-badge {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #FEF3C7, #FDE68A);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.3s ease;
}

.achievement-badge:hover {
    transform: scale(1.15);
}

.achievement-badge.locked {
    background: #E5E7EB;
    opacity: 0.5;
}

/* Recent Sessions */
.recent-sessions {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.session-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 0;
    border-bottom: 1px solid #F3F4F6;
}

.session-item:last-child {
    border-bottom: none;
}

.session-tree {
    font-size: 1.8rem;
}

.session-info {
    flex: 1;
}

.session-task {
    font-weight: 500;
    color: #1F2937;
    font-size: 0.9rem;
}

.session-meta {
    font-size: 0.8rem;
    color: #6B7280;
}

.session-duration {
    font-weight: 600;
    color: var(--focus-primary);
}

/* Streak Badge */
.streak-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    background: linear-gradient(135deg, var(--focus-orange), #D97706);
    padding: 8px 16px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
}

/* Notification Toast */
.focus-toast {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #fff;
    border-radius: 16px;
    padding: 20px 25px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 15px;
    transform: translateX(150%);
    transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    z-index: 1000;
}

.focus-toast.show {
    transform: translateX(0);
}

.toast-icon {
    font-size: 2.5rem;
}

.toast-content h4 {
    font-weight: 600;
    color: #1F2937;
    margin: 0 0 5px 0;
}

.toast-content p {
    color: #6B7280;
    margin: 0;
    font-size: 0.9rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .timer-card {
        padding: 25px;
        min-height: auto;
    }

    .timer-time {
        font-size: 3rem;
    }

    .tree-container {
        width: 150px;
        height: 150px;
    }

    .tree-icon {
        font-size: 60px;
    }

    .setup-select {
        min-width: 100%;
    }

    .timer-btn {
        padding: 12px 24px;
        font-size: 0.9rem;
    }
}
</style>
@endpush

@section('content')
<div class="focus-container">
    <div class="row">
        <!-- Main Timer Column -->
        <div class="col-lg-8">
            <!-- Timer Card -->
            <div class="timer-card" id="timerCard">
                @if($streak > 0)
                <div class="streak-badge">
                    <span>🔥</span>
                    <span>{{ $streak }} day streak</span>
                </div>
                @endif

                <!-- Tree Animation -->
                <div class="tree-container">
                    <div class="tree-circle"></div>
                    <svg class="tree-progress-ring" width="200" height="200" style="position: absolute; transform: rotate(-90deg);">
                        <circle id="progressCircle" cx="100" cy="100" r="96" fill="none" stroke="rgba(52, 211, 153, 0.8)" stroke-width="6" stroke-dasharray="603" stroke-dashoffset="603" stroke-linecap="round"/>
                    </svg>
                    <div class="tree-icon" id="treeIcon">🌱</div>
                </div>

                <!-- Timer Display -->
                <div class="timer-display">
                    <div class="timer-time" id="timerDisplay">{{ sprintf('%02d', $settings->focus_duration) }}:00</div>
                    <div class="timer-label" id="timerLabel">{{ __('Ready to focus?') }}</div>
                </div>

                <!-- Session Setup (shown when not active) -->
                <div class="session-setup" id="sessionSetup">
                    <!-- Duration Selection -->
                    <div class="duration-chips">
                        <div class="duration-chip {{ $settings->focus_duration == 15 ? 'active' : '' }}" data-duration="15">15 min</div>
                        <div class="duration-chip {{ $settings->focus_duration == 25 ? 'active' : '' }}" data-duration="25">25 min</div>
                        <div class="duration-chip {{ $settings->focus_duration == 45 ? 'active' : '' }}" data-duration="45">45 min</div>
                        <div class="duration-chip {{ $settings->focus_duration == 60 ? 'active' : '' }}" data-duration="60">60 min</div>
                    </div>

                    <!-- Task Selection (Optional) -->
                    <div class="setup-row">
                        <select class="setup-select" id="projectSelect">
                            <option value="">{{ __('Link to project (optional)') }}</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                            @endforeach
                        </select>
                        <select class="setup-select" id="taskSelect" disabled>
                            <option value="">{{ __('Select task (optional)') }}</option>
                        </select>
                    </div>

                    <!-- Start Button -->
                    <div class="timer-controls">
                        <button class="timer-btn timer-btn-primary" id="startBtn">
                            <i class="ti ti-player-play"></i>
                            {{ __('Start Growing') }}
                        </button>
                    </div>
                </div>

                <!-- Active Session Controls (hidden initially) -->
                <div class="timer-controls" id="activeControls" style="display: none;">
                    <button class="timer-btn timer-btn-secondary" id="pauseBtn">
                        <i class="ti ti-player-pause"></i>
                        {{ __('Pause') }}
                    </button>
                    <button class="timer-btn timer-btn-danger" id="abandonBtn">
                        <i class="ti ti-x"></i>
                        {{ __('Give Up') }}
                    </button>
                </div>

                <!-- Paused Controls (hidden initially) -->
                <div class="timer-controls" id="pausedControls" style="display: none;">
                    <button class="timer-btn timer-btn-primary" id="resumeBtn">
                        <i class="ti ti-player-play"></i>
                        {{ __('Resume') }}
                    </button>
                    <button class="timer-btn timer-btn-danger" id="abandonBtn2">
                        <i class="ti ti-x"></i>
                        {{ __('Give Up') }}
                    </button>
                </div>
            </div>

            <!-- Weekly Progress Chart -->
            <div class="weekly-chart">
                <div class="chart-title">{{ __('This Week') }}</div>
                <div class="chart-bars">
                    @php $maxMinutes = max(array_column($weeklyStats['daily'], 'minutes')) ?: 1; @endphp
                    @foreach($weeklyStats['daily'] as $index => $day)
                        @php
                            $height = ($day['minutes'] / $maxMinutes) * 100;
                            $isToday = $day['date'] === now()->toDateString();
                        @endphp
                        <div class="chart-bar-wrapper">
                            <div class="chart-bar {{ $isToday ? 'today' : '' }}" style="height: {{ max($height, 5) }}%;"></div>
                            <div class="chart-day">{{ $day['day'] }}</div>
                            <div class="chart-value">{{ $day['minutes'] }}m</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Sessions -->
            @if($recentSessions->count() > 0)
            <div class="recent-sessions">
                <h5 style="font-weight: 600; margin-bottom: 15px;">{{ __('Recent Focus Sessions') }}</h5>
                @foreach($recentSessions->take(5) as $session)
                    <div class="session-item">
                        <div class="session-tree">{{ $session->getTreeInfo()['icon'] }}</div>
                        <div class="session-info">
                            <div class="session-task">
                                {{ $session->task?->name ?? $session->project?->project_name ?? __('Free Focus') }}
                            </div>
                            <div class="session-meta">
                                {{ $session->ended_at->format('M d, H:i') }}
                                @if($session->task)
                                    &bull; {{ $session->task->issue_key }}
                                @endif
                            </div>
                        </div>
                        <div class="session-duration">{{ $session->actual_duration }} min</div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Stats Column -->
        <div class="col-lg-4">
            <!-- Today's Progress -->
            <div class="daily-progress">
                <div class="progress-header">
                    <span class="progress-title">{{ __("Today's Goal") }}</span>
                    <span class="progress-count">{{ $todayStats->completed_sessions }} / {{ $settings->daily_goal }}</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: {{ min(100, ($todayStats->completed_sessions / $settings->daily_goal) * 100) }}%;"></div>
                </div>
                <div class="progress-sessions">
                    @for($i = 0; $i < $settings->daily_goal; $i++)
                        <div class="session-dot {{ $i < $todayStats->completed_sessions ? 'completed' : ($i == $todayStats->completed_sessions ? 'current' : '') }}">
                            @if($i < $todayStats->completed_sessions)
                                🌳
                            @elseif($i == $todayStats->completed_sessions)
                                🌱
                            @endif
                        </div>
                    @endfor
                </div>
            </div>

            <!-- Level Card -->
            <div class="level-card">
                <div class="level-header">
                    <div class="level-info">
                        <span class="level-icon">{{ $levelInfo['icon'] }}</span>
                        <div>
                            <div class="level-name">{{ $levelInfo['name'] }}</div>
                            <div class="level-number">Level {{ $levelInfo['level'] }}</div>
                        </div>
                    </div>
                    <div class="level-points">
                        <div class="points-value">{{ number_format($levelInfo['points']) }}</div>
                        <div class="points-label">points</div>
                    </div>
                </div>
                <div class="level-progress">
                    <div class="level-progress-fill" style="width: {{ $levelInfo['progress'] }}%;"></div>
                </div>
                @if($levelInfo['next_level_points'])
                    <div style="font-size: 0.75rem; opacity: 0.8; margin-top: 8px; text-align: right;">
                        {{ number_format($levelInfo['next_level_points'] - $levelInfo['points']) }} points to next level
                    </div>
                @endif
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🌳</div>
                    <div class="stat-value">{{ $settings->total_trees }}</div>
                    <div class="stat-label">Trees</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-value">{{ floor($weeklyStats['total_minutes'] / 60) }}h</div>
                    <div class="stat-label">This Week</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-value">{{ $streak }}</div>
                    <div class="stat-label">Streak</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-value">{{ $settings->longest_streak }}</div>
                    <div class="stat-label">Best Streak</div>
                </div>
            </div>

            <!-- Tree Selection -->
            <div class="trees-showcase">
                <div class="trees-title">
                    <span>{{ __('Choose Your Tree') }}</span>
                    <small style="color: #6B7280;">{{ count($unlockedTrees) }}/{{ count(\App\Models\FocusSession::$treeTypes) }}</small>
                </div>
                <div class="trees-grid">
                    @foreach(\App\Models\FocusSession::$treeTypes as $key => $tree)
                        <div class="tree-option {{ isset($unlockedTrees[$key]) ? '' : 'locked' }} {{ $settings->preferred_tree === $key ? 'selected' : '' }}"
                             data-tree="{{ $key }}"
                             data-level="{{ $tree['level'] }}"
                             title="{{ $tree['name'] }} (Level {{ $tree['level'] }})">
                            {{ $tree['icon'] }}
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Achievements -->
            <div class="achievements-card">
                <div class="achievements-title">
                    {{ __('Achievements') }}
                    <span style="font-weight: normal; color: #6B7280;">({{ $achievements->count() }}/{{ count(\App\Models\FocusAchievement::$achievements) }})</span>
                </div>
                <div class="achievements-grid">
                    @foreach(\App\Models\FocusAchievement::$achievements as $key => $achievement)
                        @php $earned = $achievements->firstWhere('achievement_key', $key); @endphp
                        <div class="achievement-badge {{ $earned ? '' : 'locked' }}"
                             title="{{ $achievement['name'] }}: {{ $achievement['description'] }}">
                            {{ $achievement['icon'] }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Achievement Toast -->
<div class="focus-toast" id="achievementToast">
    <div class="toast-icon" id="toastIcon">🏆</div>
    <div class="toast-content">
        <h4 id="toastTitle">Achievement Unlocked!</h4>
        <p id="toastMessage">You earned a new badge</p>
    </div>
</div>

<!-- Completion Modal -->
<div class="modal fade" id="completionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-body text-center p-5" style="background: linear-gradient(135deg, #065F46 0%, #064E3B 100%); color: #fff;">
                <div style="font-size: 5rem; margin-bottom: 20px;" id="completionTree">🌳</div>
                <h3 style="font-weight: 700; margin-bottom: 10px;">{{ __('Tree Grown!') }}</h3>
                <p style="opacity: 0.9; margin-bottom: 20px;" id="completionMessage">Great focus session!</p>
                <div style="font-size: 1.5rem; margin-bottom: 20px;">
                    +<span id="completionPoints">0</span> points
                </div>
                <button type="button" class="timer-btn timer-btn-primary" data-bs-dismiss="modal">
                    {{ __('Continue') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script>
// Focus Timer Application
const FocusTimer = {
    // State
    sessionId: null,
    isRunning: false,
    isPaused: false,
    duration: {{ $settings->focus_duration }},
    elapsed: 0,
    interval: null,
    tickInterval: null,
    selectedTree: '{{ $settings->preferred_tree }}',

    // DOM Elements (initialized in init)
    elements: {},

    // Tree stages based on progress
    treeStages: ['🌱', '🌿', '🌳'],

    init() {
        // Initialize DOM elements after DOM is ready
        this.elements = {
            timerDisplay: document.getElementById('timerDisplay'),
            timerLabel: document.getElementById('timerLabel'),
            treeIcon: document.getElementById('treeIcon'),
            progressCircle: document.getElementById('progressCircle'),
            sessionSetup: document.getElementById('sessionSetup'),
            activeControls: document.getElementById('activeControls'),
            pausedControls: document.getElementById('pausedControls'),
            startBtn: document.getElementById('startBtn'),
            pauseBtn: document.getElementById('pauseBtn'),
            resumeBtn: document.getElementById('resumeBtn'),
            abandonBtn: document.getElementById('abandonBtn'),
            abandonBtn2: document.getElementById('abandonBtn2'),
            projectSelect: document.getElementById('projectSelect'),
            taskSelect: document.getElementById('taskSelect'),
        };

        this.bindEvents();
        this.checkActiveSession();
    },

    bindEvents() {
        // Duration chips
        document.querySelectorAll('.duration-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('.duration-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
                this.duration = parseInt(chip.dataset.duration);
                this.updateDisplay(this.duration * 60);
            });
        });

        // Tree selection
        document.querySelectorAll('.tree-option:not(.locked)').forEach(tree => {
            tree.addEventListener('click', () => {
                document.querySelectorAll('.tree-option').forEach(t => t.classList.remove('selected'));
                tree.classList.add('selected');
                this.selectedTree = tree.dataset.tree;
                this.saveSettings({ preferred_tree: this.selectedTree });
            });
        });

        // Project selection
        this.elements.projectSelect.addEventListener('change', (e) => {
            const projectId = e.target.value;
            if (projectId) {
                this.loadProjectTasks(projectId);
            } else {
                this.elements.taskSelect.innerHTML = '<option value="">{{ __("Select task (optional)") }}</option>';
                this.elements.taskSelect.disabled = true;
            }
        });

        // Start button
        this.elements.startBtn.addEventListener('click', () => this.start());

        // Pause button
        this.elements.pauseBtn.addEventListener('click', () => this.pause());

        // Resume button
        this.elements.resumeBtn.addEventListener('click', () => this.resume());

        // Abandon buttons
        this.elements.abandonBtn.addEventListener('click', () => this.abandon());
        this.elements.abandonBtn2.addEventListener('click', () => this.abandon());
    },

    async checkActiveSession() {
        try {
            const response = await fetch('{{ route("focus.status") }}');
            const data = await response.json();

            if (data.has_active) {
                this.sessionId = data.session.id;
                this.duration = data.session.planned_duration;
                this.elapsed = data.elapsed_seconds;
                this.selectedTree = data.session.tree_type;

                // Immediately update display with correct remaining time
                this.updateDisplay(this.duration * 60 - this.elapsed);
                this.updateProgress();
                this.updateTreeStage();

                if (data.session.status === 'active') {
                    this.startTimer(true);
                } else if (data.session.status === 'paused') {
                    this.isPaused = true;
                    this.showPausedState();
                }
            }
        } catch (error) {
            console.error('Error checking session:', error);
        }
    },

    async loadProjectTasks(projectId) {
        try {
            const response = await fetch(`{{ url('focus/project') }}/${projectId}/tasks`);
            const tasks = await response.json();

            this.elements.taskSelect.innerHTML = '<option value="">{{ __("Select task (optional)") }}</option>';
            tasks.forEach(task => {
                const option = document.createElement('option');
                option.value = task.id;
                option.textContent = `${task.issue_key || ''} ${task.name}`;
                this.elements.taskSelect.appendChild(option);
            });
            this.elements.taskSelect.disabled = false;
        } catch (error) {
            console.error('Error loading tasks:', error);
        }
    },

    async start() {
        try {
            this.elements.startBtn.disabled = true;

            const response = await fetch('{{ route("focus.start") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    duration: this.duration,
                    project_id: this.elements.projectSelect.value || null,
                    task_id: this.elements.taskSelect.value || null,
                    tree_type: this.selectedTree
                })
            });

            const data = await response.json();

            if (data.success) {
                this.sessionId = data.session.id;
                this.elapsed = 0;
                this.startTimer();
            } else {
                alert(data.error || 'Failed to start session');
                this.elements.startBtn.disabled = false;
            }
        } catch (error) {
            console.error('Error starting session:', error);
            this.elements.startBtn.disabled = false;
        }
    },

    startTimer(resumed = false) {
        this.isRunning = true;
        this.isPaused = false;

        // Update UI
        this.elements.sessionSetup.style.display = 'none';
        this.elements.activeControls.style.display = 'flex';
        this.elements.pausedControls.style.display = 'none';
        this.elements.timerLabel.textContent = '{{ __("Stay focused...") }}';
        this.elements.treeIcon.classList.add('growing');

        // Immediately update display with current state
        this.updateDisplay(this.duration * 60 - this.elapsed);
        this.updateProgress();
        this.updateTreeStage();

        // Start interval
        this.interval = setInterval(() => {
            this.elapsed++;
            this.updateDisplay(this.duration * 60 - this.elapsed);
            this.updateProgress();
            this.updateTreeStage();

            // Check completion
            if (this.elapsed >= this.duration * 60) {
                this.complete();
            }
        }, 1000);

        // Tick server every 30 seconds
        this.tickInterval = setInterval(() => this.tick(), 30000);
    },

    updateDisplay(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        this.elements.timerDisplay.textContent =
            `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    },

    updateProgress() {
        const progress = (this.elapsed / (this.duration * 60)) * 100;
        const circumference = 2 * Math.PI * 96;
        const offset = circumference - (progress / 100) * circumference;
        this.elements.progressCircle.style.strokeDashoffset = offset;
    },

    updateTreeStage() {
        const progress = (this.elapsed / (this.duration * 60)) * 100;
        let stage = 0;
        if (progress >= 66) stage = 2;
        else if (progress >= 33) stage = 1;

        // Use selected tree icon at final stage
        if (stage === 2) {
            const trees = @json(\App\Models\FocusSession::$treeTypes);
            this.elements.treeIcon.textContent = trees[this.selectedTree]?.icon || '🌳';
        } else {
            this.elements.treeIcon.textContent = this.treeStages[stage];
        }
    },

    async tick() {
        if (!this.sessionId || !this.isRunning) return;

        try {
            const response = await fetch(`{{ url('focus') }}/${this.sessionId}/tick`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await response.json();

            if (data.should_complete) {
                this.complete();
            }
        } catch (error) {
            console.error('Tick error:', error);
        }
    },

    async pause() {
        if (!this.sessionId) {
            console.error('No session ID');
            return;
        }

        clearInterval(this.interval);
        clearInterval(this.tickInterval);

        try {
            const response = await fetch(`{{ url('focus') }}/${this.sessionId}/pause`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.isPaused = true;
                this.isRunning = false;
                this.showPausedState();
            } else {
                console.error('Pause failed:', data.error || 'Unknown error');
                alert('Failed to pause: ' + (data.error || 'Unknown error'));
                // Restart the timer if pause failed
                this.startTimer(true);
            }
        } catch (error) {
            console.error('Pause error:', error);
            alert('Failed to pause session');
            this.startTimer(true);
        }
    },

    showPausedState() {
        this.elements.sessionSetup.style.display = 'none';
        this.elements.activeControls.style.display = 'none';
        this.elements.pausedControls.style.display = 'flex';
        this.elements.timerLabel.textContent = '{{ __("Paused") }}';

        // Update display with current remaining time
        this.updateDisplay(this.duration * 60 - this.elapsed);
        this.updateProgress();
        this.updateTreeStage();
    },

    async resume() {
        try {
            await fetch(`{{ url('focus') }}/${this.sessionId}/resume`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            this.startTimer(true);
        } catch (error) {
            console.error('Resume error:', error);
        }
    },

    async complete() {
        clearInterval(this.interval);
        clearInterval(this.tickInterval);

        try {
            const response = await fetch(`{{ url('focus') }}/${this.sessionId}/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            if (data.success) {
                // Show completion modal
                const trees = @json(\App\Models\FocusSession::$treeTypes);
                document.getElementById('completionTree').textContent = trees[this.selectedTree]?.icon || '🌳';
                document.getElementById('completionPoints').textContent = data.points_earned;
                new bootstrap.Modal(document.getElementById('completionModal')).show();

                // Show achievement toasts
                if (data.new_achievements && data.new_achievements.length > 0) {
                    data.new_achievements.forEach((achievement, index) => {
                        setTimeout(() => this.showAchievementToast(achievement), (index + 1) * 2000);
                    });
                }

                // Play sound
                this.playCompletionSound();

                // Reset
                this.reset();

                // Reload page after modal closes to update stats
                document.getElementById('completionModal').addEventListener('hidden.bs.modal', () => {
                    location.reload();
                }, { once: true });
            }
        } catch (error) {
            console.error('Complete error:', error);
        }
    },

    async abandon() {
        if (!this.sessionId) {
            console.error('No session ID');
            return;
        }

        if (!confirm('{{ __("Are you sure? Your tree will wither.") }}')) return;

        clearInterval(this.interval);
        clearInterval(this.tickInterval);

        try {
            const response = await fetch(`{{ url('focus') }}/${this.sessionId}/abandon`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Show withered tree
                this.elements.treeIcon.classList.remove('growing');
                this.elements.treeIcon.classList.add('withered');
                this.elements.timerLabel.textContent = '{{ __("Tree withered...") }}';

                setTimeout(() => {
                    this.reset();
                }, 2000);
            } else {
                console.error('Abandon failed:', data.error || 'Unknown error');
                alert('Failed to abandon: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Abandon error:', error);
            alert('Failed to abandon session');
        }
    },

    reset() {
        this.sessionId = null;
        this.isRunning = false;
        this.isPaused = false;
        this.elapsed = 0;

        this.elements.sessionSetup.style.display = 'block';
        this.elements.activeControls.style.display = 'none';
        this.elements.pausedControls.style.display = 'none';
        this.elements.startBtn.disabled = false;
        this.elements.timerLabel.textContent = '{{ __("Ready to focus?") }}';
        this.elements.treeIcon.textContent = '🌱';
        this.elements.treeIcon.classList.remove('growing', 'withered');
        this.elements.progressCircle.style.strokeDashoffset = 603;
        this.updateDisplay(this.duration * 60);
    },

    showAchievementToast(achievement) {
        const toast = document.getElementById('achievementToast');
        document.getElementById('toastIcon').textContent = achievement.icon;
        document.getElementById('toastTitle').textContent = achievement.achievement_name;
        document.getElementById('toastMessage').textContent = `+${achievement.points_awarded} points`;

        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 4000);
    },

    playCompletionSound() {
        // Create a simple completion sound
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.setValueAtTime(523.25, audioContext.currentTime); // C5
        oscillator.frequency.setValueAtTime(659.25, audioContext.currentTime + 0.1); // E5
        oscillator.frequency.setValueAtTime(783.99, audioContext.currentTime + 0.2); // G5

        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
    },

    async saveSettings(settings) {
        try {
            await fetch('{{ route("focus.settings.update") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(settings)
            });
        } catch (error) {
            console.error('Settings save error:', error);
        }
    }
};

// Initialize
document.addEventListener('DOMContentLoaded', () => FocusTimer.init());
</script>
@endpush
