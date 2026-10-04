<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InicioTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_raiz_lleva_al_login_sin_sesion(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_la_raiz_lleva_al_panel_con_sesion(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_el_panel_carga(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk();
    }

    public function test_el_panel_exige_sesion(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
