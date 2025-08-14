export const choproModalLoginUser = async function(page, user) {
    await selectChoPro(page);
    await page.waitForLoadState();
    await understoodButtonModal(page);

    const modal = page.locator('#mercadopago-checkout').contentFrame();
    await modal.locator('#mp_login_row').click();

    await page.waitForLoadState();
    const popUpPromisse = await page.waitForEvent('popup');
    await popUpPromisse.getByTestId('user_id').fill(user.email);
    await popUpPromisse.locator('.login-form__actions .login-form__submit').click();
    await page.waitForLoadState();
    await popUpPromisse.getByTestId('password').fill(user.password);
    await popUpPromisse.getByTestId('action-complete').click();
    await page.waitForLoadState();
    await selectModalPayment(page);
}

async function selectModalPayment(page) {
    await page.waitForLoadState();
    const frame = await page.locator('#mercadopago-checkout').contentFrame();
    const button = frame.locator('.review-express__change-payment-method--single');
    if (button.isVisible()) {
      await button.click();
    }

    await frame.locator('#account_money').click();
    await page.waitForLoadState();
    await frame.locator('#pay').click();
    await page.waitForLoadState();
}

export const choproRedirectLoginUser = async function(page, user) {
    await selectChoPro(page);
    await page.waitForLoadState();
    await understoodButtonRedirect(page);

    await page.locator('#mp_login_row').click();
    await page.getByTestId('user_id').fill(user.email);
    await page.locator('.login-form__actions .login-form__submit').click();
    await page.waitForLoadState();
    await page.getByTestId('password').fill(user.password);
    await page.getByTestId('action-complete').click();
    await page.waitForLoadState();
    await selectRedirectPayment(page);
  }

async function selectRedirectPayment(page) {
    await page.waitForLoadState();
    const button = page.locator('.review-express__change-payment-method--single');

    if (button.isVisible()) {
      await button.click();
    }

    await page.locator('#account_money').click();
    await page.waitForLoadState();
    await page.locator('.sidebar--default button.andes-button--progress').click();
    await page.waitForLoadState();
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

async function understoodButtonModal(page) {
  await page.waitForLoadState();
  if (await page.locator('#mercadopago-checkout').contentFrame().getByTestId('action:understood-button').isVisible()) {
    await page.locator('#mercadopago-checkout').contentFrame().getByTestId('action:understood-button').click();
  }
}

async function understoodButtonRedirect(page) {
  await page.waitForLoadState();
  if (await page.getByTestId('action:understood-button').isVisible()) {
    await page.getByTestId('action:understood-button').click();
  }
}
