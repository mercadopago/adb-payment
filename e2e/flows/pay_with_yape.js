export default async function(page, ccData) {
    await page.waitForTimeout(3000);
    await page.locator('[type=radio][value="mercadopago_adbpayment_yape"]').click();
    await page.waitForTimeout(3000);
    await page.locator('[id="yape-phone"]').fill(ccData.phone);
    await page.locator('#yape-otp-inputs > input').first().click();
    await page.locator('#yape-otp-inputs > input').first().fill(ccData.code);
    await page.waitForTimeout(1000);

    await page.getByRole('button', { name: /Realizar Pedido|Place Order/ }).click();

    await page.waitForTimeout(1000);
}
