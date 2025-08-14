export const choproModalGuestUser = async function(page, card, document, status) {
    await selectChoPro(page);
    await page.waitForLoadState();
    await understoodButtonModal(page);
    await selectCreditCardAndFillDataModal(page, card, document, status);
    await mpInstallmentsAndPaymentFlowModal(page);
}

export const choproRedirectGuestUser = async function(page, card, document, status) {
  await selectChoPro(page);
  await page.waitForLoadState();
  await understoodButtonRedirect(page);
  await selectCreditCardAndFillDataRedirect(page, card, document, status);
  await mpInstallmentsAndPaymentFlowRedirect(page);
}

async function selectChoPro(page) {
    await page.waitForLoadState();
    await page.locator('#mercadopago_adbpayment_checkout_pro').check();
    await page.waitForLoadState();
    const myBillingAndShipping = page.locator('#billing-address-same-as-shipping-mercadopago_adbpayment_checkout_pro');
    await myBillingAndShipping.check();
    await page.waitForLoadState();
    await page.locator('#payment_form_mercadopago_adbpayment_checkout_pro button.action.primary.checkout').click();
  }

async function selectCreditCardAndFillDataModal(page, card, document, status) {
    await page.waitForLoadState();
    const modal = page.locator('#mercadopago-checkout').contentFrame();

    await page.waitForLoadState();
    await modal.locator('#new_card_row button.andes-list__item-action').click();
    await page.waitForLoadState();

    await modal.frameLocator('iframe[name="cardNumber"]').locator('[name="cardNumber"]').fill(card.number);
    await modal.locator('#cardholderName').fill(status);
    await modal.frameLocator('iframe[name="expirationDate"]').locator('[name="expirationDate"]').fill(card.date);
    await modal.frameLocator('iframe[name="securityCode"]').locator('[name="securityCode"]').fill(card.code);

    await page.waitForLoadState();
    await selectDocumentTypeModal(modal, document, page);
}

async function mpInstallmentsAndPaymentFlowModal(page){
    await page.waitForLoadState();
    await page.waitForTimeout(1000);
    const modal = page.locator('#mercadopago-checkout').contentFrame();
    await modal.locator('.button-wrapper button.continue_button').click();

    await page.waitForLoadState();
    await page.waitForTimeout(2000);
    const installments = modal.locator('.andes-card .andes-list');
    if (await installments.isVisible()) {
      await page.waitForLoadState();
      await modal.locator('ul li:first-child').click();
    }

    await page.waitForLoadState();
    await modal.locator('#pay').click();
    await page.waitForLoadState();
}

async function understoodButtonModal(page) {
    await page.waitForLoadState();
    if (await page.locator('#mercadopago-checkout').contentFrame().getByTestId('action:understood-button').isVisible()) {
      await page.locator('#mercadopago-checkout').contentFrame().getByTestId('action:understood-button').click();
    }
}

async function selectDocumentTypeModal(modal, document, page) {
    if (await modal.locator('#cardholderIdentificationNumber-dropdown-trigger').isVisible()) {
      await modal.locator('#cardholderIdentificationNumber-dropdown-trigger').click();
      await modal.getByRole('option', { name: document.type }).click();
      await page.waitForLoadState();
      await modal.getByTestId('identification-types--field').fill(document.number);
    }
}

async function selectCreditCardAndFillDataRedirect(page, card, document, status) {
    await page.waitForLoadState();
    await understoodButtonRedirect(page);

    await page.waitForLoadState();
    await page.locator('#new_card_row button.andes-list__item-action').click();
    await page.waitForLoadState();

    await page.frameLocator('iframe[name="cardNumber"]').locator('[name="cardNumber"]').fill(card.number);
    await page.locator('#cardholderName').fill(status);
    await page.frameLocator('iframe[name="expirationDate"]').locator('[name="expirationDate"]').fill(card.date);
    await page.frameLocator('iframe[name="securityCode"]').locator('[name="securityCode"]').fill(card.code);

    await page.waitForLoadState();
    await selectDocumentTypeRedirect(page, document);
}

async function mpInstallmentsAndPaymentFlowRedirect(page){
    await page.waitForLoadState();
    await page.waitForTimeout(1000);
    await page.locator('.button-wrapper button.continue_button').click();

    await page.waitForLoadState();
    await page.waitForTimeout(2000);
    const installments = page.locator('.andes-card .andes-list');
    if (await installments.isVisible()) {
      await page.waitForLoadState();
      await page.locator('ul li:first-child').click();
    }

    await page.waitForLoadState();
    await page.locator('.sidebar--default button.andes-button--progress').click();
    await page.waitForLoadState();
}

async function understoodButtonRedirect(page) {
    await page.waitForLoadState();
    if (await page.getByTestId('action:understood-button').isVisible()) {
      await page.getByTestId('action:understood-button').click();
    }
}

async function selectDocumentTypeRedirect(page, document) {
  if (await page.locator('#cardholderIdentificationNumber-dropdown-trigger').isVisible()) {
    await page.locator('#cardholderIdentificationNumber-dropdown-trigger').click();
    await page.getByRole('option', { name: document.type }).click();
    await page.waitForLoadState();
    await page.getByTestId('identification-types--field').fill(document.number);
  }
}
