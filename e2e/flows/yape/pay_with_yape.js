export default async function(page, data) {
    await page.waitForLoadState();
    await page.locator('[type=radio][value="mercadopago_adbpayment_yape"]').click();
    await page.waitForLoadState();
    await page.locator('[id="yape-phone"]').fill(data.phone);

    for (const [index, char] of data.code.split('').entries()) {
        await page.locator('#yape-otp-inputs > input').nth(index).fill(char);
    }

    await page.locator('#billing-address-same-as-shipping-mercadopago_adbpayment_yape').check();

    await page.waitForLoadState();

    await page.locator('#payment_form_mercadopago_adbpayment_yape button.action.primary.checkout').click();
}
