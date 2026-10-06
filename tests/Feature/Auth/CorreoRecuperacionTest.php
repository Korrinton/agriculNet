<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CorreoRecuperacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_correo_de_recuperacion_va_en_castellano(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        Volt::test('pages.auth.forgot-password')->set('email', $user->email)->call('sendPasswordResetLink')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $aviso) use ($user) {
            $correo = $aviso->toMail($user);
            $html = (string) $correo->render();

            $this->assertSame('Restablece tu contraseña', $correo->subject);
            $this->assertStringContainsString('¡Hola!', $html);
            $this->assertStringContainsString('Restablecer contraseña', $html);
            $this->assertStringContainsString('caduca dentro de 60 minutos', $html);
            $this->assertStringContainsString('copia y pega esta dirección', $html);
            $this->assertStringNotContainsString('Reset Password', $html);

            return true;
        });
    }
}
