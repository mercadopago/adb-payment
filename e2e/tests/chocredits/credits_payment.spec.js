import { test } from "./../test";
import { expect } from "@playwright/test";
import payWithChoCredits from "../../flows/pay_with_cho_credits";

test.beforeEach(({ siteIdParams }) => {
  test.skip(!['MLB', 'MLA', 'MLM'].includes(siteIdParams.siteId));
});

test('test successful payment with pre approved credit, payment must be approved and success page must be shown', async ({ page, siteIdParams }) => {
  await payWithChoCredits(page, siteIdParams.user);

  await page.waitForURL('**/checkout/onepage/success/**');

  await expect(page.locator('.checkout-onepage-success')).toBeVisible();
});
