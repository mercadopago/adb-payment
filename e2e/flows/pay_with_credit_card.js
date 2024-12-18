export default async function(page, ccData) {
    await page.waitForTimeout(3000);
    await page.locator('[type=radio][value="mercadopago_adbpayment_cc"]').click();
    await page.waitForTimeout(3000);
    await page.locator('[id="mercadopago_adbpayment_cc_payer_document_type"]').selectOption('CPF');
    await page.locator('[name="payment[payer_document_identification]"]').first().fill(ccData.document);

    await page.frameLocator('iframe[name="cardNumber"]').locator('[name="cardNumber"]').fill(ccData.number);
    await page.frameLocator('iframe[name="securityCode"]').locator('[name="securityCode"]').fill(ccData.code);
    await page.frameLocator('iframe[name="expirationMonth"]').locator('[name="expirationMonth"]').fill(ccData.month);
    await page.frameLocator('iframe[name="expirationYear"]').locator('[name="expirationYear"]').fill(ccData.year);

    await page.locator('[name="payment[card_holder_name]"]').first().fill(ccData.name);
    await page.waitForTimeout(3000);
    await page.locator('select[name="payment[card_installments]"]').selectOption('1');

    await page.waitForTimeout(1000);

    await page.getByRole('button', { name: /Realizar Pedido|Place Order/ }).click();

    await page.waitForTimeout(1000);
}
