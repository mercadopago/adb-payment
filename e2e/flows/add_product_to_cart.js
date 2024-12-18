export default async function(page) {
    await page.getByRole('link', { name: 'Radiant Tee' }).first().click();
    await page.waitForTimeout(5000);
    await page.getByLabel('XS').click();
    await page.getByLabel('Blue').click();
    await page.getByRole('button', { name: 'Add to Cart' }).click();
    await page.getByRole('link', { name: ' My Cart 1 1 items' }).click();
    await page.getByRole('button', { name: 'Proceed to Checkout' }).click();
}
