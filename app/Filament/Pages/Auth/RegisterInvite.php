<?php

namespace App\Filament\Pages\Auth;

use App\Models\Invitation;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SimplePage;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterInvite extends SimplePage
{
    protected static string $view = 'filament.pages.auth.register-invite';

    protected static ?string $title = 'Завршите регистрацију';

    public ?string $token = null;

    public ?string $name = null;

    public ?string $password = null;

    public ?string $passwordConfirmation = null;

    public ?int $organ_id = null;

    public function mount(string $token): void
    {
        $this->token = $token;

        // Proveri da li je pozivnica validna
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation) {
            Notification::make()
                ->danger()
                ->title('Невалидна позивница')
                ->body('Позивница коју покушавате да користите не постоји.')
                ->persistent()
                ->send();

            $this->redirect('/');
            return;
        }

        if ($invitation->isAccepted()) {
            Notification::make()
                ->warning()
                ->title('Позивница је већ искоришћена')
                ->body('Ова позивница је већ искоришћена за регистрацију.')
                ->persistent()
                ->send();

            $this->redirect('/');
            return;
        }

        if ($invitation->isExpired()) {
            Notification::make()
                ->danger()
                ->title('Позивница је истекла')
                ->body('Ова позивница је истекла. Молимо вас да контактирате администратора.')
                ->persistent()
                ->send();

            $this->redirect('/');
            return;
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Име и презиме')
                    ->required()
                    ->maxLength(255),

                Select::make('organ_id')
                    ->label('Орган')
                    ->options(fn() => \App\Models\SifarnikOrgani::orderBy('organ', 'asc')->pluck('organ', 'id')->toArray())
                    ->required()
                    ->searchable(),

                TextInput::make('password')
                    ->label('Лозинка')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->same('passwordConfirmation')
                    ->revealable(),

                TextInput::make('passwordConfirmation')
                    ->label('Потврда лозинке')
                    ->password()
                    ->required()
                    ->revealable(),
            ]);
    }

    public function register(): void
    {
        $data = $this->form->getState();

        $invitation = Invitation::where('token', $this->token)->first();

        if (!$invitation || !$invitation->isValid()) {
            throw ValidationException::withMessages([
                'token' => 'Позивница није валидна.',
            ]);
        }

        // Proveri da li korisnik sa ovim email-om već postoji
        if (User::where('email', $invitation->email)->exists()) {
            Notification::make()
                ->danger()
                ->title('Email адреса је већ регистрована')
                ->body('Корисник са овом email адресом већ постоји.')
                ->persistent()
                ->send();

            throw ValidationException::withMessages([
                'email' => 'Корисник са овом email адресом већ постоји.',
            ]);
        }

        // Kreiraj korisnika
        $user = User::create([
            'name' => $data['name'],
            'email' => $invitation->email,
            'password' => Hash::make($data['password']),
            'organ_id' => $data['organ_id'],
        ]);

        // Dodeli "user" ulogu
        $user->assignRole('user');

        // Markuj pozivnicu kao prihvaćenu
        $invitation->markAsAccepted();

        // Loguj korisnika
        auth()->login($user);

        // Regenerate session for security (prevents session fixation attacks)
        session()->regenerate();

        Notification::make()
            ->success()
            ->title('Регистрација успешна')
            ->body('Ваш налог је успешно креиран.')
            ->send();

        $this->redirect('/');
    }
}
