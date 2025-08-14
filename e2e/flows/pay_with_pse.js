export const payWithPse = async function (page, document, entity_type = 'individual', financial_institutions = '1804') {
    await page.waitForLoadState();
    await page.locator('#mercadopago_adbpayment_pse').click();

    await page.waitForLoadState();

    if (document) {
        await page.locator('#mercadopago_adbpayment_pse_payer_document_type').selectOption(document.type);
        await page.locator('#mercadopago_adbpayment_pse_document_identification').fill(document.number);
    }

    await page.locator('#mercadopago_adbpayment_pse_payer_entity_type').selectOption(entity_type);
    await page.locator('#mercadopago_adbpayment_pse_financial_institutions').selectOption(financial_institutions);
    await page.locator('#billing-address-same-as-shipping-mercadopago_adbpayment_pse').check();

    await page.waitForLoadState();

    await page.locator('#payment_form_mercadopago_adbpayment_pse button.action.primary.checkout').click();
    await page.waitForLoadState();
}
