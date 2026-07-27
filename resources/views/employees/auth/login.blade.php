@extends('employees.layout')

@section('title', 'Accesso dipendenti')

@section('content')
<main class="employee-auth">
    <section class="employee-auth__visual">
        <div class="employee-auth__overlay"></div>
        <img class="employee-auth__brand" src="/assets/images/new-logo-white.png" alt="Il Paradiso della Frutta">
        <div class="employee-auth__copy">
            <p class="eyebrow">Area riservata al personale</p>
            <h1>La tua giornata,<br>registrata bene.</h1>
            <p>Accedi per comunicare la presenza, gli orari di lavoro oppure un’assenza.</p>
        </div>
    </section>

    <section class="employee-auth__form-panel">
        <form class="employee-login-card" method="POST" action="{{ route('employee.login.store') }}">
            @csrf
            <img class="employee-login-card__logo" src="/assets/images/new-logo-primary.png" alt="Il Paradiso della Frutta">
            <div>
                <p class="eyebrow">Bentornato</p>
                <h2>Accesso dipendenti</h2>
                <p class="muted">Inserisci le credenziali fornite da Antonio.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert--error" role="alert">{{ $errors->first() }}</div>
            @endif

            <label class="field">
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            </label>

            <label class="field">
                <span>Password</span>
                <span class="password-field">
                    <input id="employee-password" type="password" name="password" autocomplete="current-password" required>
                    <button type="button" data-password-toggle="employee-password" aria-label="Mostra o nascondi password">Mostra</button>
                </span>
            </label>

            <label class="check-field">
                <input type="checkbox" name="remember" value="1">
                <span>Ricordami su questo dispositivo</span>
            </label>

            <button class="primary-button" type="submit">Accedi alla tua area</button>
            <p class="employee-login-card__help">Se non ricordi le credenziali, contatta l’amministratore.</p>
        </form>
    </section>
</main>
@endsection
