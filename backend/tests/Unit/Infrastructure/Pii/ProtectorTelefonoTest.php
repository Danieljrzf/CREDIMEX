<?php

namespace Tests\Unit\Infrastructure\Pii;

use App\Infrastructure\Pii\DatoPiiException;
use App\Infrastructure\Pii\ProtectorTelefono;
use Tests\TestCase;

final class ProtectorTelefonoTest extends TestCase
{
    private ProtectorTelefono $protector;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'credimex.pii.key_version' => 1,
            'credimex.pii.encryption_keys' => [1 => base64_encode(random_bytes(32))],
            'credimex.pii.hmac_keys' => [1 => base64_encode(random_bytes(32))],
        ]);

        $this->protector = new ProtectorTelefono;
    }

    public function test_protege_telefono_con_xchacha_y_hmac(): void
    {
        $protegido = $this->protector->protegerTelefono('(722) 123-45-67', 'clientes.telefono_principal');

        $this->assertNotSame('7221234567', $protegido['cifrado']);
        $this->assertStringNotContainsString('7221234567', $protegido['cifrado']);
        $this->assertSame(32, strlen($protegido['hmac']));
        $this->assertSame('4567', $protegido['ultimos4']);
        $this->assertSame(1, $protegido['version_clave']);
        $this->assertSame(
            '7221234567',
            $this->protector->revelarTelefono($protegido['cifrado'], 1, 'clientes.telefono_principal'),
        );
        $this->assertSame(
            $protegido['hmac'],
            $this->protector->hmacTelefono('7221234567', 1),
        );
    }

    public function test_version_inexistente_falla_cerrado(): void
    {
        $this->expectException(DatoPiiException::class);
        $this->expectExceptionMessage('No fue posible proteger el dato.');

        $this->protector->hmacTelefono('7221234567', 99);
    }
}
