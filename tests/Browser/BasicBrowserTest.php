<?php

describe('Basic Browser Testing', function () {
    it('can visit the home page', function () {
        $page = visit('/');

        $page->assertSee('Bem-vindo')
            ->assertNoJavaScriptErrors();
    });

    it('can visit the partner registration page', function () {
        $page = visit('/partners');

        $page->assertSee('Cadastro de Colaborador Parceiro')
            ->assertNoJavaScriptErrors()
            ->assertElementExists('[name="data.name"]')
            ->assertElementExists('[name="data.email"]')
            ->assertElementExists('button[type="submit"]');
    });

    it('can fill and interact with form fields', function () {
        $page = visit('/partners');

        $page->type('[name="data.name"]', 'Test User')
            ->assertValue('[name="data.name"]', 'Test User')
            ->type('[name="data.email"]', 'test@example.com')
            ->assertValue('[name="data.email"]', 'test@example.com');
    });
})->group('browser', 'basic');
