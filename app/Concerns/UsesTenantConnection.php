<?php

namespace App\Concerns;

/**
 * Trait para modelos que residen en la base de datos del tenant.
 *
 * Todos los modelos operativos (Asset, Group, Inventory, etc.)
 * deben usar este trait para conectarse a la base correcta
 * según el tenant activo.
 *
 * En el sistema original estos modelos usaban la conexión por defecto.
 * Ahora se redirigen a la conexión 'tenant' que apunta dinámicamente
 * a la base operativa de la sede activa.
 */
trait UsesTenantConnection
{
    /**
     * Inicializa el trait estableciendo la conexión tenant.
     */
    public function initializeUsesTenantConnection(): void
    {
        $this->setConnection('tenant');
    }

    /**
     * Obtiene la conexión de base de datos del tenant.
     */
    public function getConnectionName(): ?string
    {
        return 'tenant';
    }
}
