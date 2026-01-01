{{-- Floating Focus Timer Widget --}}
<style>
.focus-widget {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 9999;
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
}

.focus-widget-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10B981, #059669);
    border: none;
    color: #fff;
    font-size: 1.8rem;
    cursor: pointer;
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.focus-widget-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 12px 35px rgba(16, 185, 129, 0.5);
}

.focus-widget-btn.active {
    animation: widget-pulse 2s infinite;
}

@keyframes widget-pulse {
    0%, 100% { box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4); }
    50% { box-shadow: 0 8px 35px rgba(16, 185, 129, 0.7), 0 0 0 10px rgba(16, 185, 129, 0.1); }
}

.focus-widget-expanded {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 280px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.2);
    overflow: hidden;
    transform: scale(0) translateY(20px);
    transform-origin: bottom right;
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.focus-widget-expanded.show {
    transform: scale(1) translateY(0);
    opacity: 1;
}

.widget-header {
    background: linear-gradient(135deg, #065F46, #064E3B);
    padding: 20px;
    color: #fff;
    text-align: center;
}

.widget-tree {
    font-size: 3rem;
    margin-bottom: 10px;
    animation: tree-sway 3s ease-in-out infinite;
}

@keyframes tree-sway {
    0%, 100% { transform: rotate(-2deg); }
    50% { transform: rotate(2deg); }
}

.widget-time {
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: 2px;
    font-variant-numeric: tabular-nums;
}

.widget-label {
    font-size: 0.85rem;
    opacity: 0.8;
    margin-top: 5px;
}

.widget-progress {
    height: 4px;
    background: rgba(255,255,255,0.2);
}

.widget-progress-fill {
    height: 100%;
    background: #34D399;
    transition: width 0.5s ease;
}

.widget-body {
    padding: 15px;
}

.widget-task {
    font-size: 0.85rem;
    color: #6B7280;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.widget-task-name {
    color: #1F2937;
    font-weight: 500;
}

.widget-controls {
    display: flex;
    gap: 10px;
}

.widget-control-btn {
    flex: 1;
    padding: 10px;
    border-radius: 10px;
    border: none;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.widget-control-btn.pause {
    background: #FEF3C7;
    color: #D97706;
}

.widget-control-btn.pause:hover {
    background: #FDE68A;
}

.widget-control-btn.resume {
    background: #D1FAE5;
    color: #059669;
}

.widget-control-btn.resume:hover {
    background: #A7F3D0;
}

.widget-control-btn.abandon {
    background: #FEE2E2;
    color: #DC2626;
}

.widget-control-btn.abandon:hover {
    background: #FECACA;
}

.widget-start-section {
    padding: 20px;
    text-align: center;
}

.widget-start-btn {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    background: linear-gradient(135deg, #10B981, #059669);
    color: #fff;
    border: none;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.widget-start-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.widget-quick-start {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.widget-duration-chip {
    flex: 1;
    padding: 8px;
    border-radius: 8px;
    background: #F3F4F6;
    border: 2px solid transparent;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
}

.widget-duration-chip:hover {
    border-color: #10B981;
}

.widget-duration-chip.active {
    background: #D1FAE5;
    border-color: #10B981;
}
</style>

<div class="focus-widget" id="focusWidget">
    {{-- Main Button --}}
    <button class="focus-widget-btn" id="widgetToggle" title="Focus Forest">
        🌱
    </button>

    {{-- Expanded Panel --}}
    <div class="focus-widget-expanded" id="widgetPanel">
        {{-- Active Session View --}}
        <div id="widgetActiveView" style="display: none;">
            <div class="widget-header">
                <div class="widget-tree" id="widgetTree">🌱</div>
                <div class="widget-time" id="widgetTime">25:00</div>
                <div class="widget-label" id="widgetLabel">Stay focused...</div>
            </div>
            <div class="widget-progress">
                <div class="widget-progress-fill" id="widgetProgress" style="width: 0%;"></div>
            </div>
            <div class="widget-body">
                <div class="widget-task">
                    <span>📋</span>
                    <span class="widget-task-name" id="widgetTaskName">Free Focus</span>
                </div>
                <div class="widget-controls" id="widgetActiveControls">
                    <button class="widget-control-btn pause" id="widgetPauseBtn">
                        <i class="ti ti-player-pause"></i> Pause
                    </button>
                    <button class="widget-control-btn abandon" id="widgetAbandonBtn">
                        <i class="ti ti-x"></i> Give Up
                    </button>
                </div>
                <div class="widget-controls" id="widgetPausedControls" style="display: none;">
                    <button class="widget-control-btn resume" id="widgetResumeBtn">
                        <i class="ti ti-player-play"></i> Resume
                    </button>
                    <button class="widget-control-btn abandon" id="widgetAbandonBtn2">
                        <i class="ti ti-x"></i> Give Up
                    </button>
                </div>
            </div>
        </div>

        {{-- Idle View (No Active Session) --}}
        <div id="widgetIdleView">
            <div class="widget-header" style="padding: 25px 20px;">
                <div style="font-size: 2.5rem; margin-bottom: 10px;">🌳</div>
                <div style="font-weight: 600;">Focus Forest</div>
                <div style="font-size: 0.85rem; opacity: 0.8;">Grow trees, stay focused</div>
            </div>
            <div class="widget-start-section">
                <div class="widget-quick-start" id="widgetDurations">
                    <div class="widget-duration-chip" data-duration="15">15m</div>
                    <div class="widget-duration-chip active" data-duration="25">25m</div>
                    <div class="widget-duration-chip" data-duration="45">45m</div>
                    <div class="widget-duration-chip" data-duration="60">60m</div>
                </div>
                <button class="widget-start-btn" id="widgetStartBtn" style="margin-top: 15px;">
                    🌱 Start Focus Session
                </button>
                <a href="{{ route('focus.index') }}" style="display: block; margin-top: 12px; font-size: 0.85rem; color: #6B7280; text-decoration: none;">
                    Open Full Timer →
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const FocusWidget = {
        isOpen: false,
        sessionId: null,
        isRunning: false,
        isPaused: false,
        duration: 25,
        elapsed: 0,
        interval: null,
        tickInterval: null,

        elements: {
            widget: document.getElementById('focusWidget'),
            toggle: document.getElementById('widgetToggle'),
            panel: document.getElementById('widgetPanel'),
            activeView: document.getElementById('widgetActiveView'),
            idleView: document.getElementById('widgetIdleView'),
            tree: document.getElementById('widgetTree'),
            time: document.getElementById('widgetTime'),
            label: document.getElementById('widgetLabel'),
            progress: document.getElementById('widgetProgress'),
            taskName: document.getElementById('widgetTaskName'),
            activeControls: document.getElementById('widgetActiveControls'),
            pausedControls: document.getElementById('widgetPausedControls'),
        },

        treeStages: ['🌱', '🌿', '🌳'],

        init() {
            this.bindEvents();
            this.checkActiveSession();
        },

        bindEvents() {
            // Toggle panel
            this.elements.toggle.addEventListener('click', () => this.togglePanel());

            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!this.elements.widget.contains(e.target) && this.isOpen) {
                    this.closePanel();
                }
            });

            // Duration selection
            document.querySelectorAll('.widget-duration-chip').forEach(chip => {
                chip.addEventListener('click', () => {
                    document.querySelectorAll('.widget-duration-chip').forEach(c => c.classList.remove('active'));
                    chip.classList.add('active');
                    this.duration = parseInt(chip.dataset.duration);
                });
            });

            // Start button
            document.getElementById('widgetStartBtn').addEventListener('click', () => this.start());

            // Control buttons
            document.getElementById('widgetPauseBtn').addEventListener('click', () => this.pause());
            document.getElementById('widgetResumeBtn').addEventListener('click', () => this.resume());
            document.getElementById('widgetAbandonBtn').addEventListener('click', () => this.abandon());
            document.getElementById('widgetAbandonBtn2').addEventListener('click', () => this.abandon());
        },

        togglePanel() {
            this.isOpen = !this.isOpen;
            this.elements.panel.classList.toggle('show', this.isOpen);
        },

        closePanel() {
            this.isOpen = false;
            this.elements.panel.classList.remove('show');
        },

        async checkActiveSession() {
            try {
                const response = await fetch('{{ route("focus.status") }}');
                const data = await response.json();

                if (data.has_active) {
                    this.sessionId = data.session.id;
                    this.duration = data.session.planned_duration;
                    this.elapsed = data.elapsed_seconds;

                    // Update task name
                    const taskName = data.session.task?.name || data.session.project?.project_name || 'Free Focus';
                    this.elements.taskName.textContent = taskName;

                    if (data.session.status === 'active') {
                        this.startTimer(true);
                    } else if (data.session.status === 'paused') {
                        this.isPaused = true;
                        this.showPausedState();
                    }

                    // Show active view
                    this.elements.activeView.style.display = 'block';
                    this.elements.idleView.style.display = 'none';
                    this.elements.toggle.classList.add('active');
                    this.elements.toggle.textContent = this.treeStages[0];
                }
            } catch (error) {
                console.error('Widget check session error:', error);
            }
        },

        async start() {
            try {
                document.getElementById('widgetStartBtn').disabled = true;

                const response = await fetch('{{ route("focus.start") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        duration: this.duration,
                        project_id: null,
                        task_id: null,
                        tree_type: 'oak'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.sessionId = data.session.id;
                    this.elapsed = 0;
                    this.elements.taskName.textContent = 'Free Focus';
                    this.startTimer();

                    // Show active view
                    this.elements.activeView.style.display = 'block';
                    this.elements.idleView.style.display = 'none';
                    this.elements.toggle.classList.add('active');
                } else {
                    alert(data.error || 'Failed to start session');
                }
            } catch (error) {
                console.error('Widget start error:', error);
            } finally {
                document.getElementById('widgetStartBtn').disabled = false;
            }
        },

        startTimer(resumed = false) {
            this.isRunning = true;
            this.isPaused = false;

            this.elements.label.textContent = 'Stay focused...';
            this.elements.activeControls.style.display = 'flex';
            this.elements.pausedControls.style.display = 'none';

            this.interval = setInterval(() => {
                this.elapsed++;
                this.updateDisplay();
                this.updateProgress();
                this.updateTreeStage();

                if (this.elapsed >= this.duration * 60) {
                    this.complete();
                }
            }, 1000);

            this.tickInterval = setInterval(() => this.tick(), 30000);
        },

        updateDisplay() {
            const remaining = Math.max(0, this.duration * 60 - this.elapsed);
            const mins = Math.floor(remaining / 60);
            const secs = remaining % 60;
            this.elements.time.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        },

        updateProgress() {
            const progress = (this.elapsed / (this.duration * 60)) * 100;
            this.elements.progress.style.width = `${progress}%`;
        },

        updateTreeStage() {
            const progress = (this.elapsed / (this.duration * 60)) * 100;
            let stage = 0;
            if (progress >= 66) stage = 2;
            else if (progress >= 33) stage = 1;

            this.elements.tree.textContent = this.treeStages[stage];
            this.elements.toggle.textContent = this.treeStages[stage];
        },

        async tick() {
            if (!this.sessionId || !this.isRunning) return;
            try {
                await fetch(`{{ url('focus') }}/${this.sessionId}/tick`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
            } catch (error) {
                console.error('Widget tick error:', error);
            }
        },

        async pause() {
            clearInterval(this.interval);
            clearInterval(this.tickInterval);

            try {
                await fetch(`{{ url('focus') }}/${this.sessionId}/pause`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                this.isPaused = true;
                this.isRunning = false;
                this.showPausedState();
            } catch (error) {
                console.error('Widget pause error:', error);
            }
        },

        showPausedState() {
            this.elements.activeControls.style.display = 'none';
            this.elements.pausedControls.style.display = 'flex';
            this.elements.label.textContent = 'Paused';

            // Show active view
            this.elements.activeView.style.display = 'block';
            this.elements.idleView.style.display = 'none';

            this.updateDisplay();
            this.updateProgress();
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
                console.error('Widget resume error:', error);
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
                    // Show completion
                    this.elements.tree.textContent = '🌳';
                    this.elements.toggle.textContent = '🌳';
                    this.elements.label.textContent = `+${data.points_earned} points!`;
                    this.elements.time.textContent = 'Done!';

                    // Play sound
                    this.playSound();

                    // Reset after delay
                    setTimeout(() => this.reset(), 3000);
                }
            } catch (error) {
                console.error('Widget complete error:', error);
            }
        },

        async abandon() {
            if (!confirm('Give up? Your tree will wither.')) return;

            clearInterval(this.interval);
            clearInterval(this.tickInterval);

            try {
                await fetch(`{{ url('focus') }}/${this.sessionId}/abandon`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                this.elements.tree.textContent = '🥀';
                this.elements.toggle.textContent = '🥀';
                this.elements.label.textContent = 'Tree withered...';

                setTimeout(() => this.reset(), 2000);
            } catch (error) {
                console.error('Widget abandon error:', error);
            }
        },

        reset() {
            this.sessionId = null;
            this.isRunning = false;
            this.isPaused = false;
            this.elapsed = 0;

            this.elements.activeView.style.display = 'none';
            this.elements.idleView.style.display = 'block';
            this.elements.toggle.classList.remove('active');
            this.elements.toggle.textContent = '🌱';
            this.elements.tree.textContent = '🌱';
            this.elements.time.textContent = `${this.duration}:00`;
            this.elements.progress.style.width = '0%';
        },

        playSound() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.frequency.setValueAtTime(523.25, audioContext.currentTime);
                oscillator.frequency.setValueAtTime(659.25, audioContext.currentTime + 0.1);
                oscillator.frequency.setValueAtTime(783.99, audioContext.currentTime + 0.2);

                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.5);
            } catch (e) {
                // Audio not supported
            }
        }
    };

    FocusWidget.init();
});
</script>
