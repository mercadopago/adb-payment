export const payWithPix = async function(page, document) {
    await page.waitForLoadState();
    await page.locator('#mercadopago_adbpayment_pix').click();

    await page.waitForLoadState();
    await page.waitForTimeout(2000);

    if (document !== null) {
        await setDocument(page, document);
    }
    await page.locator('#billing-address-same-as-shipping-mercadopago_adbpayment_pix').check();
    await page.waitForLoadState();
    await page.locator('#payment_form_mercadopago_adbpayment_pix button.action.primary.checkout').click();
    await page.waitForLoadState();
}

async function setDocument(page, document) {
    const documentType = page.locator('#mercadopago_adbpayment_pix_payer_document_type');
        if (await documentType.isVisible()) {
            await documentType.selectOption(document.type);
            await page.locator('#mercadopago_adbpayment_pix_document_identification').fill(document.number);
            await page.waitForLoadState();
        }
}
