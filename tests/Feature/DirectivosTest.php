<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DirectivoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Las cuentas de directivo administradas por el administrador.
 *
 * La base de datos de este equipo tiene los datos de verdad del club,
 * asi que cada prueba corre dentro de una transaccion que se deshace al
 * terminar. Va en setUp/tearDown y no dentro de cada prueba a proposito:
 * si una afirmacion falla a mitad de camino, un rollBack escrito al
 * final de la prueba no se ejecutaria y quedarian usuarios de mentira
 * sueltos en la base real.
 */
class DirectivosTest extends TestCase
{
    private const CLAVE_ADMIN = 'clave-del-admin-9876';

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    /** Un administrador de mentira, con una clave que las pruebas conocen. */
    private function admin(): User
    {
        return User::create([
            'name' => 'Admin De Prueba',
            'email' => 'admin-prueba-'.Str::random(8).'@local.test',
            'password' => self::CLAVE_ADMIN,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function directivo(string $clave = 'clave-directivo-123'): User
    {
        return User::create([
            'name' => 'Directivo De Prueba',
            'email' => 'directivo-prueba-'.Str::random(8).'@local.test',
            'password' => $clave,
            'role' => User::ROLE_DIRECTIVO,
        ]);
    }

    /** Una sesion abierta de mentira en la tabla de sesiones. */
    private function abrirSesion(User $usuario): string
    {
        $id = Str::random(40);

        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $usuario->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Pruebas',
            'payload' => base64_encode(serialize([])),
            'last_activity' => time(),
        ]);

        return $id;
    }

    private function sesionSigueAbierta(string $id): bool
    {
        return DB::table('sessions')->where('id', $id)->exists();
    }

    // ================= QUIEN ENTRA =================

    public function test_el_administrador_abre_la_pantalla(): void
    {
        $this->directivo();

        $this->actingAs($this->admin())
            ->get(route('directivos.index'))
            ->assertOk()
            ->assertSee('Cuentas de directivo');
    }

    public function test_el_directivo_no_abre_la_pantalla(): void
    {
        $this->actingAs($this->directivo())
            ->get(route('directivos.index'))
            ->assertRedirect(route('socios.index'));
    }

    public function test_sin_sesion_no_se_abre_la_pantalla(): void
    {
        $this->get(route('directivos.index'))->assertRedirect(route('login'));
    }

    public function test_el_directivo_no_puede_crear_cuentas(): void
    {
        // El directivo se crea antes de contar: si no, la cuenta de la
        // propia prueba se contaria como una cuenta creada por el.
        $directivo = $this->directivo();
        $antes = User::count();

        $this->actingAs($directivo)
            ->post(route('directivos.store'), [
                'name' => 'Colado',
                'email' => 'colado@local.test',
                'password' => 'clave-colada-123',
                'password_confirmation' => 'clave-colada-123',
            ])
            ->assertRedirect(route('socios.index'));

        $this->assertSame($antes, User::count(), 'No se creo ninguna cuenta.');
    }

    public function test_el_directivo_no_puede_cambiarle_la_clave_a_otro(): void
    {
        $otro = $this->directivo('clave-del-otro-123');

        $this->actingAs($this->directivo())
            ->put(route('directivos.password', $otro), [
                'password' => 'clave-nueva-impuesta',
                'password_confirmation' => 'clave-nueva-impuesta',
                'password_admin' => 'clave-directivo-123',
            ])
            ->assertRedirect(route('socios.index'));

        $this->assertTrue(
            Hash::check('clave-del-otro-123', $otro->fresh()->password),
            'La clave del otro directivo no cambio.',
        );
    }

    public function test_el_directivo_no_puede_eliminar_cuentas(): void
    {
        $otro = $this->directivo();

        $this->actingAs($this->directivo())
            ->delete(route('directivos.destroy', $otro), ['password_admin' => 'clave-directivo-123'])
            ->assertRedirect(route('socios.index'));

        $this->assertNotNull($otro->fresh(), 'La cuenta sigue ahi.');
    }

    public function test_el_menu_no_le_ofrece_la_pantalla_al_directivo(): void
    {
        $htmlDirectivo = $this->actingAs($this->directivo())
            ->get(route('socios.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Cuentas de directivo', $htmlDirectivo);

        $htmlAdmin = $this->actingAs($this->admin())
            ->get(route('socios.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Cuentas de directivo', $htmlAdmin);
    }

    // ================= LA CUENTA DEL ADMINISTRADOR NO SE TOCA =================

    public function test_no_se_puede_editar_al_administrador_por_su_id(): void
    {
        $admin = $this->admin();
        $otroAdmin = $this->admin();

        $this->actingAs($admin)
            ->put(route('directivos.update', $otroAdmin), [
                'name' => 'Secuestrado',
                'email' => 'secuestrado@local.test',
            ])
            ->assertNotFound();

        $this->assertSame('Admin De Prueba', $otroAdmin->fresh()->name);
        $this->assertSame(User::ROLE_ADMIN, $otroAdmin->fresh()->role);
    }

    public function test_no_se_puede_cambiarle_la_clave_a_un_administrador_por_su_id(): void
    {
        $admin = $this->admin();
        $otroAdmin = $this->admin();

        $this->actingAs($admin)
            ->put(route('directivos.password', $otroAdmin), [
                'password' => 'clave-impuesta-123',
                'password_confirmation' => 'clave-impuesta-123',
                'password_admin' => self::CLAVE_ADMIN,
            ])
            ->assertNotFound();

        $this->assertTrue(
            Hash::check(self::CLAVE_ADMIN, $otroAdmin->fresh()->password),
            'La clave del otro administrador quedo intacta.',
        );
    }

    public function test_no_se_puede_eliminar_a_un_administrador_por_su_id(): void
    {
        $admin = $this->admin();
        $otroAdmin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('directivos.destroy', $otroAdmin), ['password_admin' => self::CLAVE_ADMIN])
            ->assertNotFound();

        $this->assertNotNull($otroAdmin->fresh(), 'El otro administrador sigue existiendo.');
    }

    public function test_la_pantalla_no_lista_a_los_administradores(): void
    {
        $admin = $this->admin();
        $directivo = $this->directivo();

        $html = $this->actingAs($admin)->get(route('directivos.index'))->assertOk()->getContent();

        $this->assertStringContainsString($directivo->email, $html);
        $this->assertStringNotContainsString($admin->email, $html);
    }

    // ================= CREAR =================

    public function test_crea_la_cuenta_con_rol_de_directivo(): void
    {
        $this->actingAs($this->admin())
            ->post(route('directivos.store'), [
                'name' => 'Juan Perez',
                'email' => 'juan-nuevo@local.test',
                'password' => 'clave-de-juan-123',
                'password_confirmation' => 'clave-de-juan-123',
            ])
            ->assertRedirect(route('directivos.index'));

        $creado = User::where('email', 'juan-nuevo@local.test')->firstOrFail();
        $this->assertSame(User::ROLE_DIRECTIVO, $creado->role);
        $this->assertSame('Juan Perez', $creado->name);
    }

    public function test_la_clave_se_guarda_cifrada_y_nunca_en_claro(): void
    {
        $this->actingAs($this->admin())
            ->post(route('directivos.store'), [
                'name' => 'Juan Perez',
                'email' => 'juan-cifrado@local.test',
                'password' => 'clave-de-juan-123',
                'password_confirmation' => 'clave-de-juan-123',
            ]);

        $creado = User::where('email', 'juan-cifrado@local.test')->firstOrFail();
        $this->assertNotSame('clave-de-juan-123', $creado->password);
        $this->assertTrue(Hash::check('clave-de-juan-123', $creado->password));
    }

    public function test_el_directivo_nuevo_puede_entrar_con_lo_que_se_le_entrego(): void
    {
        $this->actingAs($this->admin())
            ->post(route('directivos.store'), [
                'name' => 'Juan Perez',
                'email' => 'juan-entra@local.test',
                'password' => 'clave-de-juan-123',
                'password_confirmation' => 'clave-de-juan-123',
            ]);

        auth()->logout();

        $this->post(route('login.store'), [
            'email' => 'juan-entra@local.test',
            'password' => 'clave-de-juan-123',
        ])->assertRedirect(route('socios.index'));

        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->esDirectivo());
    }

    public function test_un_rol_colado_en_la_peticion_se_rechaza(): void
    {
        $this->actingAs($this->admin())
            ->post(route('directivos.store'), [
                'name' => 'Aspirante',
                'email' => 'aspirante@local.test',
                'password' => 'clave-aspirante-123',
                'password_confirmation' => 'clave-aspirante-123',
                'role' => User::ROLE_ADMIN,
            ])
            ->assertSessionHasErrors('role');

        $this->assertNull(
            User::where('email', 'aspirante@local.test')->first(),
            'No se creo nada: la peticion se rechazo entera.',
        );
    }

    public function test_no_admite_un_correo_que_ya_tiene_otro_usuario(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('directivos.store'), [
                'name' => 'Clon',
                'email' => $admin->email,
                'password' => 'clave-del-clon-123',
                'password_confirmation' => 'clave-del-clon-123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame(
            1,
            User::where('email', $admin->email)->count(),
            'Sigue habiendo un solo usuario con ese correo.',
        );
    }

    public function test_exige_una_clave_de_al_menos_ocho_caracteres_y_repetida(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('directivos.store'), [
                'name' => 'Corta',
                'email' => 'corta@local.test',
                'password' => 'abc123',
                'password_confirmation' => 'abc123',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($admin)
            ->post(route('directivos.store'), [
                'name' => 'Distinta',
                'email' => 'distinta@local.test',
                'password' => 'clave-buena-123',
                'password_confirmation' => 'clave-otra-456',
            ])
            ->assertSessionHasErrors('password');

        $this->assertSame(0, User::whereIn('email', ['corta@local.test', 'distinta@local.test'])->count());
    }

    // ================= EDITAR NOMBRE Y CORREO =================

    public function test_cambia_el_nombre_y_el_correo(): void
    {
        $directivo = $this->directivo();

        $this->actingAs($this->admin())
            ->put(route('directivos.update', $directivo), [
                'name' => 'Nombre Corregido',
                'email' => 'correo-corregido@local.test',
            ])
            ->assertRedirect(route('directivos.index'));

        $directivo->refresh();
        $this->assertSame('Nombre Corregido', $directivo->name);
        $this->assertSame('correo-corregido@local.test', $directivo->email);
        $this->assertSame(User::ROLE_DIRECTIVO, $directivo->role, 'El rol no se movio.');
    }

    public function test_editar_el_nombre_no_le_cambia_la_clave(): void
    {
        $directivo = $this->directivo('clave-original-123');

        $this->actingAs($this->admin())
            ->put(route('directivos.update', $directivo), [
                'name' => 'Otro Nombre',
                'email' => $directivo->email,
            ]);

        $this->assertTrue(
            Hash::check('clave-original-123', $directivo->fresh()->password),
            'Sigue entrando con la misma clave.',
        );
    }

    public function test_puede_guardar_sin_cambiarle_el_correo(): void
    {
        $directivo = $this->directivo();

        $this->actingAs($this->admin())
            ->put(route('directivos.update', $directivo), [
                'name' => 'Nombre Nuevo',
                'email' => $directivo->email,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nombre Nuevo', $directivo->fresh()->name);
    }

    public function test_no_puede_quedarse_con_el_correo_del_administrador(): void
    {
        $admin = $this->admin();
        $directivo = $this->directivo();

        $this->actingAs($admin)
            ->put(route('directivos.update', $directivo), [
                'name' => $directivo->name,
                'email' => $admin->email,
            ])
            ->assertSessionHasErrors('email');

        $this->assertNotSame($admin->email, $directivo->fresh()->email);
    }

    public function test_una_clave_colada_en_el_formulario_de_nombre_se_rechaza(): void
    {
        $directivo = $this->directivo('clave-original-123');

        $this->actingAs($this->admin())
            ->put(route('directivos.update', $directivo), [
                'name' => $directivo->name,
                'email' => $directivo->email,
                'password' => 'clave-por-la-puerta-de-atras',
                'password_confirmation' => 'clave-por-la-puerta-de-atras',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(
            Hash::check('clave-original-123', $directivo->fresh()->password),
            'Ese formulario no sirve para cambiar la clave sin confirmar la del administrador.',
        );
    }

    // ================= CLAVE NUEVA =================

    public function test_le_asigna_una_clave_nueva_sin_saber_la_anterior(): void
    {
        $directivo = $this->directivo('clave-vieja-123');

        $this->actingAs($this->admin())
            ->put(route('directivos.password', $directivo), [
                'password' => 'clave-nueva-456',
                'password_confirmation' => 'clave-nueva-456',
                'password_admin' => self::CLAVE_ADMIN,
            ])
            ->assertRedirect(route('directivos.index'));

        $directivo->refresh();
        $this->assertTrue(Hash::check('clave-nueva-456', $directivo->password), 'Entra con la nueva.');
        $this->assertFalse(Hash::check('clave-vieja-123', $directivo->password), 'Ya no entra con la vieja.');
    }

    public function test_sin_la_clave_del_administrador_no_se_cambia_nada(): void
    {
        $directivo = $this->directivo('clave-vieja-123');

        $this->actingAs($this->admin())
            ->put(route('directivos.password', $directivo), [
                'password' => 'clave-nueva-456',
                'password_confirmation' => 'clave-nueva-456',
                'password_admin' => 'esta-no-es-la-del-admin',
            ])
            ->assertSessionHasErrors('password_admin');

        $this->assertTrue(
            Hash::check('clave-vieja-123', $directivo->fresh()->password),
            'La clave quedo como estaba.',
        );
    }

    public function test_la_clave_del_administrador_es_obligatoria(): void
    {
        $directivo = $this->directivo('clave-vieja-123');

        $this->actingAs($this->admin())
            ->put(route('directivos.password', $directivo), [
                'password' => 'clave-nueva-456',
                'password_confirmation' => 'clave-nueva-456',
            ])
            ->assertSessionHasErrors('password_admin');

        $this->assertTrue(Hash::check('clave-vieja-123', $directivo->fresh()->password));
    }

    public function test_la_clave_nueva_tiene_que_venir_repetida(): void
    {
        $directivo = $this->directivo('clave-vieja-123');

        $this->actingAs($this->admin())
            ->put(route('directivos.password', $directivo), [
                'password' => 'clave-nueva-456',
                'password_confirmation' => 'clave-distinta-789',
                'password_admin' => self::CLAVE_ADMIN,
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('clave-vieja-123', $directivo->fresh()->password));
    }

    public function test_el_directivo_entra_con_la_clave_nueva_y_no_con_la_vieja(): void
    {
        $directivo = $this->directivo('clave-vieja-123');

        $this->actingAs($this->admin())->put(route('directivos.password', $directivo), [
            'password' => 'clave-nueva-456',
            'password_confirmation' => 'clave-nueva-456',
            'password_admin' => self::CLAVE_ADMIN,
        ]);

        auth()->logout();

        $this->post(route('login.store'), [
            'email' => $directivo->email,
            'password' => 'clave-vieja-123',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => $directivo->email,
            'password' => 'clave-nueva-456',
        ])->assertRedirect(route('socios.index'));
        $this->assertAuthenticated();
    }

    // ================= LAS SESIONES ABIERTAS =================

    public function test_cambiar_la_clave_le_cierra_la_sesion_que_tenia_abierta(): void
    {
        // En pruebas las sesiones van en memoria (phpunit.xml pone
        // SESSION_DRIVER=array), asi que se pone el driver que usa el
        // sistema de verdad para ejercitar el camino real.
        config(['session.driver' => 'database']);

        $directivo = $this->directivo();
        $suya = $this->abrirSesion($directivo);
        $deOtro = $this->abrirSesion($this->directivo());

        $this->actingAs($this->admin())->put(route('directivos.password', $directivo), [
            'password' => 'clave-nueva-456',
            'password_confirmation' => 'clave-nueva-456',
            'password_admin' => self::CLAVE_ADMIN,
        ]);

        $this->assertFalse($this->sesionSigueAbierta($suya), 'Si tenia la sesion abierta, se cerro.');
        $this->assertTrue($this->sesionSigueAbierta($deOtro), 'A los demas no se les toco la sesion.');
    }

    public function test_cambiar_la_clave_anula_el_recordarme(): void
    {
        config(['session.driver' => 'database']);

        $directivo = $this->directivo();
        $directivo->forceFill(['remember_token' => Str::random(60)])->save();

        $this->actingAs($this->admin())->put(route('directivos.password', $directivo), [
            'password' => 'clave-nueva-456',
            'password_confirmation' => 'clave-nueva-456',
            'password_admin' => self::CLAVE_ADMIN,
        ]);

        $this->assertNull(
            $directivo->fresh()->remember_token,
            'El token de recordarme es la otra forma de entrar sin escribir la clave.',
        );
    }

    public function test_eliminar_la_cuenta_le_cierra_las_sesiones(): void
    {
        config(['session.driver' => 'database']);

        $directivo = $this->directivo();
        $suya = $this->abrirSesion($directivo);

        $this->actingAs($this->admin())
            ->delete(route('directivos.destroy', $directivo), ['password_admin' => self::CLAVE_ADMIN]);

        $this->assertFalse(
            $this->sesionSigueAbierta($suya),
            'La tabla de sesiones no tiene llave foranea, asi que hay que borrarlas a mano.',
        );
    }

    // ================= ELIMINAR =================

    public function test_elimina_la_cuenta_y_deja_de_poder_entrar(): void
    {
        $directivo = $this->directivo('clave-del-directivo-123');
        $correo = $directivo->email;

        $this->actingAs($this->admin())
            ->delete(route('directivos.destroy', $directivo), ['password_admin' => self::CLAVE_ADMIN])
            ->assertRedirect(route('directivos.index'));

        $this->assertNull(User::where('email', $correo)->first());

        auth()->logout();
        $this->post(route('login.store'), [
            'email' => $correo,
            'password' => 'clave-del-directivo-123',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_sin_la_clave_del_administrador_no_se_elimina(): void
    {
        $directivo = $this->directivo();

        $this->actingAs($this->admin())
            ->delete(route('directivos.destroy', $directivo), ['password_admin' => 'esta-no-es'])
            ->assertSessionHasErrors('password_admin');

        $this->assertNotNull($directivo->fresh(), 'La cuenta sigue ahi.');
    }

    public function test_borrar_la_cuenta_no_borra_lo_que_el_directivo_registro(): void
    {
        $directivo = $this->directivo();

        $idNoticia = DB::table('noticias')->insertGetId([
            'titulo' => 'Noticia de prueba',
            'contenido' => 'Contenido de prueba',
            'fecha_publicacion' => '2026-01-01',
            'publicado' => true,
            'user_id' => $directivo->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->delete(route('directivos.destroy', $directivo), ['password_admin' => self::CLAVE_ADMIN]);

        $noticia = DB::table('noticias')->where('id', $idNoticia)->first();

        $this->assertNotNull($noticia, 'La noticia sigue publicada.');
        $this->assertSame('Noticia de prueba', $noticia->titulo);
        $this->assertNull($noticia->user_id, 'Solo perdio el nombre de quien la hizo.');
    }

    public function test_un_cierre_de_caja_sin_dueno_se_muestra_como_del_sistema(): void
    {
        $directivo = $this->directivo();

        $idCierre = DB::table('cierres_caja')->insertGetId([
            'fecha' => '2099-12-31',
            'total_efectivo' => 0,
            'total_transferencia' => 0,
            'total_ingresos' => 0,
            'total_gastos' => 0,
            'total_neto_efectivo' => 0,
            'user_id' => $directivo->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->admin();

        // Se mira la linea "Guardado por" y no la pagina entera: la
        // palabra "Sistema" tambien es el nombre de un grupo del menu,
        // asi que buscarla sola daria por bueno cualquier resultado.
        $guardadoPor = function () use ($admin, $idCierre): string {
            $html = $this->actingAs($admin)
                ->get(route('cierre-caja.show', $idCierre))
                ->assertOk()
                ->getContent();

            preg_match('#Guardado por:</strong>\s*(.*?)\s*el\s#s', $html, $m);

            return trim($m[1] ?? '(no se encontro la linea)');
        };

        $this->assertSame($directivo->name, $guardadoPor());

        $this->actingAs($admin)
            ->delete(route('directivos.destroy', $directivo), ['password_admin' => self::CLAVE_ADMIN])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('directivos.index'))
            // El aviso de confirmacion nombra la cuenta borrada, asi que
            // ese nombre aparece una vez mas en la pantalla siguiente. Se
            // deja afirmado para que nadie lo confunda con un dato que
            // quedo sin borrar.
            ->assertSessionHas('status', fn (string $aviso) => str_contains($aviso, $directivo->name));

        $this->assertNull(
            DB::table('cierres_caja')->where('id', $idCierre)->value('user_id'),
            'La llave quedo en nulo, no borro el cierre.',
        );

        $this->assertSame('Sistema', $guardadoPor());
    }

    public function test_la_pantalla_avisa_cuantos_registros_perderian_el_nombre(): void
    {
        $directivo = $this->directivo();

        foreach (['Una', 'Otra'] as $titulo) {
            DB::table('noticias')->insert([
                'titulo' => $titulo,
                'contenido' => 'x',
                'fecha_publicacion' => '2026-01-01',
                'publicado' => true,
                'user_id' => $directivo->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('directivos.index'))
            ->assertOk()
            ->assertSee('Noticias publicadas')
            ->assertSee('Lo que registro NO se borra');
    }

    // ================= EL SERVICIO =================

    public function test_el_servicio_se_niega_a_tocar_una_cuenta_de_administrador(): void
    {
        $servicio = app(DirectivoService::class);
        $admin = $this->admin();

        foreach ([
            'actualizar' => fn () => $servicio->actualizar($admin, ['name' => 'X', 'email' => 'x@local.test']),
            'cambiarPassword' => fn () => $servicio->cambiarPassword($admin, 'clave-nueva-123'),
            'eliminar' => fn () => $servicio->eliminar($admin),
        ] as $metodo => $llamada) {
            try {
                $llamada();
                $this->fail("$metodo acepto una cuenta de administrador.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('solo administra cuentas de directivo', $e->getMessage());
            }
        }

        $this->assertNotNull($admin->fresh());
        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
    }

    public function test_el_servicio_cuenta_bien_los_datos_asociados(): void
    {
        $servicio = app(DirectivoService::class);
        $directivo = $this->directivo();

        $this->assertSame([], $servicio->datosAsociados($directivo), 'Recien creado no tiene nada.');

        DB::table('noticias')->insert([
            'titulo' => 'Una',
            'contenido' => 'x',
            'fecha_publicacion' => '2026-01-01',
            'publicado' => true,
            'user_id' => $directivo->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(
            ['Noticias publicadas' => 1],
            $servicio->datosAsociados($directivo),
            'Solo se nombran las tablas que de verdad tienen algo.',
        );
    }

    public function test_sin_sesiones_en_la_base_no_se_afirma_un_cero(): void
    {
        // Con las sesiones fuera de la base no hay forma de contarlas, y
        // decir "cero sesiones abiertas" seria afirmar algo no comprobado.
        config(['session.driver' => 'array']);

        $this->assertNull(app(DirectivoService::class)->sesionesAbiertas($this->directivo()));
    }

    public function test_el_rol_del_directivo_creado_no_le_abre_el_panel(): void
    {
        $this->actingAs($this->admin())->post(route('directivos.store'), [
            'name' => 'Juan Perez',
            'email' => 'juan-sin-panel@local.test',
            'password' => 'clave-de-juan-123',
            'password_confirmation' => 'clave-de-juan-123',
        ]);

        $creado = User::where('email', 'juan-sin-panel@local.test')->firstOrFail();

        $this->actingAs($creado)->get(route('panel.index'))->assertRedirect(route('socios.index'));
        $this->actingAs($creado)->get(route('socios.index'))->assertOk();
    }
}
