<?php

namespace App\Modules\Admin\Jobs;

use App\Modules\Admin\Services\Tareas;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Tarea lanzada a mano desde el backoffice; corre en el worker de colas, no en la petición web. */
class LanzarTarea implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /** La importación de fitosanitarios puede tardar varios minutos (exportación de ~10 MB). */
    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(
        public readonly string $comando,
        public readonly ?int $userId,
    ) {}

    /** No se encola dos veces la misma tarea mientras la anterior espera o se ejecuta. */
    public function uniqueId(): string
    {
        return $this->comando;
    }

    public function handle(Tareas $tareas): void
    {
        $tareas->ejecutar($this->comando, $this->userId);
    }
}
