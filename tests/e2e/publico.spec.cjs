const { test, expect } = require('@playwright/test');

for (const [ruta, titulo] of [
    ['/nosotros', 'El sabor que nos reúne.'],
    ['/como-comprar', 'Comprar en Rustic'],
    ['/aviso-legal', 'Aviso legal'],
]) {
    test(`página informativa ${ruta}`, async ({ page }) => {
        const respuesta = await page.goto(ruta);
        expect(respuesta.status()).toBe(200);
        await expect(page.locator('main#contenido')).toBeVisible();
        await expect(page.getByRole('heading', { name: titulo, exact: true })).toBeVisible();
    });
}

test('menú móvil: teclado, Escape, foco y cambio a escritorio', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/contacto');
    const boton = page.locator('[data-menu-boton]');
    const panel = page.locator('#menu-principal');
    await expect(boton).toBeVisible();
    await expect(panel).toBeHidden();
    await boton.focus();
    await page.keyboard.press('Enter');
    await expect(boton).toHaveAttribute('aria-expanded', 'true');
    await expect(panel).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(panel).toBeHidden();
    await expect(boton).toHaveAttribute('aria-expanded', 'false');
    await expect(boton).toBeFocused();
    await page.setViewportSize({ width: 1280, height: 800 });
    await expect(boton).toBeHidden();
    await expect(panel).toBeVisible();
});

test('mostrar y ocultar contraseña conserva el valor sin enviar el formulario', async ({ page }) => {
    await page.goto('/clientes/login');
    const campo = page.getByLabel('Contraseña', { exact: true });
    const boton = page.locator('[data-toggle-password="password"]');
    await campo.fill('SoloUnaPrueba-123');
    await expect(campo).toHaveAttribute('type', 'password');
    await boton.click();
    await expect(campo).toHaveAttribute('type', 'text');
    await expect(boton).toHaveAccessibleName('Ocultar contraseña');
    await expect(boton).toHaveAttribute('aria-pressed', 'true');
    await boton.click();
    await expect(campo).toHaveAttribute('type', 'password');
    await expect(boton).toHaveAccessibleName('Mostrar contraseña');
    await expect(boton).toHaveAttribute('aria-pressed', 'false');
    await expect(campo).toHaveValue('SoloUnaPrueba-123');
});

test('el panel del equipo exige iniciar sesión', async ({ page }) => {
    await page.goto('/equipo/panel');
    await expect(page).toHaveURL('http://127.0.0.1:8787/equipo/login');
    await expect(page.getByRole('heading', { name: 'Acceso del equipo', exact: true })).toBeVisible();
});
