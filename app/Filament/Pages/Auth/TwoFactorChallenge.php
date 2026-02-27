<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorChallenge extends SimplePage
{
    protected static string | array $withoutRouteMiddleware = [
        \Filament\Http\Middleware\Authenticate::class,
    ];

    public string $code = '';

    public function mount(): void
    {
        if (! session('2fa_user_id')) {
            $this->redirect(route('filament.admin.auth.login'));
        }
    }

    public function getTitle(): string | Htmlable
    {
        return 'Двофакторска аутентификација';
    }

    public function getSubHeading(): string | Htmlable | null
    {
        return 'Унесите 6-цифрени код послат на вашу е-пошту';
    }

    public function hasLogo(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label('Верификациони код')
                ->required()
                ->autofocus()
                ->autocomplete('off')
                ->placeholder('000000')
                ->maxLength(6)
                ->extraInputAttributes([
                    'class' => 'text-center text-2xl tracking-widest',
                    'inputmode' => 'numeric',
                    'pattern' => '\d{6}',
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('verify')
                ->footer([
                    Actions::make([$this->getVerifyAction()])
                        ->fullWidth(true),
                ]),
        ]);
    }

    protected function getVerifyAction(): Action
    {
        return Action::make('verify')
            ->label('Верификуј');
    }

    public function verify(): void
    {
        $userId = session('2fa_user_id');

        if (! $userId) {
            $this->redirect(route('filament.admin.auth.login'));
            return;
        }

        $rateLimitKey = '2fa_verify:' . $userId;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $this->addError('code', "Превише покушаја. Покушајте поново за {$seconds} секунди.");
            return;
        }

        RateLimiter::hit($rateLimitKey, 600);

        $this->validate([
            'code' => ['required', 'regex:/^\d{6}$/'],
        ], [
            'code.required' => 'Унесите верификациони код.',
            'code.regex' => 'Верификациони код мора имати тачно 6 цифара.',
        ]);

        $user = User::find($userId);

        if (! $user) {
            $this->redirect(route('filament.admin.auth.login'));
            return;
        }

        if (! hash_equals((string) ($user->two_factor_code ?? ''), trim($this->code))) {
            $this->addError('code', 'Неважећи верификациони код. Проверите е-пошту и покушајте поново.');
            $this->code = '';
            return;
        }

        if (now()->isAfter($user->two_factor_expires_at)) {
            $this->addError('code', 'Верификациони код је истекао. Пријавите се поново да добијете нови код.');
            $this->code = '';
            return;
        }

        RateLimiter::clear($rateLimitKey);

        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ]);

        session()->forget('2fa_user_id');

        Auth::login($user, false);

        $this->redirect(route('filament.admin.pages.dashboard'));
    }
}
