<?php

namespace Tests\Browser;

use Illuminate\Http\UploadedFile;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class EnviarArquivoTest extends DuskTestCase
{
    public function test_EnviarArquivo(): void
    {
        $file = UploadedFile::fake()->createWithContent('dusk-sample.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF"
        );

        $this->browse(function (Browser $browser) use ($file) {
            $browser->visit('/')
                ->clickLink('Entrar')
                ->waitFor('#loginUsuario')
                ->typeSlowly('#loginUsuario', '1111')
                ->press('Login')
                ->waitForLocation('/')
                ->visit('/files/create')
                ->waitFor('input[name="file"]')
                ->attach('input[name="file"]', $file->getPathname())
                ->type('input[name="name"]', 'Arquivo de teste Dusk')
                ->press('Enviar')
                ->assertPathIs('/files')
                ->assertSee('Arquivo de teste Dusk');
        });
    }
}
