import { test } from "./../../test";
import { expect } from "@playwright/test";
import { choproRedirectLoginUser } from "../../../flows/checkoutpro/pay_with_account_money";

test('test successful account money payment with chopro redirect', async ({ page, siteIdParams }) => {
    const returnButton = page.locator('#group_button_back_congrats');
    const congratsApproved = page.locator('.congrats--approved');

    await choproRedirectLoginUser(page, siteIdParams.user);

    await expect(congratsApproved).toBeVisible();
    await expect(returnButton).toBeVisible();
    returnButton.click();

    await page.waitForLoadState();
    await page.waitForSelector('.checkout-onepage-success');
})
