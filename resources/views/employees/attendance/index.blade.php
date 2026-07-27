@extends('employees.layout')

@section('title', 'Presenze')

@section('content')
<div class="employee-app">
    <header class="employee-header">
        <a href="{{ route('employee.attendance') }}">
            <img src="/assets/images/new-logo-primary.png" alt="Il Paradiso della Frutta">
        </a>
        <div class="employee-header__account">
            <span>{{ $employee->full_name }}</span>
            <form method="POST" action="{{ route('employee.logout') }}">
                @csrf
                <button type="submit">Esci</button>
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
                            {{ $todayShift->status === 'absent' ? 'Assente' : 'Registrata' }}
                        </span>
                    @endif
                </div>

                <form method="POST" action="{{ route('employee.attendance.store') }}" class="attendance-form">
                    @csrf
                    @php($currentStatus = old('status', $todayShift?->status ?? 'present'))

                    <div class="status-choice">
                        <label>
                            <input type="radio" name="status" value="present" @checked($currentStatus === 'present')>
                            <span><strong>Presente</strong><small>Inserisci inizio, fine e pausa</small></span>
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
                            <span>Fine lavoro</span>
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
                <h2>{{ $todayShift ? $todayShift->worked_duration : 'Non registrato' }}</h2>
                <dl>
                    <div><dt>Ore previste</dt><dd>{{ number_format($employee->expected_daily_minutes / 60, 2, ',', '.') }}</dd></div>
                    <div><dt>Stato</dt><dd>{{ $todayShift ? ($todayShift->status === 'absent' ? 'Assente' : 'Presente') : 'Da compilare' }}</dd></div>
                    <div>
                        <dt>Compenso giornata</dt>
                        <dd>€ {{ number_format((float) ($todayShift?->pay_amount ?? 0), 2, ',', '.') }}</dd>
                    </div>
                </dl>
                <p class="day-summary__note">Il riepilogo definitivo resta sempre verificabile e modificabile da Antonio nel gestionale.</p>
            </aside>
        </div>

        <section class="history-card">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Ultime registrazioni</p>
                    <h2>Storico personale</h2>
                </div>
            </div>

            <div class="history-list">
                @forelse ($recentShifts as $shift)
                    <article>
                        <div class="history-list__date">
                            <strong>{{ $shift->work_date->translatedFormat('d M') }}</strong>
                            <small>{{ $shift->work_date->translatedFormat('l') }}</small>
                        </div>
                        <span class="status-badge status-badge--{{ $shift->status }}">
                            {{ $shift->status === 'absent' ? 'Assente' : 'Presente' }}
                        </span>
                        <div>
                            <strong>{{ $shift->status === 'absent' ? '—' : mb_substr($shift->started_at, 0, 5).' – '.mb_substr($shift->ended_at, 0, 5) }}</strong>
                            <small>{{ $shift->status === 'absent' ? ($shift->notes ?: 'Nessuna nota') : $shift->worked_duration.' lavorate' }}</small>
                        </div>
                        <strong>€ {{ number_format((float) $shift->pay_amount, 2, ',', '.') }}</strong>
                    </article>
                @empty
                    <p class="empty-state">Non ci sono ancora presenze registrate.</p>
                @endforelse
            </div>
        </section>
    </main>
</div>
@endsection
