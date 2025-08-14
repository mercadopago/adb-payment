export const payWithTicket = async function(page, document, method) {
    await page.waitForLoadState();
    await page.waitForTimeout(3000);
    await page.locator('#mercadopago_adbpayment_payment_methods_off').click();

    await page.waitForLoadState();
    await page.waitForTimeout(1000);
    if (document !== null){
        await setDocument(page, document);
    }

    await page.waitForLoadState();
    await page.locator(`[id*="${method}"]`).click();
    await page.waitForLoadState();

    await page.locator('#billing-address-same-as-shipping-mercadopago_adbpayment_payment_methods_off').check();
    await page.waitForLoadState();
    await page.locator('#payment_form_mercadopago_adbpayment_payment_methods_off button.action.primary.checkout').click();
    await page.waitForLoadState();
}

async function setDocument(page, document) {
    await page.waitForLoadState();
    const documentType = page.locator('#mercadopago_adbpayment_payment_methods_off_payer_document_type');
    if (await documentType.isVisible()) {
        await documentType.selectOption(document.type);
        await page.locator('#mercadopago_adbpayment_payment_methods_off_document_identification').fill(document.number);
        await page.waitForLoadState();
    }
}
