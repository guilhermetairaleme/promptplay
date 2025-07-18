@component('mail::message')

<img src="{{ asset("imagem/$picture.promptplay.png") }}"
     alt="Logo"
     style="width: 300px; margin: 40px auto; display: block;">

# Olá {{ $user->name ?? 'Usuário' }}!

Sua conta foi criada com sucesso. 🎉

- **E-mail:** {{ $user->email ?? '' }}
- **Senha temporária:** `{{ $temporaryPassword ?? '' }}`

@component('mail::button', ['url' => route('password.request')])
Redefinir Senha
@endcomponent

@component('mail::button', ['url' => "$picture.promptplay.com.br"])
Acessar Site
@endcomponent

Obrigado,<br>
{{ config('app.name') }}
@endcomponent
