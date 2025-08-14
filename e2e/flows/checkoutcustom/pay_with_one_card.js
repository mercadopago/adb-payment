export const payWithOneCard = async function (page, card, document, status) {
    await page.waitForLoadState();
    await page.locator('[type=radio][value="mercadopago_adbpayment_cc"]').click();
    await page.waitForLoadState();
    await page.waitForTimeout(2000);
    await validateDocument(page, document);

    await page.frameLocator('iframe[name="cardNumber"]').locator('[name="cardNumber"]').fill(card.number);
    await page.frameLocator('iframe[name="expirationMonth"]').locator('[name="expirationMonth"]').fill(card.month);
    await page.frameLocator('iframe[name="expirationYear"]').locator('[name="expirationYear"]').fill(card.year);

    await page.frameLocator('iframe[name="securityCode"]').locator('[name="securityCode"]').fill(card.code);
    await page.locator('[name="payment[card_holder_name]"]').first().fill(status);
    await page.waitForLoadState();
    await page.waitForTimeout(2000);
    if (await page.locator('select[name="payment[card_installments]"]').isVisible()) {
        await page.locator('select[name="payment[card_installments]"]').selectOption('1');
    }

    await page.locator('#billing-address-same-as-shipping-mercadopago_adbpayment_cc').check();
    await page.waitForLoadState();
    await page.locator('#payment_form_mercadopago_adbpayment_cc button.action.primary.checkout').click();
    await page.waitForLoadState();
}

export const payWithCardEmptyFields = async function (page) {
    await page.waitForLoadState();
    await page.locator('[type=radio][value="mercadopago_adbpayment_cc"]').click();
    await page.waitForLoadState();

    await page.locator('#billing-address-same-as-shipping-mercadopago_adbpayment_cc').check();
    await page.waitForLoadState();
    await page.locator('#payment_form_mercadopago_adbpayment_cc button.action.primary.checkout').click();
    await page.waitForLoadState();
}

async function validateDocument(page, document) {
    if (!document) {
        return;
    }

    const fieldSet = page.locator('#payment_form_cc_personamercadopago_adbpayment_cc');
    if (await fieldSet.isVisible()) {
        await page.locator('#mercadopago_adbpayment_cc_payer_document_type').selectOption(document.type);
        await page.locator('#mercadopago_adbpayment_cc_document_identification').fill(document.number);
        await page.waitForLoadState();
    }
}
