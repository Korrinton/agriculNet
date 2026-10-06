<?php

namespace App\Modules\Alertas\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/** Correo diario con las alertas nuevas de un usuario, agrupadas por finca. */
class ResumenAlertas extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string, array<int, array{nivel: string, mensaje: string, fecha: string}>> $porFinca */
    public function __construct(
        public readonly User $user,
        public readonly array $porFinca,
    ) {}

    public function total(): int
    {
        return array_sum(array_map('count', $this->porFinca));
    }

    public function envelope(): Envelope
    {
        $importantes = collect($this->porFinca)->flatten(1)->where('nivel', 'critical')->count();
        $n = $this->total();

        return new Envelope(subject: $importantes > 0
            ? ($importantes === 1 ? '1 alerta importante' : "{$importantes} alertas importantes") . ' en tus fincas'
            : ($n === 1 ? '1 alerta nueva' : "{$n} alertas nuevas") . ' en tus fincas');
    }

    /** Baja con un clic desde el propio cliente de correo (RFC 8058: Gmail, Outlook…). */
    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe'      => '<' . $this->urlBaja() . '>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.resumen-alertas', with: [
            'urlBaja' => $this->urlBaja(),
            'total'   => $this->total(),
        ]);
    }

    /** Enlace firmado: funciona sin iniciar sesión, pero solo para este usuario. */
    public function urlBaja(): string
    {
        return URL::signedRoute('alertas.correo.baja', ['usuario' => $this->user->id]);
    }
}
