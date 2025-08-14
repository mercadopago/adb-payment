export default async function (page, user) {
  await page.locator('#mercadopago_adbpayment_checkout_credits').click();
  await page.locator('#billing-address-same-as-shipping-mercadopago_adbpayment_checkout_credits').check();
  await page.waitForLoadState();
  await page.locator('#payment_form_mercadopago_adbpayment_checkout_credits button.action.primary.checkout').click();

  await page.waitForURL('**/checkout/v1/payment/**');
  await page.waitForURL('**/login/**');

  await page.getByTestId('user_id').fill(user.email);
  await page.locator('.login-form .login-form__submit:first-child').click();

  await page.getByTestId('password').fill(user.password);
  await page.getByTestId('action-complete').click();
  await page.waitForLoadState();

  await page.locator('#installments_select_credits-trigger').click();
  await page.waitForLoadState();
  await page.click('#installments_select_credits-menu-list li:first-child');
  await page.waitForLoadState();
  await page.click('#pay');
  await page.waitForLoadState();
  await page.click('#group_button_back_congrats a:first-child');
}
