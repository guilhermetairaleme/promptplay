@component('mail::message')

<img src="{{ asset('imagem/sistema.promptplay.png') }}" alt="Logo" style="width: 300px; margin-bottom: 20px;">

# Olá {{ $user->name ?? 'Usuário' }}!

Sua conta foi criada com sucesso. 🎉

- **E-mail:** {{ $user->email ?? '' }}
- **Senha temporária:** `{{ $temporaryPassword ?? '' }}`

@component('mail::button', ['url' => route('password.request')])
Redefinir Senha
@endcomponent

Obrigado,<br>
{{ config('app.name') }}
@endcomponent
