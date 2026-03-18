<?php

describe('Vista alias de carga Excel de bienes', function () {

    it('el administrador puede acceder a la vista alias de carga Excel', function () {
        $this->actingAs(adminUser())
            ->get(route('goods.excel-upload-global'))
            ->assertStatus(200)
            ->assertSee('Cargar bienes al catalogo desde Excel')
            ->assertSee('excelFileInput')
            ->assertSee('goodsPreviewTable')
            ->assertSee('btnLimpiarExcel')
            ->assertSee('btnEnviarExcel');
    });

    it('el usuario no autenticado es redirigido al login', function () {
        $this->get(route('goods.excel-upload-global'))
            ->assertRedirect('/login');
    });
});
