@extends('layouts.base')

@section('title', 'Entrar al panel')
@section('noindex', '1')

@section('body')
    <main class="auth">
        <div class="auth-card">
            <p class="auth-logo">{{ mb_strtolower(config('tienda.nombre')) }}</p>
            <div class="card stack-lg">
                <h1 style="font-size: 20px">Entrar al panel</h1>

                @include('partials.flash')

                <form method="post" action="{{ route('admin.login.attempt') }}" class="stack">
                    @csrf
                    <div class="field">
                        <label for="email">Correo</label>
                        <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
                    </div>
                    <div class="field">
                        <label for="password">Contraseña</label>
                        <input class="input" id="password" name="password" type="password" required autocomplete="current-password">
                    </div>
                    <button class="btn btn-primary btn-block" type="submit" data-submit-once style="margin-top: 8px">Entrar</button>
                </form>

                @if (config('tienda.demo') && app()->isLocal())
                    <p class="hint">Acceso de prueba: <strong>admin@demo.test</strong> / <strong>demo1234</strong></p>
                @endif

                <a class="btn btn-quiet" href="{{ route('home') }}">Volver a la tienda</a>
            </div>
        </div>
    </main>
@endsection
