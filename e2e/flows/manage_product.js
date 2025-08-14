import { updateIndexCatalog } from '../helpers';

export async function setProductPrice(page, admin, price, product) {
    await page.goto(admin.url);
    await page.waitForLoadState();

    await page.getByRole('textbox', { name: 'Username *' }).fill(admin.user);
    await page.getByRole('textbox', { name: 'Password *' }).fill(admin.password);
    await page.getByRole('button', { name: 'Sign in' }).click();

    await page.waitForLoadState();
    await page.waitForTimeout(1000);
    await page.locator('#menu-magento-catalog-catalog').click();
    await page.getByRole('link', { name: 'Products', exact: true }).click();
    await page.getByRole('button', { name: 'Filters' }).click();
    await page.getByRole('textbox', { name: 'Name' }).dblclick();
    await page.getByRole('textbox', { name: 'Name' }).fill(`${product}`);
    await page.getByRole('button', { name: 'Apply Filters' }).click();
    await page.getByRole('table').getByText(`${product}`).click();
    await page.waitForLoadState();

    await page.getByRole('textbox', { name: /\[GLOBAL\] Price \*/ }).click();
    await page.getByRole('textbox', { name: /\[GLOBAL\] Price \*/ }).fill(`${price}`);
    await page.getByRole('button', { name: 'Save', exact: true }).click();
    await page.waitForLoadState();
    await page.waitForTimeout(3000);

    await page.locator('.admin__action-dropdown-text').click();
    await page.getByRole('link', { name: 'Sign Out' }).click();
    await page.waitForLoadState();

    await updateIndexCatalog();
};

export async function getProduct(page, url) {
    await updateIndexCatalog();
    await page.goto(url);
    await page.waitForLoadState();
    const item = page.locator('.product-items .product-item').first();

    await page.getByRole('menuitem', { name: 'Gear' }).hover();
    await page.getByRole('menuitem', { name: 'Bags' }).click();

    await item.waitFor({ state: 'visible' });
    await item.click();

    const product = await page.locator('h1 span[itemprop="name"]').textContent();
    return product;
}
