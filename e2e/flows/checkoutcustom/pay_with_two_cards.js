export const payWithTwoCards = async function(page, cardOne, cardTwo, statusOne, statusTwo, document) {
    await page.waitForLoadState();
    await page.locator('#mercadopago_adbpayment_twocc').click();
    await page.waitForLoadState();
    await page.waitForTimeout(2000);

    await insertCreditCardData(page, cardOne, statusOne, document);
    await page.locator('#mp-second-card-radio').click();
    await page.waitForLoadState();
    await page.waitForTimeout(2000);
    await insertCreditCardData(page, cardTwo, statusTwo, document);

    await page.locator('#billing-address-same-as-shipping-mercadopago_adbpayment_twocc').check();
    await page.waitForLoadState();
    await page.locator('#payment_form_mercadopago_adbpayment_twocc button.action.primary.checkout').click();
    await page.waitForLoadState();
}

async function insertCreditCardData(page, card, status, document) {
    await page.frameLocator('iframe[name="cardNumber"]').locator('#cardNumber').fill(card.number);
    await page.frameLocator('iframe[name="expirationMonth"]').locator('#expirationMonth').fill(card.month);
    await page.frameLocator('iframe[name="expirationYear"]').locator('#expirationYear').fill(card.year);
    await page.frameLocator('iframe[name="securityCode"]').locator('#securityCode').fill(card.code);
    await page.locator('#mercadopago_adbpayment_twocc_cardholder_name').first().fill(status);

    await page.waitForLoadState();
    await validateDocument(page, document);

    await page.waitForTimeout(2000);
    await page.waitForLoadState();
    await page.waitForSelector('select[name="payment[card_installments]"]');
    await page.locator('select[name="payment[card_installments]"]').selectOption('1');

}

async function validateDocument(page, document) {
    const documentType = page.locator('#mercadopago_adbpayment_twocc_payer_document_type');
    if (await documentType.isVisible()) {
        await documentType.selectOption(document.type);
        await page.locator('#mercadopago_adbpayment_twocc_document_identification').fill(document.number);
        await page.waitForLoadState();
    }
}
