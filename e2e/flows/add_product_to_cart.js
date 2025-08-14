export default async function(page) {
    await page.waitForLoadState();
    const firstProduct = page.locator('.product-items .product-item').first();

    await page.getByRole('menuitem', { name: 'Gear' }).hover();
    await page.getByRole('menuitem', { name: 'Bags' }).click();

    await firstProduct.waitFor({ state: 'visible' });
    await firstProduct.hover();
    await firstProduct.getByRole('button', { name: 'Add to Cart' }).click();

    await page.waitForLoadState();
    await page.getByRole('link', { name: 'shopping cart' }).click();
    await page.waitForLoadState();
    await page.waitForTimeout(1000);
    await page.getByRole('button', { name: 'Proceed to Checkout' }).click();
}
