<?php

namespace Tests\Feature\Usuarios;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** El perfil de la API da los datos del usuario sin campos internos. */
class ApiPerfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_perfil_no_expone_campos_internos(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/perfil')
            ->assertOk()
            ->assertJsonPath('name', $user->name)
            ->assertJsonPath('email', $user->email)
            ->assertJsonMissingPath('is_admin')
            ->assertJsonMissingPath('bloqueado_at')
            ->assertJsonMissingPath('ultimo_acceso_at');
    }

    public function test_actualizar_el_nombre(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/perfil', ['name' => 'Nuevo nombre'])
            ->assertOk()
            ->assertJsonPath('name', 'Nuevo nombre')
            ->assertJsonMissingPath('is_admin');

        $this->assertSame('Nuevo nombre', $user->fresh()->name);
    }
}
