@extends('employees.layout')

@section('title', 'Presenze')

@section('content')
<div class="employee-app">
    <header class="employee-header">
        <a class="employee-header__brand" href="{{ route('employee.attendance') }}" aria-label="Area dipendenti">
            <img src="/assets/images/new-logo-primary.png" alt="Il Paradiso della Frutta">
        </a>
        <div class="employee-header__account">
            <span>{{ $employee->full_name }}</span>
            <form method="POST" action="{{ route('employee.logout') }}">
                @csrf
                <button class="employee-logout" type="submit" aria-label="Esci dall'area dipendenti">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 17l5-5-5-5"></path>
                        <path d="M15 12H3"></path>
                        <path d="M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"></path>
                    </svg>
                    <span>Esci</span>
                </button>
            </form>
        </div>
    </header>

    <main class="employee-main">
        <section class="employee-welcome">
            <div>
                <p class="eyebrow">{{ now()->translatedFormat('l d F Y') }}</p>
                <h1>Buongiorno, {{ $employee->first_name }}.</h1>
                <p>Registra i dati della giornata. Puoi correggerli fino alla fine di oggi.</p>
            </div>
            <div class="employee-profile">
                @if ($employee->photo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($employee->photo_path) }}" alt="{{ $employee->full_name }}">
                @else
                    <span>{{ mb_strtoupper(mb_substr($employee->first_name, 0, 1).mb_substr($employee->last_name, 0, 1)) }}</span>
                @endif
                <div>
                    <strong>{{ $employee->full_name }}</strong>
                    <small>
                        {{ $employee->compensation_type->label() }} ·
                        € {{ number_format((float) $employee->compensation_amount, 2, ',', '.') }}
                    </small>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert--success" role="status">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert--error" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="employee-grid">
            <section class="attendance-card">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Presenza di oggi</p>
                        <h2>La tua giornata lavorativa</h2>
                    </div>
                    @if ($todayShift)
                        <span class="status-badge status-badge--{{ $todayShift->status }}">
                            {{ $todayShift->status === 'absent' ? 'Assente' : ($todayShift->is_open ? 'In corso' : 'Registrata') }}
                        </span>
                    @endif
                </div>

                <form method="POST" action="{{ route('employee.attendance.store') }}" class="attendance-form">
                    @csrf
                    @php($currentStatus = old('status', $todayShift?->status ?? 'present'))

                    <div class="status-choice">
                        <label>
                            <input type="radio" name="status" value="present" @checked($currentStatus === 'present')>
                            <span><strong>Presente</strong><small>Registra l’inizio e completa la fine quando termini</small></span>
                        </label>
                        <label>
                            <input type="radio" name="status" value="absent" @checked($currentStatus === 'absent')>
                            <span><strong>Assente</strong><small>Gli orari non sono necessari</small></span>
                        </label>
                    </div>

                    <div class="attendance-times" data-attendance-times>
                        <label class="field">
                            <span>Inizio lavoro</span>
                            <input type="time" name="started_at" value="{{ old('started_at', $todayShift?->started_at ? mb_substr($todayShift->started_at, 0, 5) : '') }}">
                        </label>
                        <label class="field">
                            <span>Fine lavoro <small>(facoltativa fino al termine)</small></span>
                            <input type="time" name="ended_at" value="{{ old('ended_at', $todayShift?->ended_at ? mb_substr($todayShift->ended_at, 0, 5) : '') }}">
                        </label>
                        <label class="field">
                            <span>Pausa</span>
                            <span class="input-suffix">
                                <input type="number" name="break_minutes" min="0" max="1439" value="{{ old('break_minutes', $todayShift?->break_minutes ?? 0) }}">
                                <small>minuti</small>
                            </span>
                        </label>
                    </div>

                    <label class="field">
                        <span>Note <small>(facoltative)</small></span>
                        <textarea name="notes" rows="4" placeholder="Aggiungi una comunicazione per Antonio">{{ old('notes', $todayShift?->notes) }}</textarea>
                    </label>

                    <button class="primary-button" type="submit">
                        {{ $todayShift ? 'Aggiorna la giornata' : 'Registra la giornata' }}
                    </button>
                </form>
            </section>

            <aside class="day-summary">
                <p class="eyebrow">Riepilogo odierno</p>
                <h2>{{ $todayShift ? ($todayShift->is_open ? 'Turno in corso' : $todayShift->worked_duration) : 'Non registrato' }}</h2>
                <dl>
                    <div><dt>Ore previste</dt><dd>{{ number_format($employee->expected_daily_minutes / 60, 2, ',', '.') }}</dd></div>
                    <div><dt>Stato</dt><dd>{{ $todayShift ? ($todayShift->status === 'absent' ? 'Assente' : ($todayShift->is_open ? 'In corso' : 'Presente')) : 'Da compilare' }}</dd></div>
                    <div>
                        <dt>Compenso giornata</dt>
                        <dd>€ {{ number_format((float) ($todayShift?->pay_amount ?? 0), 2, ',', '.') }}</dd>
                    </div>
                </dl>
                <p class="day-summary__note">Il riepilogo definitivo resta sempre verificabile e modificabile da Antonio nel gestionale.</p>
            </aside>
        </div>

        <section class="history-card" id="registrazioni">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Storico personale</p>
                    <h2>Le mie registrazioni</h2>
                </div>
                <span class="history-count">{{ $recentShifts->count() }} {{ $recentShifts->count() === 1 ? 'risultato' : 'risultati' }}</span>
            </div>

            <nav class="history-tabs" aria-label="Filtra le registrazioni">
                @foreach ([
                    'recent' => 'Recenti',
                    'today' => 'Oggi',
                    'yesterday' => 'Ieri',
                    'week' => 'Settimana',
                    'month' => 'Mese',
                ] as $periodValue => $periodLabel)
                    <a
                        href="{{ route('employee.attendance', ['period' => $periodValue]) }}#registrazioni"
                        @class(['is-active' => $period === $periodValue])
                    >
                        {{ $periodLabel }}
                    </a>
                @endforeach
            </nav>

            <form class="history-date-filter" method="GET" action="{{ route('employee.attendance') }}#registrazioni">
                <input type="hidden" name="period" value="custom">
                <label>
                    <span>Dal giorno</span>
                    <input type="date" name="from" value="{{ $from?->toDateString() }}" max="{{ today()->toDateString() }}">
                </label>
                <label>
                    <span>Al giorno</span>
                    <input type="date" name="to" value="{{ $to?->toDateString() }}" max="{{ today()->toDateString() }}">
                </label>
                <button type="submit">Applica date</button>
                @if ($period === 'custom')
                    <a href="{{ route('employee.attendance') }}#registrazioni">Azzera</a>
                @endif
            </form>

            <div class="history-list">
                @forelse ($recentShifts as $shift)
                    <details class="attendance-record" @if ($loop->first) open @endif>
                        <summary class="attendance-record__header">
                            <div>
                                <span>Registrazione</span>
                                <strong>{{ $shift->work_date->translatedFormat('l d F Y') }}</strong>
                            </div>
                            <div class="attendance-record__header-actions">
                                <span class="status-badge status-badge--{{ $shift->status }}">
                                    {{ $shift->status === 'absent' ? 'Assente' : ($shift->is_open ? 'In corso' : 'Presente') }}
                                </span>
                                <span class="attendance-record__chevron" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="m7 10 5 5 5-5"></path>
                                    </svg>
                                </span>
                            </div>
                        </summary>

                        <div class="attendance-record__content">
                            <div class="attendance-record__details">
                                <div class="attendance-record__metric">
                                    <span class="attendance-record__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <circle cx="12" cy="12" r="8"></circle>
                                            <path d="M12 7v5l3 2"></path>
                                        </svg>
                                    </span>
                                    <div>
                                        <small>Orario</small>
                                        <strong>
                                            {{ $shift->status === 'absent' ? 'Non lavorato' : mb_substr($shift->started_at, 0, 5).' – '.($shift->ended_at ? mb_substr($shift->ended_at, 0, 5) : 'In corso') }}
                                        </strong>
                                    </div>
                                </div>

                                <div class="attendance-record__metric">
                                    <span class="attendance-record__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M9 3h6M12 3v3"></path>
                                            <circle cx="12" cy="14" r="7"></circle>
                                            <path d="M12 10v4l2 2"></path>
                                        </svg>
                                    </span>
                                    <div>
                                        <small>Ore lavorate</small>
                                        <strong>{{ $shift->status === 'absent' ? '0h 00m' : ($shift->is_open ? 'Da calcolare' : $shift->worked_duration) }}</strong>
                                    </div>
                                </div>

                                <div class="attendance-record__metric">
                                    <span class="attendance-record__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M8 5v14M16 5v14"></path>
                                        </svg>
                                    </span>
                                    <div>
                                        <small>Pausa</small>
                                        <strong>{{ $shift->status === 'absent' ? '—' : $shift->break_minutes.' min' }}</strong>
                                    </div>
                                </div>
                            </div>

                            @if ($shift->notes)
                                <div class="attendance-record__note">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path d="M6 4h12a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H10l-4 3v-3a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"></path>
                                    </svg>
                                    <span>{{ $shift->notes }}</span>
                                </div>
                            @endif

                            <footer class="attendance-record__footer">
                                <span>Compenso della giornata</span>
                                <strong>€ {{ number_format((float) $shift->pay_amount, 2, ',', '.') }}</strong>
                            </footer>
                        </div>
                    </details>
                @empty
                    <p class="empty-state">Non ci sono ancora presenze registrate.</p>
                @endforelse
            </div>
        </section>
    </main>
</div>
@endsection
