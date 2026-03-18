<?php

describe('Vista de carga global Excel de bienes', function () {

    it('el administrador puede acceder a la vista global de carga Excel', function () {
        $this->actingAs(adminUser())
            ->get(route('goods.excel-upload-global'))
            ->assertStatus(200)
            ->assertSee('Cargar bienes al catalogo y asignar a inventarios')
            ->assertSee('globalExcelFileInput')
            ->assertSee('globalPreviewTable')
            ->assertSee('btnLimpiarExcelGlobal')
            ->assertSee('btnEnviarExcelGlobal');
    });

    it('el usuario no autenticado es redirigido al login', function () {
        $this->get(route('goods.excel-upload-global'))
            ->assertRedirect('/login');
    });
});
